<?php

namespace Stev\GoogleCloudTranslateBundle\Services;


use JMS\TranslationBundle\Model\MessageCatalogue;
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
     * @var CatalogueTranslator
     */
    private $catalogueTranslator;

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
     * @param CatalogueTranslator $catalogueTranslator
     * @param FileWriter $fileWriter
     * @param LoggerInterface $logger
     *
     */
    public function __construct(
        LoaderManager $loaderManager,
        LoggerInterface $logger,
        FileWriter $fileWriter,
        CatalogueTranslator $catalogueTranslator
    ) {
        $this->loaderManager = $loaderManager;
        $this->logger = $logger;
        $this->fileWriter = $fileWriter;
        $this->catalogueTranslator = $catalogueTranslator;
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
        $destinationCatalogue = $this->buildCatalogue($destinationLanguage);
        $sourceCatalogue = $this->buildCatalogue($sourceLanguage);

        $totalTranslatedMessages = $this->catalogueTranslator->translateCatalogue(
            $sourceCatalogue,
            $destinationCatalogue
        );

        $this->saveTranslations($destinationCatalogue);


        $this->logger->info(sprintf('Translated %s messages', $totalTranslatedMessages));
    }


    private function buildCatalogue($locale)
    {
        $catalogue = new MessageCatalogue();
        $catalogue->setLocale($locale);

        $catalogue->merge(
            $this->loaderManager->loadFromDirectory(
                $this->translationsConfig->getTranslationsDir(),
                $locale
            )
        );

        return $catalogue;
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


}