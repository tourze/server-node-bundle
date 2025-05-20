<?php

namespace ServerNodeBundle\Tests;

use PHPUnit\Framework\TestCase;
use ServerNodeBundle\ServerNodeBundle;

class BasicTest extends TestCase
{
    public function testBundleClass(): void
    {
        $bundle = new ServerNodeBundle();
        
        // 测试获取捆绑包依赖项
        $dependencies = $bundle::getBundleDependencies();
        
        // 验证依赖项是否为数组
        $this->assertIsArray($dependencies);
        
        // 验证依赖项是否包含预期的元素
        $this->assertArrayHasKey(\Tourze\TempFileBundle\TempFileBundle::class, $dependencies);
        $this->assertArrayHasKey(\Tourze\DoctrineRandomBundle\DoctrineRandomBundle::class, $dependencies);
        $this->assertArrayHasKey(\Tourze\DoctrineTrackBundle\DoctrineTrackBundle::class, $dependencies);
        $this->assertArrayHasKey(\Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle::class, $dependencies);
        
        // 验证每个依赖项是否都包含'all' => true
        foreach ($dependencies as $dependency) {
            $this->assertArrayHasKey('all', $dependency);
            $this->assertTrue($dependency['all']);
        }
    }
} 