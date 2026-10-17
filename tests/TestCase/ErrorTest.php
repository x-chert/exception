<?php

declare(strict_types=1);

namespace Xchert\Exception\Test\TestCase;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xchert\Exception\Error;

#[CoversClass(Error::class)]
class ErrorTest extends TestCase
{
    public function testJsonSerializeOmitsEmptyOptionalMembers(): void
    {
        $this->assertSame(
            [
                'instance' => 'urn:uuid:1',
                'type' => Error::DEFAULT_TYPE,
                'title' => 'Bad Request',
                'detail' => 'Something went wrong.',
                'status' => 400,
                'code' => 'BAD_REQUEST',
                'meta' => ['occurredAt' => '2026-10-16T12:00:00.000+00:00'],
            ],
            $this->createError()->jsonSerialize()
        );
    }

    public function testJsonSerializeContainsTraceAdditionalAndErrors(): void
    {
        $child = $this->createError();
        $data = $this->createError([['function' => 'foo']], ['pointer' => '/name'], [$child])->jsonSerialize();

        $this->assertSame([['function' => 'foo']], $data['meta']['trace']);
        $this->assertSame(['pointer' => '/name'], $data['additional']);
        $this->assertSame([$child], $data['errors']);
    }

    private function createError(?array $trace = null, array $additional = [], array $errors = []): Error
    {
        return new Error(
            instance: 'urn:uuid:1',
            type: Error::DEFAULT_TYPE,
            title: 'Bad Request',
            detail: 'Something went wrong.',
            status: 400,
            code: 'BAD_REQUEST',
            occurredAt: new \DateTimeImmutable('2026-10-16 12:00:00', new \DateTimeZone('UTC')),
            trace: $trace,
            additional: $additional,
            errors: $errors,
        );
    }
}
