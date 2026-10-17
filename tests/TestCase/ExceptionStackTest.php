<?php

declare(strict_types=1);

namespace Xchert\Exception\Test\TestCase;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xchert\Exception\ExceptionStack;
use Xchert\Exception\StackException;

#[CoversClass(ExceptionStack::class)]
#[CoversClass(StackException::class)]
class ExceptionStackTest extends TestCase
{
    public function testThrowDoesNothingWithoutErrors(): void
    {
        $this->expectNotToPerformAssertions();

        new ExceptionStack('STACK')->throw();
    }

    public function testThrowRaisesStackExceptionWithCollectedErrors(): void
    {
        $first = new \RuntimeException('first');
        $second = new \RuntimeException('second');

        $stack = new ExceptionStack('STACK', statusCode: 422);
        $stack->add($first, $second);

        try {
            $stack->throw();
            $this->fail('StackException was not thrown.');
        } catch (StackException $exception) {
            $this->assertSame(" • first\n • second\n", $exception->getMessage());
            $this->assertSame('STACK', $exception->getErrorCode());
            $this->assertSame(422, $exception->getStatusCode());
            $this->assertSame([$first, $second], $exception->getErrors());
        }
    }
}
