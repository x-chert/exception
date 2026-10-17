<?php

declare(strict_types=1);

namespace Xchert\Exception\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class AsErrorMapper
{
    public function __construct(
        public string $id,
        public int $priority,
    ) {}
}