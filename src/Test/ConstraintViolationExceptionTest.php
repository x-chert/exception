<?php

declare(strict_types=1);

namespace Xchert\Exception\Test;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Xchert\Exception\Validation\ConstraintViolationException;

class ConstraintViolationExceptionTest extends TestCase
{
    public function testExceptionInitializationAndFormattingWithRealViolations(): void
    {
        $violation1 = new ConstraintViolation(
            message: 'Should not be blank',
            messageTemplate: 'Should not be blank',
            parameters: [],
            root: null,
            propertyPath: 'username',
            invalidValue: ''
        );

        $violation2 = new ConstraintViolation(
            message: 'Invalid email address',
            messageTemplate: 'Invalid email address',
            parameters: [],
            root: null,
            propertyPath: 'email',
            invalidValue: 'invalid-email'
        );

        $violations = new ConstraintViolationList([$violation1, $violation2]);
        $inputData = ['username' => '', 'email' => 'invalid-email'];
        $source = 'user_registration';

        $exception = new ConstraintViolationException($violations, $inputData, $source);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
        $this->assertEquals(ConstraintViolationException::ERROR_CODE, $exception->getErrorCode());

        $this->assertSame($violations, $exception->getViolations());
        $this->assertEquals($inputData, $exception->getData());
        $this->assertEquals($source, $exception->getSource());

        $this->assertEquals('ConstraintViolationException', $exception->getType());
        $this->assertNotEmpty($exception->getId());
        $this->assertInstanceOf(\DateTimeInterface::class, $exception->getOccurredAt());

        $expectedMessage = "Constraint validation failed. Caught 2 errors:\n • username: Should not be blank\n • email: Invalid email address\n";
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }
}