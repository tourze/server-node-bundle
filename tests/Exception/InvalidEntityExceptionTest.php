<?php

declare(strict_types=1);

namespace ServerNodeBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use ServerNodeBundle\Exception\InvalidEntityException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidEntityException::class)]
final class InvalidEntityExceptionTest extends AbstractExceptionTestCase
{
    protected function createException(): \Throwable
    {
        return new InvalidEntityException('Test message');
    }
}
