<?php

declare(strict_types=1);

namespace Xchert\Exception\Test\TestCase\ErrorFactory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Xchert\Exception\Error;
use Xchert\Exception\ErrorFactory\ErrorExceptionMapper;
use Xchert\Exception\ErrorFactory\ErrorFactory;
use Xchert\Exception\ErrorFactory\HttpExceptionMapper;
use Xchert\Exception\ErrorFactory\StackExceptionMapper;
use Xchert\Exception\StackException;

#[CoversClass(StackExceptionMapper::class)]
class StackExceptionMapperTest extends TestCase
{
    public function testStackedErrorsAreWrappedInStackError(): void
    {
        $factory = new ErrorFactory();
        $factory->addMapper(new HttpExceptionMapper(), 'http_exception', 0);
        $factory->addMapper(new ErrorExceptionMapper(), 'error_exception', 1000);
        $factory->addMapper(new StackExceptionMapper(), 'stack_exception', 1100);

        $exception = new StackException('message', 'title', 422, 'STACK', null, new NotFoundHttpException('a'), new \RuntimeException('b'));

        $problem = $factory->getProblemDetails($exception, false);

        $this->assertSame('STACK', $problem->getCode());
        $this->assertSame(422, $problem->getStatus());
        $this->assertSame(['NOT_FOUND', Error::ERROR_CODE_UNKNOWN], \array_map(static fn(Error $e): string => $e->getCode(), $problem->getErrors()));
    }
}
