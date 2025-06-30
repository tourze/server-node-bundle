<?php

namespace ServerNodeBundle\Tests;

use PHPUnit\Framework\TestCase;
use ServerNodeBundle\ServerNodeBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\DoctrineSnowflakeBundle\DoctrineSnowflakeBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\DoctrineTrackBundle\DoctrineTrackBundle;
use Tourze\DoctrineUserBundle\DoctrineUserBundle;
use Tourze\EasyAdminMenuBundle\EasyAdminMenuBundle;

class ServerNodeBundleTest extends TestCase
{
    private ServerNodeBundle $bundle;

    protected function setUp(): void
    {
        $this->bundle = new ServerNodeBundle();
    }

    public function testIsBundle(): void
    {
        $this->assertInstanceOf(Bundle::class, $this->bundle);
    }

    public function testImplementsBundleDependencyInterface(): void
    {
        $this->assertInstanceOf(BundleDependencyInterface::class, $this->bundle);
    }

    public function testGetBundleDependencies(): void
    {
        $dependencies = ServerNodeBundle::getBundleDependencies();
        
        $this->assertCount(6, $dependencies);
        
        $expectedDependencies = [
            DoctrineTrackBundle::class => ['all' => true],
            DoctrineIndexedBundle::class => ['all' => true],
            DoctrineSnowflakeBundle::class => ['all' => true],
            DoctrineTimestampBundle::class => ['all' => true],
            DoctrineUserBundle::class => ['all' => true],
            EasyAdminMenuBundle::class => ['all' => true],
        ];
        
        $this->assertEquals($expectedDependencies, $dependencies);
    }

    public function testEachDependencyHasAllEnvironment(): void
    {
        $dependencies = ServerNodeBundle::getBundleDependencies();
        
        foreach ($dependencies as $bundleClass => $config) {
            $this->assertIsArray($config);
            $this->assertArrayHasKey('all', $config);
            $this->assertTrue($config['all']);
        }
    }
}