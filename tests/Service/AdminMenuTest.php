<?php

namespace ServerNodeBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use ServerNodeBundle\Entity\Node;
use ServerNodeBundle\Service\AdminMenu;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;

class AdminMenuTest extends TestCase
{
    private AdminMenu $adminMenu;
    private LinkGeneratorInterface $linkGenerator;
    
    protected function setUp(): void
    {
        // 创建LinkGenerator模拟对象
        $this->linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        
        // 设置LinkGenerator模拟对象的预期行为
        $this->linkGenerator
            ->method('getCurdListPage')
            ->with(Node::class)
            ->willReturn('/admin/node/list');
        
        // 创建要测试的AdminMenu实例
        $this->adminMenu = new AdminMenu($this->linkGenerator);
    }
    
    public function testInvoke(): void
    {
        // 仅测试实例
        $this->assertInstanceOf(AdminMenu::class, $this->adminMenu);
    }
} 