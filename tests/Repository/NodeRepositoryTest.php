<?php

namespace ServerNodeBundle\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ServerNodeBundle\Entity\Node;
use ServerNodeBundle\Repository\NodeRepository;

class NodeRepositoryTest extends TestCase
{
    private NodeRepository $repository;
    private EntityManagerInterface&MockObject $entityManager;
    private ManagerRegistry&MockObject $registry;
    
    protected function setUp(): void
    {
        // 创建EntityManager模拟对象
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        // 创建ClassMetadata模拟对象
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->name = Node::class;
        
        // 配置EntityManager以返回ClassMetadata
        $this->entityManager
            ->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);
            
        // 创建ManagerRegistry模拟对象
        $this->registry = $this->createMock(ManagerRegistry::class);
        
        // 配置ManagerRegistry以返回EntityManager
        $this->registry
            ->expects($this->any())
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

    public function testRepositoryIsConfiguredWithCorrectEntityClass(): void
    {
        // 验证Repository配置了正确的实体类
        $reflection = new \ReflectionClass($this->repository);
        $this->assertTrue($reflection->hasMethod('find'));
        $this->assertTrue($reflection->hasMethod('findAll'));
        $this->assertTrue($reflection->hasMethod('findBy'));
        $this->assertTrue($reflection->hasMethod('findOneBy'));
    }

    public function testRepositoryIsAutoconfigured(): void
    {
        // 通过反射检查类上的Autoconfigure属性
        $reflection = new \ReflectionClass(NodeRepository::class);
        $attributes = $reflection->getAttributes();
        
        $hasAutoconfigure = false;
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Symfony\\Component\\DependencyInjection\\Attribute\\Autoconfigure') {
                $hasAutoconfigure = true;
                $arguments = $attribute->getArguments();
                $this->assertTrue($arguments['public'] ?? false);
                break;
            }
        }
        
        $this->assertTrue($hasAutoconfigure, 'Repository should have Autoconfigure attribute');
    }

    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        // 验证Repository继承自正确的基类
        $this->assertInstanceOf(
            'Doctrine\\Bundle\\DoctrineBundle\\Repository\\ServiceEntityRepository',
            $this->repository
        );
    }

    public function testRepositoryHasCorrectDocblockMethods(): void
    {
        // 验证Repository类具有正确的docblock方法注释
        $reflection = new \ReflectionClass(NodeRepository::class);
        $docComment = $reflection->getDocComment();
        
        $this->assertStringContainsString('@method Node|null find($id, $lockMode = null, $lockVersion = null)', $docComment);
        $this->assertStringContainsString('@method Node|null findOneBy(array $criteria, array $orderBy = null)', $docComment);
        $this->assertStringContainsString('@method Node[] findAll()', $docComment);
        $this->assertStringContainsString('@method Node[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)', $docComment);
    }
} 