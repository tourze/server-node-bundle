<?php

declare(strict_types=1);

namespace ServerNodeBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use ServerNodeBundle\DependencyInjection\ServerNodeExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(ServerNodeExtension::class)]
final class ServerNodeExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
}
