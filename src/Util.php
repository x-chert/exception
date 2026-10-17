<?php

declare(strict_types=1);

namespace Xchert\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class Util
{
    public static function createId(): string
    {
        return Uuid::v7()->toRfc4122();
    }

    public static function createInstance(?string $id = null): string
    {
        return 'urn:uuid:'.($id ?? self::createId());
    }

    public static function getStatusTitle(int $status): string
    {
        return Response::$statusTexts[$status] ?? 'Unknown Error';
    }
}
