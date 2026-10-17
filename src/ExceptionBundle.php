<?php

namespace Xchert\Exception;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Xchert\Exception\Attribute\AsErrorMapper;
use Xchert\Exception\DependencyInjection\AsErrorMapperAutoConfiguration;
use Xchert\Exception\DependencyInjection\CompilerPass\ErrorMapperCompilerPass;

class ExceptionBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(AsErrorMapper::class, [new AsErrorMapperAutoConfiguration(), 'autoconfigure']);
        $container->addCompilerPass(new ErrorMapperCompilerPass());

        $locator = new FileLocator($this->getPath().'/DependencyInjection/');
        $loader = new YamlFileLoader($container, $locator);

        $loader->load('services.yaml');
    }
}
