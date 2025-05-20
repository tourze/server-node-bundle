<?php

namespace ServerNodeBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use ServerNodeBundle\Entity\Node;
use ServerNodeBundle\Enum\NodeStatus;
use Tourze\GBT2659\Alpha2Code as GBT_2659_2000;

class NodeTest extends TestCase
{
    private Node $node;

    protected function setUp(): void
    {
        $this->node = new Node();
    }

    public function testInitialState(): void
    {
        // 初始状态下ID应该为空
        $this->assertNull($this->node->getId());
        
        // 初始状态下valid应该为false
        $this->assertFalse($this->node->isValid());
        
        // 初始状态下status应该是INIT
        $this->assertEquals(NodeStatus::INIT, $this->node->getStatus());
    }

    public function testSetAndGetName(): void
    {
        $name = '测试服务器';
        $this->node->setName($name);
        $this->assertEquals($name, $this->node->getName());
    }

    public function testSetAndGetValid(): void
    {
        $this->node->setValid(true);
        $this->assertTrue($this->node->isValid());
        
        $this->node->setValid(false);
        $this->assertFalse($this->node->isValid());
    }

    public function testSetAndGetCountry(): void
    {
        $country = GBT_2659_2000::CN;
        $this->node->setCountry($country);
        $this->assertEquals($country, $this->node->getCountry());
        
        $country = GBT_2659_2000::US;
        $this->node->setCountry($country);
        $this->assertEquals($country, $this->node->getCountry());
    }

    public function testSetAndGetFrontendDomain(): void
    {
        $domain = 'test.example.com';
        $this->node->setFrontendDomain($domain);
        $this->assertEquals($domain, $this->node->getFrontendDomain());
        
        // 测试空值
        $this->node->setFrontendDomain(null);
        $this->assertNull($this->node->getFrontendDomain());
    }

    public function testSetAndGetDomainName(): void
    {
        $domain = 'server1.example.com';
        $this->node->setDomainName($domain);
        $this->assertEquals($domain, $this->node->getDomainName());
        
        // 测试空值
        $this->node->setDomainName(null);
        $this->assertNull($this->node->getDomainName());
    }

    public function testSetAndGetSshSettings(): void
    {
        // 测试SSH主机设置
        $host = '192.168.1.100';
        $this->node->setSshHost($host);
        $this->assertEquals($host, $this->node->getSshHost());
        
        // 测试SSH端口设置
        $port = 2222;
        $this->node->setSshPort($port);
        $this->assertEquals($port, $this->node->getSshPort());
        
        // 测试SSH用户名设置
        $user = 'root';
        $this->node->setSshUser($user);
        $this->assertEquals($user, $this->node->getSshUser());
        
        // 测试SSH密码设置
        $password = 'secure_password';
        $this->node->setSshPassword($password);
        $this->assertEquals($password, $this->node->getSshPassword());
    }

    public function testSetAndGetMainInterface(): void
    {
        $interface = 'eth1';
        $this->node->setMainInterface($interface);
        $this->assertEquals($interface, $this->node->getMainInterface());
        
        // 测试空值
        $this->node->setMainInterface(null);
        $this->assertNull($this->node->getMainInterface());
    }

    public function testSetAndGetFlowStatistics(): void
    {
        // 测试总流量设置
        $totalFlow = '1000000';
        $this->node->setTotalFlow($totalFlow);
        $this->assertEquals($totalFlow, $this->node->getTotalFlow());
        
        // 测试上传流量设置
        $uploadFlow = '500000';
        $this->node->setUploadFlow($uploadFlow);
        $this->assertEquals($uploadFlow, $this->node->getUploadFlow());
        
        // 测试下载流量设置
        $downloadFlow = '500000';
        $this->node->setDownloadFlow($downloadFlow);
        $this->assertEquals($downloadFlow, $this->node->getDownloadFlow());
    }

