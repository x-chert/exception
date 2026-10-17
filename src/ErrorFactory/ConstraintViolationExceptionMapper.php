<?php

namespace Xchert\Exception\ErrorFactory;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Xchert\Exception\Attribute\AsErrorMapper;
use Xchert\Exception\Error;
use Xchert\Exception\Util;
use Xchert\Exception\Validation\ConstraintViolationException;

#[AsErrorMapper(id: 'constraint_violation_exception', priority: 1100)]
class ConstraintViolationExceptionMapper extends ErrorExceptionMapper
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof ConstraintViolationException;
    }

    /**
     * @param ConstraintViolationException $exception
     */
    public function getErrors(\Throwable $exception, bool $debug, ErrorFactory $factory): array
    {
        \assert($exception instanceof ConstraintViolationException);

        $errors = [];

        foreach ($exception->getViolations() as $violation) {
            $code = $this->getErrorCodeFromViolation($violation);

            $errors[] = new Error(
                instance: Util::createInstance(),
                type: $factory->createType($code),
                title: $code,
                detail: (string)$violation->getMessage(),
                status: Response::HTTP_BAD_REQUEST,
                code: $code,
                occurredAt: $exception->getOccurredAt(),
                trace: $debug ? $exception->getTrace() : null,
                additional: [
                    'parameters' => $violation->getParameters(),
                    'pointer' => $this->createPointer($violation->getPropertyPath()),
                ]
            );
        }

        return $errors;
    }

    /**
     * Converts a property path like "address.street" or "items[0].name" into a JSON pointer (RFC 6901).
     */
    protected function createPointer(string $propertyPath): string
    {
        $segments = \preg_split('/[.\/]|\[|\]/', $propertyPath, -1, \PREG_SPLIT_NO_EMPTY);

        // An empty property path refers to the whole document, e.g. for class constraints.
        if (empty($segments)) {
            return '';
        }

        return '/'.\implode(
                '/',
                \array_map(
                    static fn(string $segment): string => \str_replace('~', '~0', $segment),
                    $segments
                )
            );
    }

    protected function getErrorCodeFromViolation(ConstraintViolationInterface $violation): string
    {
        $code = $violation->getCode();
        $constraint = $violation->getConstraint();

        if (empty($code) || $constraint === null) {
            return ConstraintViolationException::ERROR_CODE;
        }

        try {
            return $constraint::getErrorName($code);
        } catch (InvalidArgumentException) {
            return ConstraintViolationException::ERROR_CODE;
        }
    }
}