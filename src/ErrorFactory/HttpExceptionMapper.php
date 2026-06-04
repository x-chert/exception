<?php

namespace Xchert\Exception\ErrorFactory;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Xchert\Exception\Error;

class HttpExceptionMapper implements ErrorMapperInterface
{

    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof HttpException;
    }

    /**
     * @param HttpException $exception
     */
    public function getErrors(\Throwable $exception, bool $debug, ErrorFactory $factory): array
    {
        $errors = [];
        $previous = $exception->getPrevious();

        if($previous !== null) {
            $errors = $factory->getErrors($previous, $debug);
        }

        $errors[] = new Error(
            \bin2hex(\random_bytes(16)),
            Error::ERROR_CODE_UNKNOWN,
            $exception->getMessage(),
            $exception->getHeaders(),
            $exception->getStatusCode(),
            $debug ? $exception->getTrace() : null,
            (new \ReflectionClass($exception))->getShortName()
        );

        return $errors;
    }
}