<?php

namespace Xchert\Exception;

class StackException extends ErrorException
{
    public const ERROR_CODE = 'XCHERT_EXCEPTION__STACK_ERROR';

    private array $errors;
    private int $status;

    public function __construct(
        string $message,
        array $parameters,
        string $source,
        int $statusCode,
        ?\Throwable $previous,
        \Throwable ...$errors
    ) {
        parent::__construct(
            $message,
            $parameters,
            $source,
            [],
            $previous
        );

        $this->errors = $errors;
        $this->status = $statusCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

}