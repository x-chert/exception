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
        private readonly string $title,
        private readonly array $parameters = [],
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
        $this->id = Util::createId();
    }

    abstract public function getErrorCode(): string;

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getParameters(): array
    {
        return $this->parameters;
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
        if (empty($parameters)) {
            return $message;
        }

        return (string)\preg_replace_callback(
            '/\{\{\s*([^\s{}]+)\s*\}\}/',
            static function (array $match) use ($parameters): string {
                $value = $parameters[$match[1]] ?? null;

                if (\is_scalar($value) || $value instanceof \Stringable) {
                    return (string)$value;
                }

                return $match[0];
            },
            $message
        );
    }
}