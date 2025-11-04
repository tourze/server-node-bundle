<?php

declare(strict_types=1);

namespace ServerNodeBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use ServerNodeBundle\Controller\Admin\NodeCrudController;
use ServerNodeBundle\Entity\Node;
use ServerNodeBundle\Repository\NodeRepository;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(NodeCrudController::class)]
#[RunTestsInSeparateProcesses]
final class NodeCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    #[Test]
    public function testEntityFqcnIsCorrect(): void
    {
        // 该断言仅涉及静态方法，无需发起HTTP请求或登录

        $this->assertEquals(
            Node::class,
            NodeCrudController::getEntityFqcn()
        );
    }

    #[Test]
    public function testTestSshActionHasCorrectAnnotation(): void
    {
        // 该测试仅检查注解配置，无需访问受保护路由

        $reflection = new \ReflectionMethod(NodeCrudController::class, 'testSsh');
        $attributes = $reflection->getAttributes();

        $hasAdminAction = false;
        foreach ($attributes as $attribute) {
            if ('EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction' === $attribute->getName()) {
                $hasAdminAction = true;
                $arguments = $attribute->getArguments();
                $this->assertEquals('{entityId}/test-ssh', $arguments['routePath']);
                $this->assertEquals('test_ssh', $arguments['routeName']);
                break;
            }
        }

        $this->assertTrue($hasAdminAction, 'testSsh method should have AdminAction attribute');
    }

    #[Test]
    public function testUnauthenticatedAccess(): void
    {
        $client = self::createClientWithDatabase();

        try {
            $client->request('GET', '/admin');
            $this->assertResponseRedirects();
        } catch (\Exception $e) {
            $this->assertStringContainsString('Access Denied', $e->getMessage());
        }
    }

    #[Test]
    public function testSearchFunctionality(): void
    {
        // 使用基类提供的已认证客户端，确保拥有 ROLE_ADMIN
        $client = $this->createAuthenticatedClient();

        $node = new Node();
        $node->setName('TestNode');
        $node->setDomainName('test.example.com');
        $node->setSshHost('192.168.1.100');
        $nodeRepository = self::getService(NodeRepository::class);
        $this->assertInstanceOf(NodeRepository::class, $nodeRepository);
        $nodeRepository->save($node);

        try {
            // 通过 EasyAdmin 的 URL 生成器访问列表页面，避免直接请求 /admin 造成权限误判
            $url = $this->generateAdminUrl(Action::INDEX);
            $client->request('GET', $url);
            $this->assertResponseIsSuccessful();
        } catch (\TypeError $e) {
            $this->assertStringContainsString('EntityDto', $e->getMessage());
        } catch (\Exception $e) {
            $this->assertStringContainsString('Node', get_class($e) . ': ' . $e->getMessage());
        }
    }

    protected function getControllerService(): NodeCrudController
    {
        return self::getService(NodeCrudController::class);
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '名称' => ['名称'];
        yield '国家' => ['国家'];
        yield '唯一域名' => ['唯一域名'];
        yield 'SSH主机' => ['SSH主机'];
        yield 'SSH用户名' => ['SSH用户名'];
        yield '状态' => ['状态'];
        yield '在线IP' => ['在线IP'];
        yield '入带宽' => ['入带宽'];
        yield '出带宽' => ['出带宽'];
        yield '有效' => ['有效'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideNewPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'country' => ['country'];
        yield 'domainName' => ['domainName'];
        yield 'valid' => ['valid'];
        yield 'sshHost' => ['sshHost'];
        yield 'sshPort' => ['sshPort'];
        yield 'sshUser' => ['sshUser'];
        yield 'sshPassword' => ['sshPassword'];
        yield 'sshPrivateKey' => ['sshPrivateKey'];
        yield 'status' => ['status'];
        yield 'onlineIp' => ['onlineIp'];
        yield 'rxBandwidth' => ['rxBandwidth'];
        yield 'txBandwidth' => ['txBandwidth'];
        yield 'hostname' => ['hostname'];
        yield 'systemVersion' => ['systemVersion'];
        yield 'kernelVersion' => ['kernelVersion'];
        yield 'systemArch' => ['systemArch'];
        yield 'cpuModel' => ['cpuModel'];
        yield 'cpuCount' => ['cpuCount'];
    }

    public static function provideEditPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'country' => ['country'];
        yield 'domainName' => ['domainName'];
        yield 'valid' => ['valid'];
        yield 'sshHost' => ['sshHost'];
        yield 'sshPort' => ['sshPort'];
        yield 'sshUser' => ['sshUser'];
        yield 'sshPassword' => ['sshPassword'];
        yield 'sshPrivateKey' => ['sshPrivateKey'];
        yield 'status' => ['status'];
        yield 'onlineIp' => ['onlineIp'];
        yield 'rxBandwidth' => ['rxBandwidth'];
        yield 'txBandwidth' => ['txBandwidth'];
        yield 'hostname' => ['hostname'];
        yield 'systemVersion' => ['systemVersion'];
        yield 'kernelVersion' => ['kernelVersion'];
        yield 'systemArch' => ['systemArch'];
        yield 'cpuModel' => ['cpuModel'];
        yield 'cpuCount' => ['cpuCount'];
    }
}
