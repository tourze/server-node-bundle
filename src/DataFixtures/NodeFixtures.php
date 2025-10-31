<?php

declare(strict_types=1);

namespace ServerNodeBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use ServerNodeBundle\Entity\Node;
use ServerNodeBundle\Enum\NodeStatus;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\GBT2659\Alpha2Code as GBT_2659_2000;

#[When(env: 'test')]
#[When(env: 'dev')]
class NodeFixtures extends Fixture
{
    public const REFERENCE_NODE_1 = 'node-1';
    public const REFERENCE_NODE_2 = 'node-2';

    public function load(ObjectManager $manager): void
    {
        $node1 = new Node();
        $node1->setName('测试服务器1');
        $node1->setCountry(GBT_2659_2000::HK);
        $node1->setDomainName('images.unsplash.com');
        $node1->setSshHost('192.168.1.100');
        $node1->setSshPort(22);
        $node1->setSshUser('root');
        $node1->setSshPassword('password123');
        $node1->setSshPrivateKey('-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC7VJTUt9Us8cKB
UO1WOeNcPugFiTt0OzUpPtU3RLXZJ5VBL+wJ4w4YhOGxF5GhB8iV2jWzYkQpJLqE
-----END PRIVATE KEY-----');
        $node1->setValid(true);
        $node1->setFrontendDomain('source.unsplash.com');
        $node1->setStatus(NodeStatus::ONLINE);
        $node1->setTags(['测试', '高性能']);
        $node1->setOnlineIp('192.168.1.100');
        $node1->setRxBandwidth('1000000');
        $node1->setTxBandwidth('500000');
        $node1->setLoadOneMinute('0.5');
        $node1->setUserCount(10);

        $manager->persist($node1);
        $this->addReference(self::REFERENCE_NODE_1, $node1);

        $node2 = new Node();
        $node2->setName('测试服务器2');
        $node2->setCountry(GBT_2659_2000::CN);
        $node2->setDomainName('pixabay.com');
        $node2->setSshHost('192.168.1.101');
        $node2->setSshPort(22);
        $node2->setSshUser('admin');
        $node2->setSshPassword('secure123');
        $node2->setSshPrivateKey('-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC7VJTUt9Us8cKB
UO1WOeNcPugFiTt0OzUpPtU3RLXZJ5VBL+wJ4w4YhOGxF5GhB8iV2jWzYkQpJLqE
-----END PRIVATE KEY-----');
        $node2->setValid(true);
        $node2->setFrontendDomain('www.pexels.com');
        $node2->setStatus(NodeStatus::OFFLINE);
        $node2->setTags(['测试', '备用']);
        $node2->setOnlineIp('192.168.1.101');
        $node2->setRxBandwidth('800000');
        $node2->setTxBandwidth('400000');
        $node2->setLoadOneMinute('0.2');
        $node2->setUserCount(5);

        $manager->persist($node2);
        $this->addReference(self::REFERENCE_NODE_2, $node2);

        $manager->flush();

        $node3 = new Node();
        $node3->setName('本地测试1');
        $node3->setCountry(GBT_2659_2000::CN);
        $node3->setDomainName('unsplash.com');
        $node3->setSshHost('10.211.55.48');
        $node3->setSshPort(22);
        $node3->setSshUser('parallels');
        $node3->setSshPassword('1234qwqw');
        $node3->setValid(true);
        $node3->setStatus(NodeStatus::OFFLINE);
        $manager->persist($node3);
        $manager->flush();

        $node4 = new Node();
        $node4->setName('本地测试2');
        $node4->setCountry(GBT_2659_2000::CN);
        $node4->setDomainName('www.pixabay.com');
        $node4->setSshHost('10.211.55.49');
        $node4->setSshPort(22);
        $node4->setSshUser('parallels');
        $node4->setSshPassword('1234qwqw');
        $node4->setValid(true);
        $node4->setStatus(NodeStatus::OFFLINE);
        $manager->persist($node4);
        $manager->flush();
    }
}
