<?php

declare(strict_types=1);

namespace Xchert\Exception\Test;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Xchert\Exception\Error;
use Xchert\Exception\ErrorFactory\ErrorFactory;
use Xchert\Exception\ErrorFactory\HttpExceptionMapper;
use Xchert\Exception\ErrorFactory\StackExceptionMapper;
use Xchert\Exception\StackException;

class StackExceptionMapperTest extends TestCase
{
    private StackExceptionMapper $mapper;

    private ErrorFactory $factory;

    #[AllowMockObjectsWithoutExpectations]
    public function testSupportsReturnsTrueForStackException(): void
    {
        $exception = $this->createMock(StackException::class);
        $this->assertTrue($this->mapper->supports($exception));
    }

    public function testSupportsReturnsFalseForGenericThrowable(): void
    {
        $exception = new \Exception('Generic error');
        $this->assertFalse($this->mapper->supports($exception));
    }

    public function testGetErrorsDelegatesInnerErrorsToFactory(): void
    {
        $innerException1 = new BadRequestHttpException('First inner error');
        $innerException2 = new BadRequestHttpException('Second inner error');

        $stackException = new StackException(
            'Stack container exception',
            [],
            Response::HTTP_BAD_REQUEST,
            'stack_source',
            null,
            ...[$innerException1, $innerException2]
        );

        /** @var Error[] $errors */
        $errors = $this->mapper->getErrors($stackException, false, $this->factory);

        $this->assertCount(2, $errors);
        $this->assertInstanceOf(Error::class, $errors[0]);
        $this->assertInstanceOf(Error::class, $errors[1]);
        $this->assertEquals('First inner error', $errors[0]->getMessage());
        $this->assertEquals('Second inner error', $errors[1]->getMessage());
    }

    public function testGetErrorsDelegatesPreviousAndInnerErrorsToFactory(): void
    {
        $previousException = new BadRequestHttpException('Previous error');
        $innerException = new BadRequestHttpException('Inner error');

        $stackException = new StackException(
            'Stack container exception',
            [],
            Response::HTTP_BAD_REQUEST,
            'stack_source',
            $previousException,
            ...[$innerException]
        );

        /** @var Error[] $errors */
        $errors = $this->mapper->getErrors($stackException, false, $this->factory);

        $this->assertCount(2, $errors);
        $this->assertEquals('Previous error', $errors[0]->getMessage());
        $this->assertEquals('Inner error', $errors[1]->getMessage());
    }

    protected function setUp(): void
    {
        $this->mapper = new StackExceptionMapper();
        $this->factory = new ErrorFactory();

        $this->factory->addMapper(new HttpExceptionMapper(), HttpException::class, 0);
        $this->factory->addMapper($this->mapper, StackException::class, 1);
    }
}