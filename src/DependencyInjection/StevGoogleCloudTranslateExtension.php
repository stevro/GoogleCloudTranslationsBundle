<?php

namespace Stev\GoogleCloudTranslateBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class StevGoogleCloudTranslateExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        if(!isset($config['keyFilePath'])){
            throw new \RuntimeException('Path to google cloud key file [keyFilePath] is mandatory configs for Stev\StevGoogleCloudTranslateBundle');
        }

        $container->setParameter('stev_google_cloud_translate.translate_api_config', [
                'keyFilePath' => $config['keyFilePath']
        ]);
    }
}
