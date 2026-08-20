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

class HttpExceptionMapperTest extends TestCase
{
    private HttpExceptionMapper $mapper;

    private ErrorFactory $factory;

    #[AllowMockObjectsWithoutExpectations]
    public function testSupportsReturnsTrueForHttpException(): void
    {
        $exception = $this->createMock(HttpException::class);
        $this->assertTrue($this->mapper->supports($exception));
    }

    public function testSupportsReturnsFalseForGenericThrowable(): void
    {
        $exception = new \Exception('Generic error');
        $this->assertFalse($this->mapper->supports($exception));
    }

    public function testGetErrorsWithoutPreviousAndDebugDisabled(): void
    {
        $exception = new BadRequestHttpException(
            message: 'Invalid request payload',
            headers: ['X-Custom-Header' => 'Value']
        );

        /** @var Error[] $errors */
        $errors = $this->mapper->getErrors($exception, false, $this->factory);

        $this->assertCount(1, $errors);
        $error = $errors[0];

        $this->assertInstanceOf(Error::class, $error);
        $this->assertNotEmpty($error->getId());
        $this->assertEquals(Error::ERROR_CODE_UNKNOWN, $error->getErrorCode());
        $this->assertEquals('Invalid request payload', $error->getMessage());
        $this->assertEquals(['X-Custom-Header' => 'Value'], $error->getParameters());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $error->getStatusCode());
        $this->assertNull($error->getTrace());
        $this->assertEquals('BadRequestHttpException', $error->getType());
        $this->assertNull($error->getSource());
        $this->assertInstanceOf(\DateTimeInterface::class, $error->getOccuredAt());
    }

    public function testGetErrorsWithDebugEnabledIncludesTrace(): void
    {
        $exception = new BadRequestHttpException('Invalid request payload');

        /** @var Error[] $errors */
        $errors = $this->mapper->getErrors($exception, true, $this->factory);

        $this->assertCount(1, $errors);
        $this->assertIsArray($errors[0]->getTrace());
    }

    public function testGetErrorsDelegatesPreviousExceptionToFactory(): void
    {
        $exception = new BadRequestHttpException(
            message: 'Invalid request payload',
            previous: new \Exception('Previous error')
        );

        /** @var Error[] $errors */
        $errors = $this->mapper->getErrors($exception, true, $this->factory);

        $this->assertCount(2, $errors);
        $this->assertInstanceOf(Error::class, $errors[0]);
        $this->assertInstanceOf(Error::class, $errors[1]);
    }

    protected function setUp(): void
    {
        $this->mapper = new HttpExceptionMapper();
        $this->factory = new ErrorFactory();
        $this->factory->addMapper($this->mapper, HttpException::class, 0);
    }
}