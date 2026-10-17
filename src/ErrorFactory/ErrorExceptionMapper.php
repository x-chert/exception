<?php

namespace Xchert\Exception\ErrorFactory;

use Xchert\Exception\Attribute\AsErrorMapper;
use Xchert\Exception\Error;
use Xchert\Exception\ErrorExceptionInterface;
use Xchert\Exception\Util;

#[AsErrorMapper(id: 'error_exception', priority: 1000)]
class ErrorExceptionMapper extends HttpExceptionMapper
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof ErrorExceptionInterface;
    }

    public function createError(\Throwable $exception, bool $debug, array $errors, ErrorFactory $factory): Error
    {
        \assert($exception instanceof ErrorExceptionInterface);

        $params = $exception->getParameters();

        return new Error(
            instance: Util::createInstance($exception->getId()),
            type: $factory->createType($exception->getErrorCode()),
            title: $exception->getTitle(),
            detail: $exception->getMessage(),
            status: $exception->getStatusCode(),
            code: $exception->getErrorCode(),
            occurredAt: $exception->getOccurredAt(),
            trace: $debug ? $exception->getTrace() : null,
            additional: empty($params) ? [] : ['parameters' => $params],
            errors: $errors
        );
    }

    public function getErrorCode(\Throwable $exception): string
    {
        \assert($exception instanceof ErrorExceptionInterface);

        return $exception->getErrorCode();
    }

    public function getTitle(\Throwable $exception): string
    {
        \assert($exception instanceof ErrorExceptionInterface);

        return $exception->getTitle();
    }
}
