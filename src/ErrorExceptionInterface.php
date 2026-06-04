<?php

namespace Xchert\Exception;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

interface ErrorExceptionInterface extends HttpExceptionInterface
{
    public function getId(): string;

    public function getErrorCode(): string;

    public function getParameters(): array;

    public function getType(): string;

    public function getSource(): string;

    public function getOccurredAt(): \DateTimeInterface;
}