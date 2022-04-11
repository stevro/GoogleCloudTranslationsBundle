<?php

namespace Stev\GoogleCloudTranslateBundle\Tests\Services;

use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCollection;
use JMS\TranslationBundle\Translation\Config;
use Stev\GoogleCloudTranslateBundle\Tests\BaseTestCase;
use Symfony\Component\Filesystem\Filesystem;

class GoogleTranslatorTest extends BaseTestCase
{
#./vendor/symfony/phpunit-bridge/bin/simple-phpunit -c app
    public function testAllTranslation()
    {
        self::bootKernel();

        $translator = static::$kernel->getContainer()->get('stev_google_cloud_translate.services.google_translator');

        $translationsPath = sys_get_temp_dir().'/stev-google-translations';

        $fileSystem = new Filesystem();
        $fileSystem->mirror(__DIR__.'/../Fixtures/translations', $translationsPath);

        $config = new Config(
            $translationsPath,
            'en',
            [],
            [],
            'xliff',
            'xliff',
            [__DIR__.'/../Fixtures/Controller'],
            [],
            [],
            [],
            true,
            []
        );

        $translator->setTranslationsConfig($config);

        $translator->translate('en', 'fr');


        $loaderManager = static::$kernel->getContainer()->get('jms_translation.loader_manager');

        $translatedCatalogue = $loaderManager->loadFromDirectory($translationsPath, 'fr');

        /** @var MessageCollection $domain */
        foreach ($translatedCatalogue->getDomains() as $domainName => $domain) {
            /** @var Message $message */
            foreach ($domain->all() as $message) {
                switch ($domainName) {
                    case 'messages':
                        switch ($message->getId()) {
                            case '__test_msg_label':
                                $this->assertEquals('Tester l&#39;étiquette du message', $message->getLocaleString());
                                break;
                            case '_controller_test_message':
                                $this->assertEquals('je suis un bon contrôleur', $message->getLocaleString());
                                break;
                            case '_placeholder_message':
                                $this->assertEquals(
                                    'Bonjour %myVar%, comment allez-vous ?',
                                    $message->getLocaleString()
                                );
                                break;
                            case '_plural_message':
                                $this->assertEquals(
                                    '{0}%name% n&#39;a pas de pommes|{1}%name% a une pomme|]1,Inf[ %name% a %count% pommes',
                                    $message->getLocaleString()
                                );
                                break;
                        }
                        break;
                    case 'MY custom domain':
                        switch ($message->getId()) {
                            case '_controller_test_message':
                                $this->assertEquals(
                                    'Tester l&#39;envoi de messages à partir d&#39;un contrôleur personnalisé',
                                    $message->getLocaleString()
                                );
                                break;
                        }
                        break;
                }
            }
        }

        $fileSystem->remove($translationsPath);
    }

    public function testSimpleTranslation()
    {
        self::bootKernel();
        $googleApiTranslator = static::$kernel->getContainer()->get(
            'stev_google_cloud_translate.services.google_translate_api'
        );

        $sourceText = "I am a good developer";

        $translation = $googleApiTranslator->getClient()->translate($sourceText, [
            'source' => 'en',
            'target' => 'fr',
            'format' => 'html',
        ]);

        $this->assertEquals('je suis un bon développeur', $translation['text']);
    }

}
