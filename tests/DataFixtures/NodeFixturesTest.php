<?php

namespace ServerNodeBundle\Tests\DataFixtures;

use PHPUnit\Framework\TestCase;
use ServerNodeBundle\DataFixtures\NodeFixtures;

class NodeFixturesTest extends TestCase
{
    private NodeFixtures $fixture;
    
    protected function setUp(): void
    {
        $this->fixture = new NodeFixtures();
    }

    public function testFixtureHasCorrectReferenceConstants(): void
    {
        // 验证Fixture类定义了正确的常量
        $this->assertEquals('node-1', NodeFixtures::REFERENCE_NODE_1);
        $this->assertEquals('node-2', NodeFixtures::REFERENCE_NODE_2);
    }

    public function testFixtureExtendsDoctrineFixture(): void
    {
        // 验证Fixture继承自正确的基类
        $this->assertInstanceOf(
            'Doctrine\\Bundle\\FixturesBundle\\Fixture',
            $this->fixture
        );
    }

    public function testFixtureHasLoadMethod(): void
    {
        // 验证Fixture有load方法
        $this->assertTrue(method_exists($this->fixture, 'load'));
        
        // 验证方法是public的
        $reflection = new \ReflectionMethod(NodeFixtures::class, 'load');
        $this->assertTrue($reflection->isPublic());
    }

    public function testLoadMethodSignature(): void
    {
        // 验证load方法的参数签名正确
        $reflection = new \ReflectionMethod(NodeFixtures::class, 'load');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('manager', $parameters[0]->getName());
        $this->assertEquals('Doctrine\\Persistence\\ObjectManager', $parameters[0]->getType()->getName());
    }

    public function testConstantsAreStrings(): void
    {
        // 验证常量是字符串类型
        $this->assertIsString(NodeFixtures::REFERENCE_NODE_1);
        $this->assertIsString(NodeFixtures::REFERENCE_NODE_2);
        
        // 验证常量不为空
        $this->assertNotEmpty(NodeFixtures::REFERENCE_NODE_1);
        $this->assertNotEmpty(NodeFixtures::REFERENCE_NODE_2);
    }

    public function testConstantsAreUnique(): void
    {
        // 验证两个常量值不相同
        $this->assertNotEquals(
            NodeFixtures::REFERENCE_NODE_1,
            NodeFixtures::REFERENCE_NODE_2
        );
    }
} 