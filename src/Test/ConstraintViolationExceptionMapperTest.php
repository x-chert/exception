<?php

declare(strict_types=1);

namespace Xchert\Exception\Test;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Xchert\Exception\Error;
use Xchert\Exception\ErrorFactory\ConstraintViolationExceptionMapper;
use Xchert\Exception\ErrorFactory\ErrorFactory;
use Xchert\Exception\Validation\ConstraintViolationException;

class ConstraintViolationExceptionMapperTest extends TestCase
{
    private ConstraintViolationExceptionMapper $mapper;

    private ErrorFactory $factory;

    #[AllowMockObjectsWithoutExpectations]
    public function testSupportsReturnsTrueForConstraintViolationException(): void
    {
        $exception = $this->createMock(ConstraintViolationException::class);
        $this->assertTrue($this->mapper->supports($exception));
    }

    public function testSupportsReturnsFalseForGenericThrowable(): void
    {
        $exception = new \Exception('Generic error');
        $this->assertFalse($this->mapper->supports($exception));
    }

    public function testGetErrorsConvertsViolationsToErrors(): void
    {
        $constraint = new NotBlank();
        $violation = new ConstraintViolation(
            message: 'This value should not be blank.',
            messageTemplate: 'This value should not be blank.',
            parameters: ['{{ value }}' => '""'],
            root: null,
            propertyPath: 'username',
            invalidValue: '',
            code: NotBlank::IS_BLANK_ERROR,
            constraint: $constraint
        );

        $violations = new ConstraintViolationList([$violation]);
        $exception = new ConstraintViolationException($violations, ['username' => ''], 'user_service');

        /** @var Error[] $errors */
        $errors = $this->mapper->getErrors($exception, false, $this->factory);

        $this->assertCount(1, $errors);
        $error = $errors[0];

        $this->assertInstanceOf(Error::class, $error);
        $this->assertNotEmpty($error->getId());
        $this->assertEquals('IS_BLANK_ERROR', $error->getErrorCode());
        $this->assertEquals('This value should not be blank.', $error->getMessage());
        $this->assertEquals([
            '{{ value }}' => '""',
            'propertyPath' => '/username',
        ], $error->getParameters());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $error->getStatusCode());
        $this->assertNull($error->getTrace());
        $this->assertEquals('ConstraintViolation', $error->getType());
        $this->assertEquals('user_service', $error->getSource());
        $this->assertSame($exception->getOccurredAt(), $error->getOccuredAt());
    }

    public function testGetErrorsFallbackToDefaultErrorCodeWhenConstraintOrCodeIsMissing(): void
    {
        $violationWithoutConstraint = new ConstraintViolation(
            message: 'Custom error message',
            messageTemplate: 'Custom error message',
            parameters: [],
            root: null,
            propertyPath: '/email',
            invalidValue: 'invalid'
        );

        $violations = new ConstraintViolationList([$violationWithoutConstraint]);
        $exception = new ConstraintViolationException($violations, []);

        /** @var Error[] $errors */
        $errors = $this->mapper->getErrors($exception, false, $this->factory);

        $this->assertCount(1, $errors);
        $this->assertEquals(ConstraintViolationException::ERROR_CODE, $errors[0]->getErrorCode());
        $this->assertEquals('/email', $errors[0]->getParameters()['propertyPath']);
    }

    public function testGetErrorsWithDebugEnabledIncludesTrace(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Error', 'Error', [], null, 'field', 'val')
        ]);
        $exception = new ConstraintViolationException($violations, []);

        /** @var Error[] $errors */
        $errors = $this->mapper->getErrors($exception, true, $this->factory);

        $this->assertCount(1, $errors);
        $this->assertIsArray($errors[0]->getTrace());
    }

    protected function setUp(): void
    {
        $this->mapper = new ConstraintViolationExceptionMapper();
        $this->factory = new ErrorFactory();
        $this->factory->addMapper($this->mapper, ConstraintViolationException::class, 0);
    }
}