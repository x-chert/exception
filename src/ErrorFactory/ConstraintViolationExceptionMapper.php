<?php

namespace Xchert\Exception\ErrorFactory;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Xchert\Exception\Error;
use Xchert\Exception\Util;
use Xchert\Exception\Validation\ConstraintViolationException;

class ConstraintViolationExceptionMapper implements ErrorMapperInterface
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
        $errors = [];

        foreach ($exception->getViolations() as $violation) {
            $errors[] = new Error(
                Util::createId(),
                $this->getErrorCode($violation),
                $violation->getMessage(),
                \array_merge(
                    $violation->getParameters(),
                    [
                        'propertyPath' => '/'.\ltrim($violation->getPropertyPath(), '/'),
                    ]
                ),
                Response::HTTP_BAD_REQUEST,
                $debug ? $exception->getTrace() : null,
                'ConstraintViolation',
                $exception->getSource(),
                $exception->getOccurredAt()
            );
        }

        return $errors;
    }

    public function getStatusCode(\Throwable $exception): int
    {
        \assert($exception instanceof ConstraintViolationException);

        return $exception->getStatusCode();
    }

    public function getHeaders(\Throwable $exception): array
    {
        \assert($exception instanceof ConstraintViolationException);

        return $exception->getHeaders();
    }

    protected function getErrorCode(ConstraintViolationInterface $violation): string
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