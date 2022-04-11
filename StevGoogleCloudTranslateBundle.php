<?php

namespace Stev\GoogleCloudTranslateBundle;

use Stev\GoogleCloudTranslateBundle\DependencyInjection\Compiler\LoadersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class StevGoogleCloudTranslateBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new LoadersPass());
    }
}
