<?php

namespace Xchert\Exception;

/**
 * Problem details object according to RFC 9457.
 */
class Error implements \JsonSerializable
{
    public const string ERROR_CODE_UNKNOWN = 'UNKNOWN_ERROR';

    public const string DEFAULT_TYPE = 'about:blank';
    public const string DEFAULT_TITLE = 'Unknown error';

    /**
     * @param Error[] $errors
     */
    public function __construct(
        private readonly string $instance,
        private readonly string $type,
        private readonly string $title,
        private readonly string $detail,
        private readonly int $status,

        private readonly string $code,
        private readonly \DateTimeInterface $occurredAt,
        private readonly ?array $trace = [],
        private readonly array $additional = [],
        private readonly array $errors = []
    ) {}

    public function getInstance(): string
    {
        return $this->instance;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDetail(): string
    {
        return $this->detail;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getOccurredAt(): \DateTimeInterface
    {
        return $this->occurredAt;
    }

    public function getTrace(): ?array
    {
        return $this->trace;
    }

    public function getAdditional(): array
    {
        return $this->additional;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function jsonSerialize(): array
    {
        $meta = [
            'occurredAt' => $this->occurredAt->format(\DateTimeInterface::RFC3339_EXTENDED),
        ];

        if (!empty($this->trace)) {
            $meta['trace'] = $this->trace;
        }

        return \array_filter(
            [
                'instance' => $this->instance,
                'type' => $this->type,
                'title' => $this->title,
                'detail' => $this->detail,
                'status' => $this->status,
                'code' => $this->code,
                'meta' => $meta,
                'additional' => empty($this->additional) ? null : $this->additional,
                'errors' => empty($this->errors) ? null : $this->errors,
            ],
            static fn(mixed $value): bool => $value !== null
        );
    }
}
