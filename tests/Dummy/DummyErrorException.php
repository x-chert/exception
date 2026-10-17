<?php

declare(strict_types=1);

namespace Xchert\Exception\Test\Dummy;

use Symfony\Component\HttpFoundation\Response;
use Xchert\Exception\ErrorException;

class DummyErrorException extends ErrorException
{
    public function getErrorCode(): string
    {
        return 'XCHERT_EXCEPTION__TEST_ERROR';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}