<?php

declare(strict_types=1);

namespace Xchert\Exception\Test\TestCase\ErrorFactory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xchert\Exception\ErrorFactory\ErrorExceptionMapper;
use Xchert\Exception\ErrorFactory\ErrorFactory;
use Xchert\Exception\Test\Dummy\TestErrorException;

#[CoversClass(ErrorExceptionMapper::class)]
class ErrorExceptionMapperTest extends TestCase
{
    public function testErrorExceptionIsMapped(): void
    {
        $factory = new ErrorFactory('https://example.com/problems/');
        $factory->addMapper(new ErrorExceptionMapper(), 'error_exception', 0);
        $exception = new TestErrorException('Hello {{ name }}', 'My title', ['name' => 'Alice']);

        $problem = $factory->createError($exception, false);

        $this->assertSame('urn:uuid:'.$exception->getId(), $problem->getInstance());
        $this->assertSame('https://example.com/problems/TEST_ERROR', $problem->getType());
        $this->assertSame('My title', $problem->getTitle());
        $this->assertSame('Hello Alice', $problem->getDetail());
        $this->assertSame(422, $problem->getStatus());
        $this->assertSame(TestErrorException::ERROR_CODE, $problem->getCode());
        $this->assertSame($exception->getOccurredAt(), $problem->getOccurredAt());
        $this->assertSame(['parameters' => ['name' => 'Alice']], $problem->getAdditional());
    }
}
