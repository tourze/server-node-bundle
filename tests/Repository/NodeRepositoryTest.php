<?php

declare(strict_types=1);

namespace ServerNodeBundle\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ServerNodeBundle\Entity\Node;
use ServerNodeBundle\Repository\NodeRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(NodeRepository::class)]
#[RunTestsInSeparateProcesses]
final class NodeRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // 初始化测试环境，无需特殊设置
    }

    protected function createNewEntity(): object
    {
        $entity = new Node();
        $entity->setName('Test Node ' . uniqid());
        $entity->setSshHost('127.0.0.' . rand(1, 254));
        $entity->setSshPort(22);
        $entity->setSshUser('testuser');
        $entity->setValid(true);

        return $entity;
    }

    protected function getRepository(): NodeRepository
    {
        return self::getService(NodeRepository::class);
    }

    public function testCanFindAllNodes(): void
    {
        $nodes = $this->getRepository()->findAll();
        $this->assertIsArray($nodes);
    }

    public function testCanFindNodeById(): void
    {
        $node = $this->getRepository()->find(1);
        $this->assertNull($node); // 空数据库中应该返回null
    }

    public function testCanFindNodesByStatus(): void
    {
        $nodes = $this->getRepository()->findBy(['status' => 'active']);
        $this->assertIsArray($nodes);
    }

    public function testCanFindOneNodeByName(): void
    {
        $node = $this->getRepository()->findOneBy(['name' => 'test']);
        $this->assertNull($node); // 空数据库中应该为null
    }

    public function testRepositoryIsRegistered(): void
    {
        $this->assertInstanceOf(NodeRepository::class, $this->getRepository());
    }

    public function testFindByWithNullableFieldIsNullQuery(): void
    {
        $repository = $this->getRepository();
        $node1 = new Node();
        $node1->setName('Node with domain');
        $node1->setSshHost('127.0.0.1');
        $node1->setDomainName('example.com');
        $repository->save($node1);

        $node2 = new Node();
        $node2->setName('Node without domain');
        $node2->setSshHost('127.0.0.2');
        $node2->setDomainName(null);
        $repository->save($node2);

        $results = $repository->findBy(['domainName' => null]);
        $this->assertCount(1, $results);
        $firstResult = $results[0];
        $this->assertInstanceOf(Node::class, $firstResult);
        $this->assertEquals('Node without domain', $firstResult->getName());
    }

    public function testCountWithNullableFieldQuery(): void
    {
        $repository = $this->getRepository();
        $node = new Node();
        $node->setName('Node');
        $node->setSshHost('127.0.0.1');
        $node->setApiKey(null);
        $repository->save($node);

        $count = $repository->count(['apiKey' => null]);
        $this->assertEquals(1, $count);
    }

    public function testSaveMethodPersistsEntity(): void
    {
        $repository = $this->getRepository();
        $node = new Node();
        $node->setName('New Node');
        $node->setSshHost('127.0.0.1');

        $repository->save($node);

        $this->assertNotNull($node->getId());
        $found = $repository->find($node->getId());
        $this->assertInstanceOf(Node::class, $found);
        $this->assertEquals('New Node', $found->getName());
    }

    public function testSaveMethodWithoutFlushDoesNotPersist(): void
    {
        $repository = $this->getRepository();
        $node = new Node();
        $node->setName('Pending Node');
        $node->setSshHost('127.0.0.1');

        $repository->save($node, false);

        $em = self::getService(EntityManagerInterface::class);
        $em->clear();

        $found = $repository->find($node->getId());
        $this->assertNull($found);
    }

    public function testRemoveMethodDeletesEntity(): void
    {
        $repository = $this->getRepository();
        $node = new Node();
        $node->setName('To Delete');
        $node->setSshHost('127.0.0.1');
        $repository->save($node);

        $id = $node->getId();
        $repository->remove($node);

        $found = $repository->find($id);
        $this->assertNull($found);
    }

    public function testRemoveMethodWithoutFlushDoesNotDelete(): void
    {
        $repository = $this->getRepository();
        $node = new Node();
        $node->setName('Pending Delete');
        $node->setSshHost('127.0.0.1');
        $repository->save($node);

        $id = $node->getId();
        $repository->remove($node, false);

        $em = self::getService(EntityManagerInterface::class);
        $em->clear();

        $found = $repository->find($id);
        $this->assertInstanceOf(Node::class, $found);
    }

    public function testFindOneByWithOrderBySorting(): void
    {
        $repository = $this->getRepository();

        // 先清理所有现有的 valid=true 的节点，确保测试隔离
        $existingNodes = $repository->findBy(['valid' => true]);
        foreach ($existingNodes as $node) {
            $this->assertInstanceOf(Node::class, $node);
            $repository->remove($node);
        }

        $node1 = new Node();
        $node1->setName('ZZZ Node');
        $node1->setSshHost('127.0.0.1');
        $node1->setValid(true);
        $repository->save($node1);

        $node2 = new Node();
        $node2->setName('AAA Node');
        $node2->setSshHost('127.0.0.2');
        $node2->setValid(true);
        $repository->save($node2);

        $result = $repository->findOneBy(['valid' => true], ['name' => 'ASC']);
        $this->assertInstanceOf(Node::class, $result);
        $this->assertEquals('AAA Node', $result->getName());

        $result = $repository->findOneBy(['valid' => true], ['name' => 'DESC']);
        $this->assertInstanceOf(Node::class, $result);
        $this->assertEquals('ZZZ Node', $result->getName());
    }

    public function testFindOneByWithNullableFieldIsNullQuery(): void
    {
        $repository = $this->getRepository();

        // 先清理所有 hostname 为 null 的节点，确保测试隔离
        $existingNodes = $repository->findBy(['hostname' => null]);
        foreach ($existingNodes as $node) {
            $this->assertInstanceOf(Node::class, $node);
            $repository->remove($node);
        }

        $node1 = new Node();
        $node1->setName('Node with hostname');
        $node1->setSshHost('127.0.0.1');
        $node1->setHostname('server1');
        $repository->save($node1);

        $node2 = new Node();
        $node2->setName('Node without hostname');
        $node2->setSshHost('127.0.0.2');
        $node2->setHostname(null);
        $repository->save($node2);

        $result = $repository->findOneBy(['hostname' => null]);
        $this->assertInstanceOf(Node::class, $result);
        $this->assertEquals('Node without hostname', $result->getName());
    }

    public function testCountWithMultipleNullableFieldQueries(): void
    {
        $repository = $this->getRepository();

        // 先清理所有现有的节点，确保测试隔离
        $existingNodes = $repository->findAll();
        foreach ($existingNodes as $node) {
            $this->assertInstanceOf(Node::class, $node);
            $repository->remove($node);
        }

        $node1 = new Node();
        $node1->setName('Complete Node');
        $node1->setSshHost('127.0.0.1');
        $node1->setHostname('server1');
        $node1->setSystemVersion('Ubuntu 20.04');
        $repository->save($node1);

        $node2 = new Node();
        $node2->setName('Partial Node');
        $node2->setSshHost('127.0.0.2');
        $node2->setHostname(null);
        $node2->setSystemVersion(null);
        $repository->save($node2);

        $countNullHostname = $repository->count(['hostname' => null]);
        $this->assertEquals(1, $countNullHostname);

        $countNullSystemVersion = $repository->count(['systemVersion' => null]);
        $this->assertEquals(1, $countNullSystemVersion);
    }

    public function testFindOneByRobustFieldQuery(): void
    {
        $repository = $this->getRepository();
        $node = new Node();
        $node->setName('Test Node');
        $node->setSshHost('127.0.0.1');
        $node->setDomainName('test.com');
        $repository->save($node);

        $result = $repository->findOneBy(['domainName' => 'test.com']);
        $this->assertInstanceOf(Node::class, $result);
        $this->assertEquals('Test Node', $result->getName());
    }

    public function testAdditionalNullableFieldIsNullQueries(): void
    {
        $repository = $this->getRepository();

        $node1 = new Node();
        $node1->setName('Node with API key');
        $node1->setSshHost('127.0.0.1');
        $node1->setApiKey('test-key');
        $repository->save($node1);

        $node2 = new Node();
        $node2->setName('Node without API key');
        $node2->setSshHost('127.0.0.2');
        $node2->setApiKey(null);
        $repository->save($node2);

        $results = $repository->findBy(['apiKey' => null]);
        $this->assertCount(1, $results);
        $firstResult = $results[0];
        $this->assertInstanceOf(Node::class, $firstResult);
        $this->assertEquals('Node without API key', $firstResult->getName());
    }

    public function testMoreCountNullableFieldQueries(): void
    {
        $repository = $this->getRepository();

        // 先清理所有现有的节点，确保测试隔离
        $existingNodes = $repository->findAll();
        foreach ($existingNodes as $node) {
            $this->assertInstanceOf(Node::class, $node);
            $repository->remove($node);
        }

        $node = new Node();
        $node->setName('Node');
        $node->setSshHost('127.0.0.1');
        $node->setFrontendDomain(null);
        $node->setCpuModel(null);
        $node->setKernelVersion(null);
        $repository->save($node);

        $this->assertEquals(1, $repository->count(['frontendDomain' => null]));
        $this->assertEquals(1, $repository->count(['cpuModel' => null]));
        $this->assertEquals(1, $repository->count(['kernelVersion' => null]));
    }

    public function testFindOneByWithOrderBySortingMultipleFields(): void
    {
        $repository = $this->getRepository();

        // 先清理所有现有的节点，确保测试隔离
        $existingNodes = $repository->findAll();
        foreach ($existingNodes as $node) {
            $this->assertInstanceOf(Node::class, $node);
            $repository->remove($node);
        }

        $node1 = new Node();
        $node1->setName('Server A');
        $node1->setSshHost('192.168.1.1');
        $node1->setValid(true);
        $repository->save($node1);

        $node2 = new Node();
        $node2->setName('Server B');
        $node2->setSshHost('192.168.1.2');
        $node2->setValid(true);
        $repository->save($node2);

        $node3 = new Node();
        $node3->setName('Server C');
        $node3->setSshHost('192.168.1.3');
        $node3->setValid(false);
        $repository->save($node3);

        $result = $repository->findOneBy(['valid' => true], ['name' => 'DESC', 'id' => 'ASC']);
        $this->assertInstanceOf(Node::class, $result);
        $this->assertEquals('Server B', $result->getName());

        $result = $repository->findOneBy([], ['valid' => 'DESC', 'name' => 'ASC']);
        $this->assertInstanceOf(Node::class, $result);
        $this->assertEquals('Server A', $result->getName());
    }

    public function testInvalidFieldQueryRobustness(): void
    {
        $repository = $this->getRepository();

        $this->expectException(ORMException::class);
        $repository->findOneBy(['invalidFieldName' => 'value']);
    }

    public function testAdditionalCountNullableFieldQueries(): void
    {
        $repository = $this->getRepository();

        // 先清理所有现有的节点，确保测试隔离
        $existingNodes = $repository->findAll();
        foreach ($existingNodes as $node) {
            $this->assertInstanceOf(Node::class, $node);
            $repository->remove($node);
        }

        $node1 = new Node();
        $node1->setName('Node with all fields');
        $node1->setSshHost('127.0.0.1');
        $node1->setSshPassword('pass');
        $node1->setSshPrivateKey('key');
        $node1->setSystemArch('x64');
        $repository->save($node1);

        $node2 = new Node();
        $node2->setName('Node with null fields');
        $node2->setSshHost('127.0.0.2');
        $node2->setSshPassword(null);
        $node2->setSshPrivateKey(null);
        $node2->setSystemArch(null);
        $repository->save($node2);

        $this->assertEquals(1, $repository->count(['sshPassword' => null]));
        $this->assertEquals(1, $repository->count(['sshPrivateKey' => null]));
        $this->assertEquals(1, $repository->count(['systemArch' => null]));
    }

    public function testMoreIsNullFieldQueries(): void
    {
        $repository = $this->getRepository();

        // 先清理所有现有的节点，确保测试隔离
        $existingNodes = $repository->findAll();
        foreach ($existingNodes as $node) {
            $this->assertInstanceOf(Node::class, $node);
            $repository->remove($node);
        }

        $node = new Node();
        $node->setName('Test Node');
        $node->setSshHost('127.0.0.1');
        $node->setOnlineIp(null);
        $node->setHostname(null);
        $node->setTags(null);
        $repository->save($node);

        $results = $repository->findBy(['onlineIp' => null]);
        $this->assertCount(1, $results);

        $results = $repository->findBy(['hostname' => null]);
        $this->assertCount(1, $results);

        $results = $repository->findBy(['tags' => null]);
        $this->assertCount(1, $results);
    }

    public function testFindOneByWithAllNullableFields(): void
    {
        $repository = $this->getRepository();

        $node = new Node();
        $node->setName('Test Node');
        $node->setSshHost('127.0.0.1');
        $node->setVirtualizationTech(null);
        $node->setTcpCongestionControl(null);
        $node->setSystemUuid(null);
        $node->setCpuMaxFreq(null);
        $node->setCpuCount(null);
        $node->setRxBandwidth(null);
        $node->setTxBandwidth(null);
        $node->setLoadOneMinute(null);
        $repository->save($node);

        $result = $repository->findOneBy(['virtualizationTech' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['tcpCongestionControl' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['systemUuid' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['cpuMaxFreq' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['cpuCount' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['rxBandwidth' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['txBandwidth' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['loadOneMinute' => null]);
        $this->assertInstanceOf(Node::class, $result);
    }

    public function testCountWithAllNullableFields(): void
    {
        $repository = $this->getRepository();

        // 先清理所有现有的节点，确保测试隔离
        $existingNodes = $repository->findAll();
        foreach ($existingNodes as $node) {
            $this->assertInstanceOf(Node::class, $node);
            $repository->remove($node);
        }

        $node = new Node();
        $node->setName('Test Node');
        $node->setSshHost('127.0.0.1');
        $node->setVirtualizationTech(null);
        $node->setTcpCongestionControl(null);
        $node->setSystemUuid(null);
        $node->setCpuMaxFreq(null);
        $node->setCpuCount(null);
        $node->setRxBandwidth(null);
        $node->setTxBandwidth(null);
        $node->setLoadOneMinute(null);
        $node->setSshUser(null);
        $node->setApiSecret(null);
        $repository->save($node);

        $this->assertEquals(1, $repository->count(['virtualizationTech' => null]));
        $this->assertEquals(1, $repository->count(['tcpCongestionControl' => null]));
        $this->assertEquals(1, $repository->count(['systemUuid' => null]));
        $this->assertEquals(1, $repository->count(['cpuMaxFreq' => null]));
        $this->assertEquals(1, $repository->count(['cpuCount' => null]));
        $this->assertEquals(1, $repository->count(['rxBandwidth' => null]));
        $this->assertEquals(1, $repository->count(['txBandwidth' => null]));
        $this->assertEquals(1, $repository->count(['loadOneMinute' => null]));
        $this->assertEquals(1, $repository->count(['sshUser' => null]));
        $this->assertEquals(1, $repository->count(['apiSecret' => null]));
    }

    public function testFindOneByOrderBySortingWithNullableFields(): void
    {
        $repository = $this->getRepository();

        // 先清理所有现有的节点，确保测试隔离
        $existingNodes = $repository->findAll();
        foreach ($existingNodes as $node) {
            $this->assertInstanceOf(Node::class, $node);
            $repository->remove($node);
        }

        $node1 = new Node();
        $node1->setName('Node A');
        $node1->setSshHost('127.0.0.1');
        $node1->setSystemVersion('Ubuntu 20.04');
        $repository->save($node1);

        $node2 = new Node();
        $node2->setName('Node B');
        $node2->setSshHost('127.0.0.2');
        $node2->setSystemVersion(null);
        $repository->save($node2);

        $node3 = new Node();
        $node3->setName('Node C');
        $node3->setSshHost('127.0.0.3');
        $node3->setSystemVersion('CentOS 8');
        $repository->save($node3);

        $results = $repository->findBy([], ['systemVersion' => 'ASC']);
        $this->assertCount(3, $results);
        $nonNullResults = array_filter($results, function (Node $node) {
            return null !== $node->getSystemVersion();
        });
        $this->assertCount(2, $nonNullResults);
        $firstNonNull = reset($nonNullResults);
        /** @var Node $firstNonNull */
        $this->assertEquals('CentOS 8', $firstNonNull->getSystemVersion());

        $results = $repository->findBy([], ['systemVersion' => 'DESC']);
        $this->assertCount(3, $results);
        $nonNullResults = array_filter($results, function (Node $node) {
            return null !== $node->getSystemVersion();
        });
        $this->assertCount(2, $nonNullResults);
        $firstNonNull = reset($nonNullResults);
        /** @var Node $firstNonNull */
        $this->assertEquals('Ubuntu 20.04', $firstNonNull->getSystemVersion());

        $result = $repository->findOneBy(['systemVersion' => null], ['name' => 'ASC']);
        $this->assertInstanceOf(Node::class, $result);
        $this->assertEquals('Node B', $result->getName());
    }

    public function testCompleteNullableFieldCoverage(): void
    {
        $repository = $this->getRepository();

        // 先清理所有现有的节点，确保测试隔离
        $existingNodes = $repository->findAll();
        foreach ($existingNodes as $node) {
            $this->assertInstanceOf(Node::class, $node);
            $repository->remove($node);
        }

        $node = new Node();
        $node->setName('Complete Test Node');
        $node->setSshHost('127.0.0.1');
        $node->setValid(null);
        $node->setCountry(null);
        $node->setStatus(null);
        $node->setApiKey(null);
        $node->setApiSecret(null);
        $repository->save($node);

        $this->assertEquals(1, $repository->count(['valid' => null]));
        $this->assertEquals(1, $repository->count(['country' => null]));
        $this->assertEquals(1, $repository->count(['status' => null]));
        $this->assertEquals(1, $repository->count(['domainName' => null]));
        $this->assertEquals(1, $repository->count(['tags' => null]));
        $this->assertEquals(1, $repository->count(['onlineIp' => null]));

        $results = $repository->findBy(['valid' => null]);
        $this->assertCount(1, $results);

        $results = $repository->findBy(['country' => null]);
        $this->assertCount(1, $results);

        $results = $repository->findBy(['status' => null]);
        $this->assertCount(1, $results);

        $results = $repository->findBy(['sshUser' => null]);
        $this->assertCount(1, $results);

        $results = $repository->findBy(['sshPassword' => null]);
        $this->assertCount(1, $results);

        $results = $repository->findBy(['cpuModel' => null]);
        $this->assertCount(1, $results);

        $results = $repository->findBy(['systemVersion' => null]);
        $this->assertCount(1, $results);

        $results = $repository->findBy(['kernelVersion' => null]);
        $this->assertCount(1, $results);

        $results = $repository->findBy(['systemArch' => null]);
        $this->assertCount(1, $results);

        $results = $repository->findBy(['apiSecret' => null]);
        $this->assertCount(1, $results);

        $result = $repository->findOneBy(['valid' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['country' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['frontendDomain' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['domainName' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['sshUser' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['sshPassword' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['sshPrivateKey' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['cpuModel' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['systemVersion' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['kernelVersion' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['systemArch' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['status' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['tags' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['onlineIp' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['apiKey' => null]);
        $this->assertInstanceOf(Node::class, $result);

        $result = $repository->findOneBy(['apiSecret' => null]);
        $this->assertInstanceOf(Node::class, $result);
    }
}
