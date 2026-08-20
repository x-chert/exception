<?php

namespace Xchert\Exception;

use Symfony\Component\HttpFoundation\Response;

class ExceptionStack
{
    private array $errors = [];

    public function __construct(
        private ?string $message = null,
        private ?string $source = null,
        private array $parameters = [],
        private int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
    ) {}

    public function getMessage(): string
    {
        return $this->message ?? $this->createMessage();
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): void
    {
        $this->source = $source;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
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
            $this->parameters,
            $this->statusCode,
            $this->source,
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