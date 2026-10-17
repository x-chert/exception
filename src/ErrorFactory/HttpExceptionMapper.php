<?php

namespace Xchert\Exception\ErrorFactory;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Xchert\Exception\Attribute\AsErrorMapper;
use Xchert\Exception\Error;
use Xchert\Exception\Util;

#[AsErrorMapper(id: 'http_exception', priority: 0)]
class HttpExceptionMapper implements ErrorMapperInterface
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof HttpException;
    }

    /**
     * @param HttpException $exception
     */
    public function getErrors(\Throwable $exception, bool $debug, ErrorFactory $factory): array
    {
        $previousErrors = [];
        $previous = $exception->getPrevious();

        if ($previous !== null) {
            $previousErrors = $factory->createErrors($previous, $debug);
        }

        return [$this->createError($exception, $debug, $previousErrors, $factory)];
    }

    public function getStatusCode(\Throwable $exception): int
    {
        \assert($exception instanceof HttpException);

        return $exception->getStatusCode();
    }

    public function getHeaders(\Throwable $exception): array
    {
        \assert($exception instanceof HttpException);

        return $exception->getHeaders();
    }

    public function createError(\Throwable $exception, bool $debug, array $errors, ErrorFactory $factory): Error
    {
        \assert($exception instanceof HttpException);

        $code = $this->getErrorCode($exception);

        return new Error(
            instance: Util::createInstance(),
            type: $factory->createType($code),
            title: $this->getTitle($exception),
            detail: $exception->getMessage(),
            status: $exception->getStatusCode(),
            code: $code,
            occurredAt: new \DateTime(),
            trace: $debug ? $exception->getTrace() : null,
            errors: $errors
        );
    }

    public function getErrorCode(\Throwable $exception): string
    {
        \assert($exception instanceof HttpException);

        $code = Response::$statusTexts[$exception->getStatusCode()] ?? null;

        if ($code === null) {
            return Error::ERROR_CODE_UNKNOWN;
        }

        return \str_replace(' ', '_', \strtoupper($code));
    }

    public function getTitle(\Throwable $exception): string
    {
        \assert($exception instanceof HttpException);

        return Response::$statusTexts[$exception->getStatusCode()] ?? Error::DEFAULT_TITLE;
    }
}