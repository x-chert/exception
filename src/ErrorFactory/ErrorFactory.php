<?php

namespace Xchert\Exception\ErrorFactory;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Xchert\Exception\Error;
use Xchert\Exception\Util;

class ErrorFactory
{
    private array $mappers = [];

    public function __construct(
        private readonly ?string $problemTypeBaseUri = null
    ) {}

    public function createType(string $errorCode): string
    {
        if ($this->problemTypeBaseUri === null || $errorCode === Error::ERROR_CODE_UNKNOWN) {
            return Error::DEFAULT_TYPE;
        }

        return $this->problemTypeBaseUri.\rawurlencode($errorCode);
    }

    /**
     * @return Error[]
     */
    public function createErrors(\Throwable $exception, bool $debug): array
    {
        $mapper = $this->getMapper($exception);

        if ($mapper !== null) {
            return $mapper->getErrors($exception, $debug, $this);
        }

        return [
            new Error(
                instance: Util::createInstance(),
                type: $this->createType(Error::ERROR_CODE_UNKNOWN),
                title: Error::DEFAULT_TITLE,
                detail: $exception->getMessage(),
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: Error::ERROR_CODE_UNKNOWN,
                occurredAt: new \DateTime(),
                trace: $debug ? $exception->getTrace() : null,
            )
        ];
    }

    public function createError(\Throwable $exception, bool $debug): Error
    {
        $errors = \array_values($this->createErrors($exception, $debug));

        if (\count($errors) === 1) {
            return $errors[0];
        }

        $mapper = $this->getMapper($exception);

        if ($mapper !== null) {
            return $mapper->createError($exception, $debug, $errors, $this);
        }

        return new Error(
            instance: Util::createInstance(),
            type: $this->createType(Error::ERROR_CODE_UNKNOWN),
            title: Error::DEFAULT_TITLE,
            detail: $exception->getMessage(),
            status: Response::HTTP_INTERNAL_SERVER_ERROR,
            code: Error::ERROR_CODE_UNKNOWN,
            occurredAt: new \DateTime(),
            trace: $debug ? $exception->getTrace() : null,
            errors: $errors
        );
    }

    public function createResponse(\Throwable $exception, bool $debug): JsonResponse
    {
        $problem = $this->createError($exception, $debug);
        $headers = $this->getHeaders($exception);
        $headers['Content-Type'] = 'application/problem+json';

        return new JsonResponse($problem, $problem->getStatus(), $headers);
    }

    public function getErrorCode(\Throwable $exception): string
    {
        $mapper = $this->getMapper($exception);

        if ($mapper !== null) {
            return $mapper->getErrorCode($exception);
        }

        return Error::ERROR_CODE_UNKNOWN;
    }

    public function getStatusCode(\Throwable $exception): int
    {
        $mapper = $this->getMapper($exception);

        if ($mapper !== null) {
            return $mapper->getStatusCode($exception);
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    public function getHeaders(\Throwable $exception): array
    {
        $mapper = $this->getMapper($exception);

        if ($mapper !== null) {
            return $mapper->getHeaders($exception);
        }

        return [];
    }

    public function addMapper(ErrorMapperInterface $mapper, string $id, int $priority): void
    {
        $this->mappers[$id] = ['priority' => $priority, 'mapper' => $mapper];

        \uasort(
            $this->mappers,
            function (array $a, array $b): int {
                return $b['priority'] <=> $a['priority'];
            }
        );
    }

    public function getMapper(\Throwable $exception): ?ErrorMapperInterface
    {
        /** @var array $mapper */
        foreach ($this->mappers as $mapper) {
            $mapper = $mapper['mapper'];
            if ($mapper->supports($exception)) {
                return $mapper;
            }
        }

        return null;
    }
}