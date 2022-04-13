<?php

namespace Stev\GoogleCloudTranslateBundle\Command;

use JMS\TranslationBundle\Translation\ConfigFactory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TranslateCommandCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('stev:google_cloud_translate')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'The config to use')
            ->addOption('sourceLocale', null, InputOption::VALUE_REQUIRED, 'The source locale')
            ->addOption('destinationLocale', null, InputOption::VALUE_REQUIRED, 'The destination locale')
            ->setDescription('Translates from source locale to destination locale');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $googleTranslator = $this->getContainer()->get('stev_google_cloud_translate.services.google_translator');


        /** @var ConfigFactory $builder */
        $configFactory = $this->getContainer()->get('jms_translation.config_factory');
        $config = $configFactory->getConfig($input->getOption('config'), $input->getOption('sourceLocale'));


        $googleTranslator->setTranslationsConfig($config);
        $googleTranslator->translate($input->getOption('sourceLocale'), $input->getOption('destinationLocale'));


        $output->writeln('Translations done');
    }

}
