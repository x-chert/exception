<?php

namespace Xchert\Exception\ErrorFactory;

use Xchert\Exception\StackException;

class StackExceptionMapper implements ErrorMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof StackException;
    }

    /**
     * @param StackException $exception
     */
    public function getErrors(\Throwable $exception, bool $debug, ErrorFactory $factory): array
    {
        $errors = [];
        $previous = $exception->getPrevious();

        if ($previous !== null) {
            $errors = \array_merge(
                $errors,
                $factory->getErrors($previous, $debug)
            );
        }

        /** @var \Throwable $error */
        foreach ($exception->getErrors() as $e) {
            $errors = \array_merge(
                $errors,
                $factory->getErrors($e, $debug)
            );
        }

        return $errors;
    }

    public function getStatusCode(\Throwable $exception): int
    {
        \assert($exception instanceof StackException);

        return $exception->getStatusCode();
    }

    public function getHeaders(\Throwable $exception): array
    {
        \assert($exception instanceof StackException);

        return $exception->getHeaders();
    }
}