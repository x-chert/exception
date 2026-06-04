<?php

namespace Xchert\Exception\ErrorFactory;

use Xchert\Exception\Error;
use Xchert\Exception\ErrorExceptionInterface;

class ErrorExceptionMapper implements ErrorMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof ErrorExceptionInterface;
    }

    /**
     * @param ErrorExceptionInterface $exception
     */
    public function getErrors(\Throwable $exception, bool $debug, ErrorFactory $factory): array
    {
        $errors = [];
        $previous = $exception->getPrevious();

        if($previous !== null) {
            $errors = $factory->getErrors($previous, $debug);
        }

        $errors[] = new Error(
            $exception->getId(),
            $exception->getErrorCode(),
            $exception->getMessage(),
            $exception->getParameters(),
            $exception->getStatusCode(),
            $debug ? $exception->getTrace() : null,
            $exception->getType(),
            $exception->getSource(),
            $exception->getOccurredAt()
        );

        return $errors;
    }
}