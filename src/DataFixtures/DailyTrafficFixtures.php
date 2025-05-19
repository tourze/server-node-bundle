<?php

namespace ServerNodeBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use ServerNodeBundle\Entity\DailyTraffic;
use ServerNodeBundle\Entity\Node;

class DailyTrafficFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $node1 = $this->getReference(NodeFixtures::REFERENCE_NODE_1, Node::class);
        $node2 = $this->getReference(NodeFixtures::REFERENCE_NODE_2, Node::class);

        // 为node1创建7天的流量数据
        for ($i = 7; $i >= 0; $i--) {
            $date = new \DateTime();
            $date->modify("-$i days");

            $traffic = new DailyTraffic();
            $traffic->setNode($node1);
            $traffic->setIp('192.168.1.100');
            $traffic->setDate($date);
            $traffic->setRx((string)(1000000 + $i * 100000)); // 下行流量增长
            $traffic->setTx((string)(500000 + $i * 50000));   // 上行流量增长

            $manager->persist($traffic);
        }

        // 为node2创建3天的流量数据
        for ($i = 3; $i >= 0; $i--) {
            $date = new \DateTime();
            $date->modify("-$i days");

            $traffic = new DailyTraffic();
            $traffic->setNode($node2);
            $traffic->setIp('192.168.1.101');
            $traffic->setDate($date);
            $traffic->setRx((string)(800000 + $i * 80000));
            $traffic->setTx((string)(400000 + $i * 40000));

            $manager->persist($traffic);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            NodeFixtures::class,
        ];
    }
}
