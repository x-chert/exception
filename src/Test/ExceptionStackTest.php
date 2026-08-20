<?php

declare(strict_types=1);

namespace Xchert\Exception\Test;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Xchert\Exception\ExceptionStack;
use Xchert\Exception\StackException;

class ExceptionStackTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $stack = new ExceptionStack();

        $stack->setMessage('Custom Message');
        $this->assertEquals('Custom Message', $stack->getMessage());

        $stack->setSource('user_service');
        $this->assertEquals('user_service', $stack->getSource());

        $stack->setParameters(['id' => 42]);
        $this->assertEquals(['id' => 42], $stack->getParameters());

        $stack->setStatusCode(Response::HTTP_BAD_REQUEST);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $stack->getStatusCode());
    }

    public function testAddAndGetErrors(): void
    {
        $stack = new ExceptionStack();
        $exception1 = new \Exception('Error 1');
        $exception2 = new \InvalidArgumentException('Error 2');

        $stack->add($exception1, $exception2);

        $this->assertCount(2, $stack->getErrors());
        $this->assertSame([$exception1, $exception2], $stack->getErrors());
    }

    public function testCreateMessageFromErrorsWhenMessageIsNull(): void
    {
        $stack = new ExceptionStack();
        $stack->add(
            new \Exception('First error'),
            new \Exception('Second error')
        );

        $expectedMessage = " • First error\n • Second error\n";
        $this->assertEquals($expectedMessage, $stack->getMessage());
    }

    public function testThrowDoesNothingWhenNoErrorsExist(): void
    {
        $stack = new ExceptionStack();

        $stack->throw();
        $this->assertTrue(true);
    }

    public function testThrowRaisesStackExceptionWhenErrorsExist(): void
    {
        $error1 = new \RuntimeException('Database down');
        $error2 = new \LogicException('Invalid state');

        $stack = new ExceptionStack(
            message: 'Custom Stack Error',
            source: 'payment_module',
            parameters: ['amount' => 100],
            statusCode: Response::HTTP_SERVICE_UNAVAILABLE
        );
        $stack->add($error1, $error2);

        $this->expectException(StackException::class);

        $stack->throw();
    }
}