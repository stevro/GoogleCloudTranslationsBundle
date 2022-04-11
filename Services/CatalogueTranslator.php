<?php

namespace Stev\GoogleCloudTranslateBundle\Services;

use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Model\MessageCollection;
use Psr\Log\LoggerInterface;

class CatalogueTranslator
{
    /**
     * @var GoogleTranslateAPI
     */
    private $googleCloudAPI;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param GoogleTranslateAPI $googleCloudAPI
     * @param LoggerInterface $logger
     */
    public function __construct(GoogleTranslateAPI $googleCloudAPI, LoggerInterface $logger)
    {
        $this->googleCloudAPI = $googleCloudAPI;
        $this->logger = $logger;
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
    public function translateCatalogue(MessageCatalogue $sourceCatalogue, MessageCatalogue $destinationCatalogue)
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