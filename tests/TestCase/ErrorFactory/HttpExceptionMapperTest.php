<?php

declare(strict_types=1);

namespace Xchert\Exception\Test\TestCase\ErrorFactory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Xchert\Exception\Error;
use Xchert\Exception\ErrorFactory\ErrorFactory;
use Xchert\Exception\ErrorFactory\HttpExceptionMapper;

#[CoversClass(HttpExceptionMapper::class)]
class HttpExceptionMapperTest extends TestCase
{
    public function testHttpExceptionIsMapped(): void
    {
        $problem = $this->createFactory()->createError(new NotFoundHttpException('Page missing.'), false);

        $this->assertSame('https://example.com/problems/NOT_FOUND', $problem->getType());
        $this->assertSame('Not Found', $problem->getTitle());
        $this->assertSame('Page missing.', $problem->getDetail());
        $this->assertSame(404, $problem->getStatus());
        $this->assertSame('NOT_FOUND', $problem->getCode());
    }

    public function testPreviousExceptionIsNested(): void
    {
        $exception = new NotFoundHttpException('Page missing.', new \RuntimeException('inner'));

        $problem = $this->createFactory()->createError($exception, false);

        $this->assertSame('NOT_FOUND', $problem->getCode());
        $this->assertSame([Error::ERROR_CODE_UNKNOWN], \array_map(static fn(Error $e): string => $e->getCode(), $problem->getErrors()));
    }

    private function createFactory(): ErrorFactory
    {
        $factory = new ErrorFactory('https://example.com/problems/');
        $factory->addMapper(new HttpExceptionMapper(), 'http_exception', 0);

        return $factory;
    }
}
