<?php

namespace Stev\GoogleCloudTranslateBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LoadersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('stev_google_cloud_translate.services.loader_manager')) {
            return;
        }

        $loaders = [];

        foreach ($container->findTaggedServiceIds('translation.loader') as $id => $attr) {
            if (!isset($attr[0]['alias'])) {
                throw new \RuntimeException(sprintf('The attribute "alias" must be defined for tag "translation.loader" for service "%s".', $id));
            }

            $loaders[$attr[0]['alias']] = new Reference($id);
            if (isset($attr[0]['legacy_alias'])) {
                $loaders[$attr[0]['legacy_alias']] = new Reference($id);
            }
        }

        $container
            ->getDefinition('stev_google_cloud_translate.services.loader_manager')
            ->addArgument($loaders);
    }
}