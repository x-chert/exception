<?php

namespace Xchert\Exception\ErrorFactory;

use Xchert\Exception\Error;

interface ErrorMapperInterface
{
    public function supports(\Throwable $exception): bool;

    /**
     * @return Error[]
     */
    public function getErrors(\Throwable $exception, bool $debug, ErrorFactory $factory): array;

    public function createError(\Throwable $exception, bool $debug, array $errors, ErrorFactory $factory): Error;

    public function getStatusCode(\Throwable $exception): int;

    public function getHeaders(\Throwable $exception): array;

    public function getErrorCode(\Throwable $exception): string;

    public function getTitle(\Throwable $exception): string;
}