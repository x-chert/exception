<?php

namespace Xchert\Exception\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Xchert\Exception\ErrorFactory\ErrorFactory;
use Xchert\Util\Value;

class ErrorMapperCompilerPass implements CompilerPassInterface
{
    public const string TAG = 'exception.error_mapper';

    /**
     * @throws \Exception
     */
    public function process(ContainerBuilder $container): void
    {
        $factoryDefinition = $container->getDefinition(ErrorFactory::class);
        $taggedMappers = $container->findTaggedServiceIds(self::TAG);

        /**
         * @param string $serviceId
         * @param array $tags
         */
        foreach ($taggedMappers as $serviceId => $tags) {
            \assert(\is_array($tags));

            foreach ($tags as $tag) {
                \assert(\is_array($tag));

                $id = (string)($tag['id'] ?? null);

                if (Value::isEmpty($id)) {
                    throw new \Exception('Id of error mapper must not be empty.');
                }

                $priority = (int)($tag['priority'] ?? 0);

                $factoryDefinition->addMethodCall('addMapper', [new Reference($serviceId), $id, $priority]);
            }
        }
    }
}
