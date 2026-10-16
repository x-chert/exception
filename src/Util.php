<?php

declare(strict_types=1);

namespace Xchert\Exception;

use Symfony\Component\Uid\Uuid;

class Util
{
    public static function createId(): string
    {
        return Uuid::v7()->toRfc4122();
    }
}