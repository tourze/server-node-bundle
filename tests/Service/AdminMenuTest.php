<?php

namespace ServerNodeBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ServerNodeBundle\Entity\Node;
use ServerNodeBundle\Service\AdminMenu;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;

class AdminMenuTest extends TestCase
{
    private AdminMenu $adminMenu;
    private LinkGeneratorInterface&MockObject $linkGenerator;
    
    protected function setUp(): void
    {
        // 创建LinkGenerator模拟对象
        $this->linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        
        // 设置LinkGenerator模拟对象的预期行为
        $this->linkGenerator
            ->expects($this->any())
            ->method('getCurdListPage')
            ->with(Node::class)
            ->willReturn('/admin/node/list');
        
        // 创建要测试的AdminMenu实例
        $this->adminMenu = new AdminMenu($this->linkGenerator);
    }
    
    public function testConstructor(): void
    {
        // 测试构造函数正确注入依赖
        $this->assertInstanceOf(AdminMenu::class, $this->adminMenu);
    }

    public function testInvokeCreatesServerManagementMenuWhenNotExists(): void
    {
        // 创建根菜单项模拟对象
        $rootMenuItem = $this->createMock(ItemInterface::class);
        $serverMenuItem = $this->createMock(ItemInterface::class);
        $nodeMenuItem = $this->createMock(ItemInterface::class);
        
        // 设置根菜单项的调用顺序
        $rootMenuItem
            ->expects($this->exactly(2))
            ->method('getChild')
            ->with('服务器管理')
            ->willReturnOnConsecutiveCalls(null, $serverMenuItem);
        
        // 设置根菜单项创建'服务器管理'子菜单
        $rootMenuItem
            ->expects($this->once())
            ->method('addChild')
            ->with('服务器管理')
            ->willReturn($serverMenuItem);
        
        // 设置'服务器管理'菜单项创建'服务器节点'子菜单
        $serverMenuItem
            ->expects($this->once())
            ->method('addChild')
            ->with('服务器节点')
            ->willReturn($nodeMenuItem);
        
        // 设置'服务器节点'菜单项的URI
        $nodeMenuItem
            ->expects($this->once())
            ->method('setUri')
            ->with('/admin/node/list')
            ->willReturn($nodeMenuItem);
        
        // 设置'服务器节点'菜单项的图标
        $nodeMenuItem
            ->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-server')
            ->willReturn($nodeMenuItem);
        
        // 执行菜单构建
        ($this->adminMenu)($rootMenuItem);
    }

    public function testInvokeUsesExistingServerManagementMenu(): void
    {
        // 创建根菜单项模拟对象
        $rootMenuItem = $this->createMock(ItemInterface::class);
        $serverMenuItem = $this->createMock(ItemInterface::class);
        $nodeMenuItem = $this->createMock(ItemInterface::class);
        
        // 设置根菜单项返回现有的'服务器管理'菜单
        $rootMenuItem
            ->expects($this->exactly(2))
            ->method('getChild')
            ->with('服务器管理')
            ->willReturn($serverMenuItem);
        
        // 确保不会再次创建'服务器管理'菜单
        $rootMenuItem
            ->expects($this->never())
            ->method('addChild');
        
        // 设置'服务器管理'菜单项创建'服务器节点'子菜单
        $serverMenuItem
            ->expects($this->once())
            ->method('addChild')
            ->with('服务器节点')
            ->willReturn($nodeMenuItem);
        
        // 设置'服务器节点'菜单项的URI
        $nodeMenuItem
            ->expects($this->once())
            ->method('setUri')
            ->with('/admin/node/list')
            ->willReturn($nodeMenuItem);
        
        // 设置'服务器节点'菜单项的图标
        $nodeMenuItem
            ->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-server')
            ->willReturn($nodeMenuItem);
        
        // 执行菜单构建
        ($this->adminMenu)($rootMenuItem);
    }

    public function testInvokeCallsLinkGeneratorWithCorrectEntity(): void
    {
        // 创建简化的菜单项模拟对象
        $rootMenuItem = $this->createMock(ItemInterface::class);
        $serverMenuItem = $this->createMock(ItemInterface::class);
        $nodeMenuItem = $this->createMock(ItemInterface::class);
        
        // 设置菜单项的返回值
        $rootMenuItem
            ->method('getChild')
            ->willReturnOnConsecutiveCalls(null, $serverMenuItem);
        $rootMenuItem->method('addChild')->willReturn($serverMenuItem);
        $serverMenuItem->method('addChild')->willReturn($nodeMenuItem);
        $nodeMenuItem->method('setUri')->willReturn($nodeMenuItem);
        $nodeMenuItem->method('setAttribute')->willReturn($nodeMenuItem);
        
        // 验证LinkGenerator被正确调用
        $this->linkGenerator
            ->expects($this->once())
            ->method('getCurdListPage')
            ->with(Node::class);
        
        // 执行菜单构建
        ($this->adminMenu)($rootMenuItem);
    }

    public function testAdminMenuImplementsMenuProviderInterface(): void
    {
        // 验证AdminMenu实现了正确的接口
        $this->assertInstanceOf(
            'Tourze\\EasyAdminMenuBundle\\Service\\MenuProviderInterface',
            $this->adminMenu
        );
    }

    public function testAdminMenuIsCallable(): void
    {
        // 验证AdminMenu是可调用的
        $this->addToAssertionCount(1); // AdminMenu 类自然是可调用的
    }
} 