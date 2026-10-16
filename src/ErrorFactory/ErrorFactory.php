<?php

namespace Xchert\Exception\ErrorFactory;

use Symfony\Component\HttpFoundation\Response;
use Xchert\Exception\Error;
use Xchert\Exception\Util;

class ErrorFactory
{
    private array $mappers = [];

    /**
     * @return Error[]
     */
    public function getErrors(\Throwable $exception, bool $debug): array
    {
        $mapper = $this->getMapper($exception);

        if ($mapper !== null) {
            return $mapper->getErrors($exception, $debug, $this);
        }

        return [
            new Error(
                Util::createId(),
                Error::ERROR_CODE_UNKNOWN,
                $exception->getMessage(),
                [],
                Response::HTTP_INTERNAL_SERVER_ERROR,
                $debug ? $exception->getTrace() : null,
                (new \ReflectionClass($exception))->getShortName()
            )
        ];
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