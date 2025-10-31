<?php

declare(strict_types=1);

namespace ServerNodeBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ServerNodeBundle\ServerNodeBundle;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\DoctrineTrackBundle\DoctrineTrackBundle;

/**
 * @internal
 */
#[CoversClass(ServerNodeBundle::class)]
final class BasicTest extends TestCase
{
    public function testBundleClass(): void
    {
        $bundle = new ServerNodeBundle();

        // 测试获取捆绑包依赖项
        $dependencies = $bundle::getBundleDependencies();

        // 验证依赖项不为空
        $this->assertNotEmpty($dependencies);

        // 验证依赖项是否包含预期的元素
        $this->assertArrayHasKey(DoctrineTrackBundle::class, $dependencies);
        $this->assertArrayHasKey(DoctrineIndexedBundle::class, $dependencies);

        // 验证每个依赖项是否都包含'all' => true
        foreach ($dependencies as $dependency) {
            $this->assertArrayHasKey('all', $dependency);
            $this->assertTrue($dependency['all']);
        }
    }
}
