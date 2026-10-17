<?php

declare(strict_types=1);

namespace Xchert\Exception\Test\TestCase;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Xchert\Exception\ErrorException;
use Xchert\Exception\Test\Dummy\TestErrorException;

#[CoversClass(ErrorException::class)]
class ErrorExceptionTest extends TestCase
{
    public function testConstruction(): void
    {
        $exception = new TestErrorException('message', 'title', ['foo' => 'bar'], ['X-Foo' => 'bar']);

        $this->assertTrue(Uuid::isValid($exception->getId()));
        $this->assertSame('title', $exception->getTitle());
        $this->assertSame(['foo' => 'bar'], $exception->getParameters());
        $this->assertSame(['X-Foo' => 'bar'], $exception->getHeaders());
        $this->assertSame(422, $exception->getStatusCode());
    }

    public function testPlaceholdersAreReplacedWithParameters(): void
    {
        $exception = new TestErrorException('User {{ user_id }} has {{count}} items.', 'title', ['user_id' => 'a1', 'count' => 3]);

        $this->assertSame('User a1 has 3 items.', $exception->getMessage());
    }
}
