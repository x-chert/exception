<?php

declare(strict_types=1);

namespace Xchert\Exception\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder() : TreeBuilder {
        $treeBuilder = new TreeBuilder('exception');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->scalarNode('problem_type_base_uri')
                ->info('Absolute URI the error code is appended to for the problem type, e.g. "https://example.com/problems/". Without it, the type is "about:blank".')
                ->defaultNull()
                ->validate()
                    ->ifTrue(static fn(mixed $uri): bool => $uri !== null && (!\is_string($uri) || !\preg_match('/^[a-z][a-z\d+.-]*:/i', $uri)))
                    ->thenInvalid('The problem type base URI must be an absolute URI, got %s.')
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}