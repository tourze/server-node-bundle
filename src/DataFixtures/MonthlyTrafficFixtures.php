<?php

namespace ServerNodeBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use ServerNodeBundle\Entity\MonthlyTraffic;
use ServerNodeBundle\Entity\Node;

class MonthlyTrafficFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $node1 = $this->getReference(NodeFixtures::REFERENCE_NODE_1, Node::class);
        $node2 = $this->getReference(NodeFixtures::REFERENCE_NODE_2, Node::class);

        // 为node1创建6个月的流量数据
        $date = new \DateTime();
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = clone $date;
            $monthDate->modify("-$i months");
            $monthKey = $monthDate->format('Y-m');

            $traffic = new MonthlyTraffic();
            $traffic->setNode($node1);
            $traffic->setIp('192.168.1.100');
            $traffic->setMonth($monthKey);
            $traffic->setRx((string)(30000000 + $i * 3000000)); // 下行流量每月增长
            $traffic->setTx((string)(15000000 + $i * 1500000)); // 上行流量每月增长

            $manager->persist($traffic);
        }

        // 为node2创建3个月的流量数据
        for ($i = 2; $i >= 0; $i--) {
            $monthDate = clone $date;
            $monthDate->modify("-$i months");
            $monthKey = $monthDate->format('Y-m');

            $traffic = new MonthlyTraffic();
            $traffic->setNode($node2);
            $traffic->setIp('192.168.1.101');
            $traffic->setMonth($monthKey);
            $traffic->setRx((string)(24000000 + $i * 2400000));
            $traffic->setTx((string)(12000000 + $i * 1200000));

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
