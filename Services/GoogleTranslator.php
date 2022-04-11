<?php

namespace Stev\GoogleCloudTranslateBundle\Services;


use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Model\MessageCollection;
use JMS\TranslationBundle\Translation\Config;
use JMS\TranslationBundle\Translation\FileWriter;
use JMS\TranslationBundle\Translation\LoaderManager;
use Psr\Log\LoggerInterface;
use RuntimeException;

class GoogleTranslator
{

    /**
     * @var LoaderManager
     */
    private $loaderManager;

    /**
     * @var GoogleTranslateAPI
     */
    private $googleCloudAPI;

    /**
     * @var Config
     */
    private $translationsConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FileWriter
     */
    private $fileWriter;

    /**
     * @param FileWriter $fileWriter
     * @param LoggerInterface $logger
     * @param GoogleTranslateAPI $googleCloudAPI
     */
    public function __construct(
        LoaderManager $loaderManager,
        GoogleTranslateAPI $googleCloudAPI,
        LoggerInterface $logger,
        FileWriter $fileWriter
    ) {
        $this->loaderManager = $loaderManager;
        $this->googleCloudAPI = $googleCloudAPI;
        $this->logger = $logger;
        $this->fileWriter = $fileWriter;
    }

    /**
     * @param Config $translationsConfig
     */
    public function setTranslationsConfig(Config $translationsConfig)
    {
        $this->translationsConfig = $translationsConfig;
    }


    public function translate($sourceLanguage, $destinationLanguage)
    {

        $destinationCatalogue = $this->buildDestinationCatalogue($destinationLanguage);
        $sourceCatalogue = $this->buildSourceCatalogue($sourceLanguage);

        $totalTranslatedMessages = $this->translateMessages($sourceCatalogue, $destinationCatalogue);


        $this->saveTranslations($destinationCatalogue);


        $this->logger->info(sprintf('Translated %s messages', $totalTranslatedMessages));
    }

    private function buildDestinationCatalogue($destinationLanguage)
    {
        $destinationCatalogue = new MessageCatalogue();
        $destinationCatalogue->setLocale($destinationLanguage);

        $destinationCatalogue->merge(
            $this->loaderManager->loadFromDirectory(
                $this->translationsConfig->getTranslationsDir(),
                $destinationLanguage
            )
        );

        return $destinationCatalogue;
    }

    private function buildSourceCatalogue($sourceLanguage)
    {
        $sourceCatalogue = new MessageCatalogue();
        $sourceCatalogue->setLocale($sourceLanguage);

        $sourceCatalogue->merge(
            $this->loaderManager->loadFromDirectory(
                $this->translationsConfig->getTranslationsDir(),
                $sourceLanguage
            )
        );

        return $sourceCatalogue;
    }

    /**
     *
     * It will build a list of message to translate following this rules:
     *  - The message must be NEW in the destination, if NEW the message is not translated yet
     *  - The message must not be NEW in the source, if NEW the message is not yet in its final form
     *
     * @param MessageCatalogue $sourceCatalogue
     * @param MessageCatalogue $destinationCatalogue
     *
     */
    private function translateMessages(MessageCatalogue $sourceCatalogue, MessageCatalogue $destinationCatalogue)
    {
        $count = 0;
        /** @var MessageCollection $sourceDomain */
        foreach ($sourceCatalogue->getDomains() as $sourceDomainName => $sourceDomain) {
            /** @var Message $sourceMessage */
            foreach ($sourceDomain->all() as $sourceMessage) {
                if ($sourceMessage->isNew()) {
                    //if a message is new in source then it is not yet in the final form, so we should not translate it
                    continue;
                }

                $destinationDomain = $destinationCatalogue->getDomain($sourceDomainName);
                /** @var Message $destinationMessage */
                foreach ($destinationDomain->all() as $destinationMessage) {
                    //if the message is not new in the destination it was probably already translated
                    if (!$destinationMessage->isNew()) {
                        continue;
                    }

                    if ($destinationMessage->getId() === $sourceMessage->getId()) {
                        $translation = $this->translateText(
                            $sourceMessage->getLocaleString(),
                            $sourceCatalogue->getLocale(),
                            $destinationCatalogue->getLocale()
                        );

                        if (!isset($translation['text'])) {
                            $this->logger->error(
                                sprintf(
                                    "Unable to translate a text for locale %s of domain %s",
                                    $destinationCatalogue->getLocale(),
                                    $sourceDomainName
                                )
                            );
                            $this->logger->error(sprintf('Text not translated: %s', $sourceMessage->getLocaleString()));
                            break;
                        }

                        $destinationMessage->setLocaleString($translation['text'])
                            ->setNew(false);

                        $count++;
                        break;
                    }
                }
            }
        }

        return $count;
    }

    private function saveTranslations(MessageCatalogue $destinationCatalogue)
    {
        $format = $this->translationsConfig->getOutputFormat() ?: $this->translationsConfig->getDefaultOutputFormat();

        if (!$format) {
            throw new RuntimeException(
                'You must set the output format or the default output format in your JMS Translation bundle config!'
            );
        }

        foreach ($destinationCatalogue->getDomains() as $domainName => $destinationDomain) {
            $outputFile = $this->translationsConfig->getTranslationsDir(
                ).'/'.$domainName.'.'.$destinationCatalogue->getLocale().'.'.$this->translationsConfig->getOutputFormat(
                );

            $this->fileWriter->write($destinationCatalogue, $domainName, $outputFile, $format);
        }
    }

    private function translateText($text, $sourceLocale, $destinationLocale)
    {
        /*
array:4 [
  "source" => "en"
  "input" => "Test message label"
  "text" => "Tester l&#39;étiquette du message"
  "model" => null
]
    */

        return $this->googleCloudAPI->getClient()->translate(
            $text,
            [
                'source' => $sourceLocale,
                'target' => $destinationLocale,
                'format' => 'html',
            ]
        );

    }
}