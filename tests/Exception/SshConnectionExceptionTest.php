<?php

declare(strict_types=1);

namespace ServerNodeBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use ServerNodeBundle\Exception\SshConnectionException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(SshConnectionException::class)]
final class SshConnectionExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return SshConnectionException::class;
    }

    protected function getParentExceptionClass(): string
    {
        return \RuntimeException::class;
    }
}
