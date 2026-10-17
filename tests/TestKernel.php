<?php

declare(strict_types=1);

namespace Xchert\Exception\Test;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Kernel;
use Xchert\Exception\ExceptionBundle;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new ExceptionBundle();
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/config/config.yaml');

        parent::registerContainerConfiguration($loader);
    }

    protected function build(ContainerBuilder $container): void
    {
        $locator = new FileLocator(__DIR__.'/config');
        $loader = new YamlFileLoader($container, $locator);

        $loader->load('services.yaml');

        parent::build($container);
    }
}
