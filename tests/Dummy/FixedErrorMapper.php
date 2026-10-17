<?php

declare(strict_types=1);

namespace Xchert\Exception\Test\Dummy;

use Xchert\Exception\Error;
use Xchert\Exception\ErrorFactory\ErrorFactory;
use Xchert\Exception\ErrorFactory\ErrorMapperInterface;

/**
 * Mapper that supports a configurable exception class and identifies itself by its name.
 */
class FixedErrorMapper implements ErrorMapperInterface
{
    /**
     * @param class-string<\Throwable> $supportedClass
     */
    public function __construct(
        public readonly string $name,
        private readonly string $supportedClass = \Throwable::class,
        private readonly int $statusCode = 418,
        private readonly array $headers = [],
    ) {}

    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof $this->supportedClass;
    }

    public function getErrors(\Throwable $exception, bool $debug, ErrorFactory $factory): array
    {
        return [$this->createError($exception, $debug, [], $factory)];
    }

    public function createError(\Throwable $exception, bool $debug, array $errors, ErrorFactory $factory): Error
    {
        return new Error(
            instance: 'urn:test:'.$this->name,
            type: $factory->createType($this->name),
            title: $this->name,
            detail: $exception->getMessage(),
            status: $this->statusCode,
            code: $this->name,
            occurredAt: new \DateTimeImmutable(),
            errors: $errors,
        );
    }

    public function getStatusCode(\Throwable $exception): int
    {
        return $this->statusCode;
    }

    public function getHeaders(\Throwable $exception): array
    {
        return $this->headers;
    }

    public function getErrorCode(\Throwable $exception): string
    {
        return $this->name;
    }

    public function getTitle(\Throwable $exception): string
    {
        return $this->name;
    }
}
