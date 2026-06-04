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
}