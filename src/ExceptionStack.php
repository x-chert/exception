<?php

namespace Xchert\Exception;

use Symfony\Component\HttpFoundation\Response;

class ExceptionStack
{
    private array $errors = [];

    public function __construct(
        private readonly string $errorCode,
        private readonly ?string $message = null,
        private readonly ?string $title = null,
        private readonly int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
    ) {}

    public function getMessage(): string
    {
        return $this->message ?? $this->createMessage();
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function add(\Throwable ...$errors): void
    {
        foreach ($errors as $error) {
            $this->errors[] = $error;
        }
    }

    public function throw(): void
    {
        if (\count($this->errors) === 0) {
            return;
        }

        throw new StackException(
            $this->message ?? $this->createMessage(),
            $this->title ?? 'Multiple errors occurred.',
            $this->statusCode,
            $this->errorCode,
            null,
            ...$this->errors
        );
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    protected function createMessage(): string
    {
        $message = '';

        foreach ($this->errors as $error) {
            $message .= ' • '.trim($error->getMessage())."\n";
        }

        return $message;
    }

}