<?php

declare(strict_types=1);

namespace ServerNodeBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use ServerNodeBundle\Entity\Node;
use ServerNodeBundle\Enum\NodeStatus;
use Tourze\GBT2659\Alpha2Code;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Node::class)]
final class NodeTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Node();
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', '测试服务器'];
        yield 'valid' => ['valid', true];
        yield 'country' => ['country', Alpha2Code::CN];
        yield 'frontendDomain' => ['frontendDomain', 'test.example.com'];
        yield 'domainName' => ['domainName', 'server1.example.com'];
        yield 'sshHost' => ['sshHost', '192.168.1.100'];
        yield 'sshPort' => ['sshPort', 2222];
        yield 'sshUser' => ['sshUser', 'root'];
        yield 'sshPassword' => ['sshPassword', 'secure_password'];
        yield 'sshPrivateKey' => ['sshPrivateKey', "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC7VJTUt9Us8cKB\n-----END PRIVATE KEY-----"];
        yield 'totalFlow' => ['totalFlow', '1000000'];
        yield 'uploadFlow' => ['uploadFlow', '500000'];
        yield 'downloadFlow' => ['downloadFlow', '500000'];
        yield 'hostname' => ['hostname', 'server1'];
        yield 'virtualizationTech' => ['virtualizationTech', 'KVM'];
        yield 'cpuModel' => ['cpuModel', 'Intel Xeon E5-2680'];
        yield 'cpuMaxFreq' => ['cpuMaxFreq', '3.500'];
        yield 'cpuCount' => ['cpuCount', 8];
        yield 'systemVersion' => ['systemVersion', 'Ubuntu 22.04 LTS'];
        yield 'kernelVersion' => ['kernelVersion', '5.15.0-91-generic'];
        yield 'systemArch' => ['systemArch', 'x86_64'];
        yield 'systemUuid' => ['systemUuid', '550e8400-e29b-41d4-a716-446655440000'];
        yield 'tcpCongestionControl' => ['tcpCongestionControl', 'bbr'];
        yield 'status' => ['status', NodeStatus::ONLINE];
        yield 'tags' => ['tags', ['测试', '高性能', '备用']];
        yield 'onlineIp' => ['onlineIp', '192.168.1.100'];
        yield 'rxBandwidth' => ['rxBandwidth', '100.50'];
        yield 'txBandwidth' => ['txBandwidth', '50.25'];
        yield 'loadOneMinute' => ['loadOneMinute', '0.75'];
        yield 'userCount' => ['userCount', 100];
    }

    public function testInitialState(): void
    {
        $node = $this->createEntity();
        $this->assertInstanceOf(Node::class, $node);

        // 初始状态下ID应该为空
        $this->assertNull($node->getId());

        // 初始状态下valid应该为false
        $this->assertFalse($node->isValid());

        // 初始状态下status应该是INIT
        $this->assertEquals(NodeStatus::INIT, $node->getStatus());

        // 初始状态下userCount应该为0
        $this->assertEquals(0, $node->getUserCount());
    }

    public function testGetAccessHost(): void
    {
        $node = $this->createEntity();
        $this->assertInstanceOf(Node::class, $node);

        // 测试设置了域名时获取访问主机名
        $node->setDomainName('server.example.com');
        $this->assertEquals('server.example.com', $node->getAccessHost());

        // 测试没有域名但有SSH主机时获取访问主机名
        $node->setDomainName(null);
        $node->setSshHost('192.168.1.100');
        $this->assertEquals('192.168.1.100', $node->getAccessHost());
    }

    public function testToString(): void
    {
        $node = $this->createEntity();
        $this->assertInstanceOf(Node::class, $node);

        // 测试没有ID的情况
        $this->assertEquals('', $node->__toString());

        // 测试设置名称
        $node->setName('测试服务器');
        $this->assertEquals('测试服务器', $node->getName());
    }
}
