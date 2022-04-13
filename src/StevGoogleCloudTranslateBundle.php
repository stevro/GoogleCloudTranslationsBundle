<?php

namespace Stev\GoogleCloudTranslateBundle;

use Stev\GoogleCloudTranslateBundle\DependencyInjection\Compiler\LoadersPass;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class StevGoogleCloudTranslateBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container)
    {

        $container->addCompilerPass(new LoadersPass());
    }
}
