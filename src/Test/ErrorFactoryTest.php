<?php

declare(strict_types=1);

namespace Xchert\Exception\Test;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Xchert\Exception\ErrorExceptionInterface;
use Xchert\Exception\ErrorFactory\ErrorExceptionMapper;
use Xchert\Exception\ErrorFactory\ErrorFactory;
use Xchert\Exception\ErrorFactory\ErrorMapperInterface;
use Xchert\Exception\ErrorFactory\HttpExceptionMapper;
use Xchert\Exception\Test\Dummy\DummyErrorException;

class ErrorFactoryTest extends TestCase
{
    private ErrorFactory $factory;

    public function testGetStatusCodeDelegatesToMatchingMapper(): void
    {
        $exception = new NotFoundHttpException('Not found');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->factory->getStatusCode($exception));
    }

    public function testGetStatusCodeFallsBackToInternalServerErrorWithoutMapper(): void
    {
        $exception = new \Exception('Generic error');

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $this->factory->getStatusCode($exception));
    }

    public function testGetHeadersDelegatesToMatchingMapper(): void
    {
        $exception = new NotFoundHttpException(
            message: 'Not found',
            headers: ['X-Custom-Header' => 'Value']
        );

        $this->assertEquals(['X-Custom-Header' => 'Value'], $this->factory->getHeaders($exception));
    }

    public function testGetHeadersFallsBackToEmptyArrayWithoutMapper(): void
    {
        $exception = new \Exception('Generic error');

        $this->assertEquals([], $this->factory->getHeaders($exception));
    }

    public function testGetStatusCodeAndHeadersUseMapperWithHighestPriority(): void
    {
        $mapper = $this->createMock(ErrorMapperInterface::class);
        $mapper->method('supports')->willReturn(true);
        $mapper->expects($this->once())->method('getStatusCode')->willReturn(Response::HTTP_I_AM_A_TEAPOT);
        $mapper->expects($this->once())->method('getHeaders')->willReturn(['X-Priority' => 'high']);

        $this->factory->addMapper($mapper, 'priority_mapper', 100);

        $exception = new DummyErrorException(
            message: 'User not found',
            headers: ['X-Custom-Header' => 'Value']
        );

        $this->assertEquals(Response::HTTP_I_AM_A_TEAPOT, $this->factory->getStatusCode($exception));
        $this->assertEquals(['X-Priority' => 'high'], $this->factory->getHeaders($exception));
    }

    public function testGetStatusCodeAndHeadersUseFirstSupportingMapper(): void
    {
        $exception = new DummyErrorException(
            message: 'User not found',
            headers: ['X-Custom-Header' => 'Value']
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->factory->getStatusCode($exception));
        $this->assertEquals(['X-Custom-Header' => 'Value'], $this->factory->getHeaders($exception));
    }

    protected function setUp(): void
    {
        $this->factory = new ErrorFactory();
        $this->factory->addMapper(new HttpExceptionMapper(), HttpException::class, 0);
        $this->factory->addMapper(new ErrorExceptionMapper(), ErrorExceptionInterface::class, 1);
    }
}
