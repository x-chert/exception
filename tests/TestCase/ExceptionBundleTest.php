<?php

declare(strict_types=1);

namespace Xchert\Exception\Test\TestCase;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\ConstraintViolationList;
use Xchert\Exception\ErrorFactory\ConstraintViolationExceptionMapper;
use Xchert\Exception\ErrorFactory\ErrorExceptionMapper;
use Xchert\Exception\ErrorFactory\ErrorFactory;
use Xchert\Exception\ErrorFactory\HttpExceptionMapper;
use Xchert\Exception\ErrorFactory\StackExceptionMapper;
use Xchert\Exception\StackException;
use Xchert\Exception\Test\Dummy\PublicDummyService;
use Xchert\Exception\Test\Dummy\TestErrorException;
use Xchert\Exception\Validation\ConstraintViolationException;

class ExceptionBundleTest extends KernelTestCase
{
    public function setup(): void
    {
        static::bootKernel();
    }

    public function testDefaultMappersAreRegistered(): void
    {
        $factory = $this->getFactory();

        $this->assertInstanceOf(StackExceptionMapper::class, $factory->getMapper(new StackException('', '', 400, 'STACK')));
        $this->assertInstanceOf(
            ConstraintViolationExceptionMapper::class,
            $factory->getMapper(new ConstraintViolationException(new ConstraintViolationList(), []))
        );
        $this->assertInstanceOf(ErrorExceptionMapper::class, $factory->getMapper(new TestErrorException('', '')));
        $this->assertInstanceOf(HttpExceptionMapper::class, $factory->getMapper(new NotFoundHttpException()));
    }

    private function getFactory(): ErrorFactory
    {
        $dummyService = self::getContainer()->get(PublicDummyService::class);
        \assert($dummyService instanceof PublicDummyService);

        return $dummyService->getErrorFactory();
    }
}
