<?php

namespace ServerNodeBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use ServerNodeBundle\Controller\Admin\NodeCrudController;
use ServerNodeBundle\DependencyInjection\ServerNodeExtension;
use ServerNodeBundle\Repository\NodeRepository;
use ServerNodeBundle\Service\AdminMenu;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ServerNodeExtensionTest extends TestCase
{
    private ServerNodeExtension $extension;
    private ContainerBuilder $container;
    
    protected function setUp(): void
    {
        $this->extension = new ServerNodeExtension();
        $this->container = new ContainerBuilder();
    }
    
    public function testLoad(): void
    {
        // 调用load方法
        $this->extension->load([], $this->container);
        
        // 验证服务定义是否已正确加载
        $this->assertTrue($this->container->hasDefinition(NodeRepository::class) || 
                         $this->container->hasAlias(NodeRepository::class));
        
        $this->assertTrue($this->container->hasDefinition(AdminMenu::class) || 
                         $this->container->hasAlias(AdminMenu::class));
        
        $this->assertTrue($this->container->hasDefinition(NodeCrudController::class) || 
                         $this->container->hasAlias(NodeCrudController::class));
    }
} 