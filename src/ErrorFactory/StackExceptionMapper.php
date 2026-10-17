<?php

namespace Xchert\Exception\ErrorFactory;

use Xchert\Exception\Attribute\AsErrorMapper;
use Xchert\Exception\StackException;

#[AsErrorMapper(id: 'stack_exception', priority: 1100)]
class StackExceptionMapper extends ErrorExceptionMapper
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
        \assert($exception instanceof StackException);

        $errors = [];
        $previous = $exception->getPrevious();

        if ($previous !== null) {
            $errors = $factory->getErrors($previous, $debug);
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
}