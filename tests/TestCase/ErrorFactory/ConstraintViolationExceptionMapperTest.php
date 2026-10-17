<?php

declare(strict_types=1);

namespace Xchert\Exception\Test\TestCase\ErrorFactory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Xchert\Exception\ErrorFactory\ConstraintViolationExceptionMapper;
use Xchert\Exception\ErrorFactory\ErrorFactory;
use Xchert\Exception\Validation\ConstraintViolationException;

#[CoversClass(ConstraintViolationExceptionMapper::class)]
class ConstraintViolationExceptionMapperTest extends TestCase
{
    public function testViolationsAreMappedToErrors(): void
    {
        $factory = new ErrorFactory();
        $factory->addMapper(new ConstraintViolationExceptionMapper(), 'constraint_violation_exception', 0);

        $exception = new ConstraintViolationException(new ConstraintViolationList([
            new ConstraintViolation('Must not be blank.', null, ['{{ value }}' => '""'], null, 'username', '', null, NotBlank::IS_BLANK_ERROR, new NotBlank()),
            new ConstraintViolation('Invalid email.', null, [], null, 'contact.email', 'x', null, Email::INVALID_FORMAT_ERROR, new Email()),
        ]), []);

        $problem = $factory->createError($exception, false);
        [$blank, $email] = $problem->getErrors();

        $this->assertSame(ConstraintViolationException::ERROR_CODE, $problem->getCode());
        $this->assertSame(400, $problem->getStatus());

        $this->assertSame('IS_BLANK_ERROR', $blank->getCode());
        $this->assertSame('Must not be blank.', $blank->getDetail());
        $this->assertSame(['parameters' => ['{{ value }}' => '""'], 'pointer' => '/username'], $blank->getAdditional());

        $this->assertSame('INVALID_FORMAT_ERROR', $email->getCode());
        $this->assertSame('/contact/email', $email->getAdditional()['pointer']);
    }
}
