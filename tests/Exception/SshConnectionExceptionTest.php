<?php

namespace ServerNodeBundle\Tests\Exception;

use PHPUnit\Framework\TestCase;
use ServerNodeBundle\Exception\SshConnectionException;

class SshConnectionExceptionTest extends TestCase
{
    public function testCanBeCreated(): void
    {
        $exception = new SshConnectionException();
        $this->assertInstanceOf(SshConnectionException::class, $exception);
    }

    public function testCanBeCreatedWithMessage(): void
    {
        $message = 'Connection failed';
        $exception = new SshConnectionException($message);
        $this->assertSame($message, $exception->getMessage());
    }

    public function testCanBeCreatedWithMessageAndCode(): void
    {
        $message = 'Connection failed';
        $code = 123;
        $exception = new SshConnectionException($message, $code);
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testCanBeCreatedWithMessageCodeAndPrevious(): void
    {
        $message = 'Connection failed';
        $code = 123;
        $previous = new \Exception('Previous exception');
        $exception = new SshConnectionException($message, $code, $previous);
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExtendsRuntimeException(): void
    {
        $exception = new SshConnectionException();
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}