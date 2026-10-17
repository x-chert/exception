<?php

namespace Xchert\Exception;

class StackException extends ErrorException
{
    private array $errors;

    public function __construct(
        string $message,
        string $title,
        private readonly int $status,
        private readonly string $errorCode,
        ?\Throwable $previous = null,
        \Throwable ...$errors
    ) {
        parent::__construct(
            $message,
            $title,
            [],
            [],
            $previous
        );

        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

}