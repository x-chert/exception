<?php

namespace Xchert\Exception;

use DateTimeInterface;
use Symfony\Component\HttpFoundation\Response;

class Error implements \JsonSerializable
{
    public const ERROR_CODE_UNKNOWN = 'XCHERT_EXCEPTION__UNKNOWN_ERROR';

    public function __construct(
        private readonly string $id,
        private readonly string $code,
        private readonly string $message,
        private readonly array $parameters = [],
        private readonly int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
        private readonly ?array $trace = null,
        private readonly string $type = 'Error',
        private readonly string $source = 'unknown',
        private readonly ?DateTimeInterface $occuredAt = new \DateTime()
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getErrorCode(): string
    {
        return $this->code;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getTrace(): ?array
    {
        return $this->trace;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getOccuredAt(): DateTimeInterface
    {
        return $this->occuredAt;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'message' => $this->message,
            'parameters' => $this->parameters,
            'statusCode' => $this->statusCode,
            'trace' => $this->trace,
            'type' => $this->type,
            'source' => $this->source,
            'occuredAt' => $this->occuredAt->format(DateTimeInterface::RFC3339_EXTENDED)
        ];
    }
}