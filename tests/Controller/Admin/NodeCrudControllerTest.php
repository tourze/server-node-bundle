<?php

namespace ServerNodeBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\TestCase;
use ServerNodeBundle\Controller\Admin\NodeCrudController;
use ServerNodeBundle\Entity\Node;

class NodeCrudControllerTest extends TestCase
{
    private NodeCrudController $controller;
    
    protected function setUp(): void
    {
        $this->controller = new NodeCrudController();
    }

    public function testControllerExtendsAbstractCrudController(): void
    {
        // 验证Controller继承自正确的基类
        $this->assertInstanceOf(AbstractCrudController::class, $this->controller);
        $this->assertInstanceOf(CrudControllerInterface::class, $this->controller);
    }

    public function testGetEntityFqcn(): void
    {
        // 测试返回正确的实体类名
        $this->assertEquals(Node::class, NodeCrudController::getEntityFqcn());
    }

    public function testConfigureFields(): void
    {
        // 测试不同页面的字段配置
        $indexFields = $this->controller->configureFields(Crud::PAGE_INDEX);
        $this->assertNotNull($indexFields);
        
        $newFields = $this->controller->configureFields(Crud::PAGE_NEW);
        $this->assertNotNull($newFields);
        
        $editFields = $this->controller->configureFields(Crud::PAGE_EDIT);
        $this->assertNotNull($editFields);
        
        $detailFields = $this->controller->configureFields(Crud::PAGE_DETAIL);
        $this->assertNotNull($detailFields);
    }

    public function testConfigureFieldsReturnsCorrectFieldsForIndexPage(): void
    {
        // 测试列表页面字段配置
        $fields = iterator_to_array($this->controller->configureFields(Crud::PAGE_INDEX));
        $this->assertNotEmpty($fields);
        
        // 验证字段数量符合预期（列表页面应该有特定数量的字段）
        $this->assertGreaterThan(5, count($fields));
    }

    public function testConfigureFieldsReturnsCorrectFieldsForDetailPage(): void
    {
        // 测试详情页面字段配置
        $fields = iterator_to_array($this->controller->configureFields(Crud::PAGE_DETAIL));
        $this->assertNotEmpty($fields);
        
        // 详情页面应该比列表页面有更多字段
        $indexFields = iterator_to_array($this->controller->configureFields(Crud::PAGE_INDEX));
        $this->assertGreaterThan(count($indexFields), count($fields));
    }

    public function testConfigureFieldsReturnsCorrectFieldsForNewPage(): void
    {
        // 测试新建页面字段配置
        $fields = iterator_to_array($this->controller->configureFields(Crud::PAGE_NEW));
        $this->assertNotEmpty($fields);
    }

    public function testConfigureFieldsReturnsCorrectFieldsForEditPage(): void
    {
        // 测试编辑页面字段配置
        $fields = iterator_to_array($this->controller->configureFields(Crud::PAGE_EDIT));
        $this->assertNotEmpty($fields);
    }

    public function testControllerHasCorrectRouteAnnotation(): void
    {
        // 通过反射检查testSsh方法的路由注解
        $reflection = new \ReflectionMethod(NodeCrudController::class, 'testSsh');
        $attributes = $reflection->getAttributes();
        
        $hasAdminAction = false;
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'EasyCorp\\Bundle\\EasyAdminBundle\\Attribute\\AdminAction') {
                $hasAdminAction = true;
                $arguments = $attribute->getArguments();
                $this->assertEquals('{entityId}/test-ssh', $arguments[0]);
                $this->assertEquals('test_ssh', $arguments[1]);
                break;
            }
        }
        
        $this->assertTrue($hasAdminAction, 'testSsh method should have AdminAction attribute');
    }

    public function testControllerHasTestSshMethod(): void
    {
        // 验证方法是public的
        $reflection = new \ReflectionMethod(NodeCrudController::class, 'testSsh');
        $this->assertTrue($reflection->isPublic());
    }

    public function testControllerHasConfigureMethods(): void
    {
        // 验证Controller继承了必要的基类
        $this->assertInstanceOf(AbstractCrudController::class, $this->controller);
    }
} 