<?php

declare(strict_types=1);

namespace Xchert\Exception\Test\Dummy;

use Symfony\Component\HttpFoundation\Response;
use Xchert\Exception\ErrorException;

class TestErrorException extends ErrorException
{
    public const string ERROR_CODE = 'TEST_ERROR';

    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_UNPROCESSABLE_ENTITY;
    }
}
