<?php

namespace Xchert\Exception\Validation;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Xchert\Exception\ErrorException;

class ConstraintViolationException extends ErrorException
{
    public const string ERROR_CODE = 'XCHERT_EXCEPTION__CONSTRAINT_VIOLATION';

    public function __construct(
        protected readonly ConstraintViolationListInterface $violations,
        protected readonly array $data,
        ?string $source = null
    ) {
        parent::__construct(
            "Constraint validation failed. Caught {{ errorCount }} errors:\n{{ violationsMessage }}",
            [
                'errorCount' => $this->violations->count(),
                'violationsMessage' => $this->createViolationsMessage()
            ],
            $source
        );
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }

    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    protected function createViolationsMessage(): string
    {
        $message = '';

        foreach ($this->violations as $violation) {
            $message .= \sprintf(" • %s: %s\n", $violation->getPropertyPath(), $violation->getMessage());
        }

        return $message;
    }
}
