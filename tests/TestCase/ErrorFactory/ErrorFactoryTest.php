<?php

declare(strict_types=1);

namespace Xchert\Exception\Test\TestCase\ErrorFactory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xchert\Exception\Error;
use Xchert\Exception\ErrorFactory\ErrorFactory;
use Xchert\Exception\Test\Dummy\FixedErrorMapper;

#[CoversClass(ErrorFactory::class)]
class ErrorFactoryTest extends TestCase
{
    public function testCreateType(): void
    {
        $factory = new ErrorFactory('https://example.com/problems/');

        $this->assertSame('https://example.com/problems/FOO', $factory->createType('FOO'));
        $this->assertSame(Error::DEFAULT_TYPE, new ErrorFactory()->createType('FOO'));
    }

    public function testMapperWithHighestPriorityIsUsed(): void
    {
        $factory = new ErrorFactory();
        $factory->addMapper(new FixedErrorMapper('low'), 'low', 0);
        $factory->addMapper(new FixedErrorMapper('high'), 'high', 100);

        $this->assertSame('high', $factory->createError(new \RuntimeException(), false)->getCode());
    }

    public function testExceptionWithoutMapperBecomesUnknownError(): void
    {
        $problem = new ErrorFactory()->createError(new \RuntimeException('boom'), false);

        $this->assertSame(Error::ERROR_CODE_UNKNOWN, $problem->getCode());
        $this->assertSame(500, $problem->getStatus());
        $this->assertSame('boom', $problem->getDetail());
    }

    public function testCreateResponse(): void
    {
        $factory = new ErrorFactory();
        $factory->addMapper(new FixedErrorMapper('mapper', headers: ['X-Foo' => 'bar']), 'mapper', 0);

        $response = $factory->createResponse(new \RuntimeException('boom'), false);
        $data = \json_decode((string)$response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        $this->assertSame(418, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));
        $this->assertSame('bar', $response->headers->get('X-Foo'));
        $this->assertSame('mapper', $data['code']);
        $this->assertSame('boom', $data['detail']);
    }
}
