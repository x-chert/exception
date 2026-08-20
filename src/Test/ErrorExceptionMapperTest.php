<?php

declare(strict_types=1);

namespace Xchert\Exception\Test;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Xchert\Exception\Error;
use Xchert\Exception\ErrorExceptionInterface;
use Xchert\Exception\ErrorFactory\ErrorExceptionMapper;
use Xchert\Exception\ErrorFactory\ErrorFactory;
use Xchert\Exception\Test\Dummy\DummyErrorException;

class ErrorExceptionMapperTest extends TestCase
{
    private ErrorExceptionMapper $mapper;

    private ErrorFactory $factory;

    #[AllowMockObjectsWithoutExpectations]
    public function testSupportsReturnsTrueForErrorExceptionInterface(): void
    {
        $exception = $this->createMock(ErrorExceptionInterface::class);
        $this->assertTrue($this->mapper->supports($exception));
    }

    public function testSupportsReturnsFalseForGenericThrowable(): void
    {
        $exception = new \Exception('Generic error');
        $this->assertFalse($this->mapper->supports($exception));
    }

    public function testGetErrorsWithoutPreviousAndDebugDisabled(): void
    {
        $exception = new DummyErrorException(
            message: 'User not found with ID {{ id }}',
            parameters: ['id' => 42],
            source: 'user_repository'
        );

        /** @var Error[] $errors */
        $errors = $this->mapper->getErrors($exception, false, $this->factory);

        $this->assertCount(1, $errors);
        $error = $errors[0];

        $this->assertInstanceOf(Error::class, $error);
        $this->assertEquals($exception->getId(), $error->getId());
        $this->assertEquals('XCHERT_EXCEPTION__TEST_ERROR', $error->getErrorCode());
        $this->assertEquals('User not found with ID 42', $error->getMessage());
        $this->assertEquals(['id' => 42], $error->getParameters());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $error->getStatusCode());
        $this->assertNull($error->getTrace());
        $this->assertEquals('DummyErrorException', $error->getType());
        $this->assertEquals('user_repository', $error->getSource());
        $this->assertSame($exception->getOccurredAt(), $error->getOccuredAt());
    }

    public function testGetErrorsWithDebugEnabledIncludesTrace(): void
    {
        $exception = new DummyErrorException(
            message: 'User not found with ID {{ id }}',
            parameters: ['id' => 42],
            source: 'user_repository'
        );

        /** @var Error[] $errors */
        $errors = $this->mapper->getErrors($exception, true, $this->factory);

        $this->assertCount(1, $errors);
        $this->assertIsArray($errors[0]->getTrace());
    }

    public function testGetErrorsDelegatesPreviousExceptionToFactory(): void
    {
        $exception = new DummyErrorException(
            message: 'User not found with ID {{ id }}',
            parameters: ['id' => 42],
            source: 'user_repository',
            previous: new \Exception('Previous error'),
        );

        /** @var Error[] $errors */
        $errors = $this->mapper->getErrors($exception, true, $this->factory);

        $this->assertCount(2, $errors);
        $this->assertInstanceOf(Error::class, $errors[0]);
        $this->assertInstanceOf(Error::class, $errors[1]);
    }

    protected function setUp(): void
    {
        $this->mapper = new ErrorExceptionMapper();
        $this->factory = new ErrorFactory();
        $this->factory->addMapper($this->mapper, ErrorExceptionInterface::class, 0);
    }
}