<?php

namespace Xchert\Exception\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Xchert\Exception\ErrorFactory\ErrorFactory;

class ErrorMapperCompilerPass implements CompilerPassInterface
{
    public const string TAG_MAPPERS = 'xch.exception.error_mapper';

    public function process(ContainerBuilder $container): void
    {
        $factoryDefinition = $container->getDefinition(ErrorFactory::class);
        $taggedMappers = $container->findTaggedServiceIds(self::TAG_MAPPERS);

        foreach($taggedMappers as $serviceId => $tags) {
            foreach($tags as $config) {
                $id = $config['id'] ?? null;
                $priority = (int) ($config['priority'] ?? 500);

                if(!\is_string($id) || empty($id)) {
                    throw new \Exception('Id of error mapper must not be empty.');
                }

                $factoryDefinition->addMethodCall(
                    'addMapper',
                    [
                        new Reference($serviceId),
                        $id,
                        $priority
                    ]
                );
            }
        }
    }
}
