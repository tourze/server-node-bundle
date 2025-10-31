<?php

declare(strict_types=1);

namespace ServerNodeBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ServerNodeBundle\ServerNodeBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(ServerNodeBundle::class)]
#[RunTestsInSeparateProcesses]
final class ServerNodeBundleTest extends AbstractBundleTestCase
{
}
