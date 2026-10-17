<?php

declare(strict_types=1);

namespace Xchert\Exception\Test\Dummy;

use Xchert\Exception\ErrorFactory\ErrorFactory;

readonly class PublicDummyService
{
    public function __construct(
        private ErrorFactory $errorFactory
    ) {}

    public function getErrorFactory(): ErrorFactory
    {
        return $this->errorFactory;
    }
}