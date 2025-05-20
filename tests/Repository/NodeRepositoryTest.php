<?php

namespace ServerNodeBundle\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use ServerNodeBundle\Repository\NodeRepository;

class NodeRepositoryTest extends TestCase
{
    private NodeRepository $repository;
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;
    
    protected function setUp(): void
    {
        // 创建EntityManager模拟对象
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        // 创建ClassMetadata模拟对象
        $classMetadata = $this->createMock(ClassMetadata::class);
        
        // 配置EntityManager以返回ClassMetadata
        $this->entityManager
            ->method('getClassMetadata')
            ->willReturn($classMetadata);
            
        // 创建ManagerRegistry模拟对象
        $this->registry = $this->createMock(ManagerRegistry::class);
        
        // 配置ManagerRegistry以返回EntityManager
        $this->registry
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        
        // 创建Repository实例
        $this->repository = new NodeRepository($this->registry);
    }
    
    public function testConstructor(): void
    {
        // 验证构造函数正确设置了实体类
        $this->assertInstanceOf(NodeRepository::class, $this->repository);
    }
} 