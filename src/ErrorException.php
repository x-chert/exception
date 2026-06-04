<?php

namespace Xchert\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class ErrorException extends HttpException implements ErrorExceptionInterface
{
    private readonly \DateTimeInterface $occurredAt;

    private readonly string $id;

    public function __construct(
        string $message,
        private readonly array $parameters = [],
        private readonly string $source = 'unknown',
        array $headers = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $this->getStatusCode(),
            $this->buildMessage($message, $parameters),
            $previous,
            $headers,
        );

        $this->occurredAt = new \DateTimeImmutable();
        $this->id = $this->generateId();
    }

    abstract public function getErrorCode(): string;

    public function getId(): string
    {
        return $this->id;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getType(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getOccurredAt(): \DateTimeInterface
    {
        return $this->occurredAt;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    protected function buildMessage(string $message, array $parameters = []): string
    {
        if(empty($parameters)) {
            return $message;
        }

        $regex = [];

        foreach($parameters as $key => $value) {
            if(\is_array($value)) {
                continue;
            }

            $formattedKey = \preg_replace('/[^a-z]/i', '', $key);
            $regex[\sprintf('/\{\{(\s+)?(%s)(\s+)?\}\}/', $formattedKey)] = $value;
        }

        return (string) \preg_replace(\array_keys($regex), \array_values($regex), $message);
    }

    protected function generateId(): string
    {
        return \bin2hex(\random_bytes(16));
    }
}