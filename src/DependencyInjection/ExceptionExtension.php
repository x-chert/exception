<?php

declare(strict_types=1);

namespace Xchert\Exception\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class ExceptionExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configs = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('exception.problem_type_base_uri', $configs['problem_type_base_uri']);
    }
}