<?php

declare(strict_types=1);

namespace Xchert\Exception\DependencyInjection;

use Symfony\Component\DependencyInjection\Definition;
use Xchert\Exception\Attribute\AsErrorMapper;
use Xchert\Exception\DependencyInjection\CompilerPass\ErrorMapperCompilerPass;

class AsErrorMapperAutoConfiguration
{
    public function autoconfigure(Definition $definition, AsErrorMapper $attribute): void
    {
        $definition->addTag(ErrorMapperCompilerPass::TAG, [
            'id' => $attribute->id,
            'priority' => $attribute->priority,
        ]);
    }
}