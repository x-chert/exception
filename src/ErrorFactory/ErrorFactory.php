<?php

namespace Xchert\Exception\ErrorFactory;

use Symfony\Component\HttpFoundation\Response;
use Xchert\Exception\Error;

class ErrorFactory
{
    private array $mappers = [];

    /**
     * @return Error[]
     */
    public function getErrors(\Throwable $exception, bool $debug): array
    {
        $mapper = $this->getMapper($exception);

        if($mapper !== null) {
            return $mapper->getErrors($exception, $debug, $this);
        }

        return [new Error(
            \bin2hex(\random_bytes(16)),
            Error::ERROR_CODE_UNKNOWN,
            $exception->getMessage(),
            [],
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $debug ? $exception->getTrace() : null,
            (new \ReflectionClass($exception))->getShortName()
        )];
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