    public function testSetAndGetSystemInfo(): void
    {
        // 测试主机名设置
        $hostname = 'server1';
        $this->node->setHostname($hostname);
        $this->assertEquals($hostname, $this->node->getHostname());
        
        // 测试虚拟化技术设置
        $virt = 'KVM';
        $this->node->setVirtualizationTech($virt);
        $this->assertEquals($virt, $this->node->getVirtualizationTech());
        
        // 测试CPU型号设置
        $cpuModel = 'Intel Xeon E5-2680';
        $this->node->setCpuModel($cpuModel);
        $this->assertEquals($cpuModel, $this->node->getCpuModel());
        
        // 测试CPU频率设置
        $cpuFreq = '3.500';
        $this->node->setCpuMaxFreq($cpuFreq);
        $this->assertEquals($cpuFreq, $this->node->getCpuMaxFreq());
        
        // 测试CPU核心数设置
        $cpuCount = 8;
        $this->node->setCpuCount($cpuCount);
        $this->assertEquals($cpuCount, $this->node->getCpuCount());
        
        // 测试系统版本设置
        $sysVer = 'Ubuntu 22.04 LTS';
        $this->node->setSystemVersion($sysVer);
        $this->assertEquals($sysVer, $this->node->getSystemVersion());
        
        // 测试内核版本设置
        $kernelVer = '5.15.0-91-generic';
        $this->node->setKernelVersion($kernelVer);
        $this->assertEquals($kernelVer, $this->node->getKernelVersion());
        
        // 测试系统架构设置
        $sysArch = 'x86_64';
        $this->node->setSystemArch($sysArch);
        $this->assertEquals($sysArch, $this->node->getSystemArch());
        
        // 测试系统UUID设置
        $sysUuid = '550e8400-e29b-41d4-a716-446655440000';
        $this->node->setSystemUuid($sysUuid);
        $this->assertEquals($sysUuid, $this->node->getSystemUuid());
        
        // 测试TCP拥塞控制设置
        $tcpCC = 'bbr';
        $this->node->setTcpCongestionControl($tcpCC);
        $this->assertEquals($tcpCC, $this->node->getTcpCongestionControl());
    }

    public function testSetAndGetStatus(): void
    {
        $status = NodeStatus::ONLINE;
        $this->node->setStatus($status);
        $this->assertEquals($status, $this->node->getStatus());
        
        $status = NodeStatus::OFFLINE;
        $this->node->setStatus($status);
        $this->assertEquals($status, $this->node->getStatus());
        
        $status = NodeStatus::MAINTAIN;
        $this->node->setStatus($status);
        $this->assertEquals($status, $this->node->getStatus());
        
        $status = NodeStatus::BANDWIDTH_OVER;
        $this->node->setStatus($status);
        $this->assertEquals($status, $this->node->getStatus());
    }

    public function testSetAndGetTags(): void
    {
        $tags = ['测试', '高性能', '备用'];
        $this->node->setTags($tags);
        $this->assertEquals($tags, $this->node->getTags());
        
        // 测试空值
        $this->node->setTags(null);
        $this->assertNull($this->node->getTags());
    }

    public function testSetAndGetOnlineIp(): void
    {
        $ip = '192.168.1.100';
        $this->node->setOnlineIp($ip);
        $this->assertEquals($ip, $this->node->getOnlineIp());
        
        // 测试空值
        $this->node->setOnlineIp(null);
        $this->assertNull($this->node->getOnlineIp());
    }

    public function testSetAndGetBandwidth(): void
    {
        // 测试入带宽设置
        $rxBandwidth = '100.50';
        $this->node->setRxBandwidth($rxBandwidth);
        $this->assertEquals($rxBandwidth, $this->node->getRxBandwidth());
        
        // 测试出带宽设置
        $txBandwidth = '50.25';
        $this->node->setTxBandwidth($txBandwidth);
        $this->assertEquals($txBandwidth, $this->node->getTxBandwidth());
    }

    public function testSetAndGetLoadOneMinute(): void
    {
        $load = '0.75';
        $this->node->setLoadOneMinute($load);
        $this->assertEquals($load, $this->node->getLoadOneMinute());
        
        // 测试空值
        $this->node->setLoadOneMinute(null);
        $this->assertNull($this->node->getLoadOneMinute());
    }

    public function testSetAndGetUserCount(): void
    {
        $count = 100;
        $this->node->setUserCount($count);
        $this->assertEquals($count, $this->node->getUserCount());
        
        // 默认值测试
        $newNode = new Node();
        $this->assertEquals(0, $newNode->getUserCount());
    }

    public function testGetAccessHost(): void
    {
        // 测试设置了域名时获取访问主机名
        $this->node->setDomainName('server.example.com');
        $this->assertEquals('server.example.com', $this->node->getAccessHost());
        
        // 测试没有域名但有SSH主机时获取访问主机名
        $this->node->setDomainName(null);
        $this->node->setSshHost('192.168.1.100');
        $this->assertEquals('192.168.1.100', $this->node->getAccessHost());
    }

    public function testToString(): void
    {
        // 模拟一个实体ID
        $reflection = new \ReflectionClass($this->node);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($this->node, '123456789');
        
        $this->node->setName('测试服务器');
        $this->assertEquals('测试服务器', $this->node->__toString());
        
        // 测试没有ID的情况
        $newNode = new Node();
        $this->assertEquals('', $newNode->__toString());
    }
} 