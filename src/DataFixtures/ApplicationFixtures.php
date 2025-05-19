<?php

namespace ServerNodeBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use ServerNodeBundle\Application\Nginx;
use ServerNodeBundle\Application\Redis;
use ServerNodeBundle\Application\Tengine;
use ServerNodeBundle\Entity\Application;
use ServerNodeBundle\Entity\Node;

class ApplicationFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $node1 = $this->getReference(NodeFixtures::REFERENCE_NODE_1, Node::class);
        $node2 = $this->getReference(NodeFixtures::REFERENCE_NODE_2, Node::class);

        $app1 = new Application();
        $app1->setNode($node1);
        $app1->setType(Nginx::CODE);
        $app1->setPort(80);
        $app1->setConfig('server { listen 80; }');
        $app1->setOnline(true);
        $app1->setActiveTime(new \DateTime());

        $manager->persist($app1);

        $app2 = new Application();
        $app2->setNode($node1);
        $app2->setType(Tengine::CODE);
        $app2->setPort(8080);
        $app2->setConfig('server { listen 8080; }');
        $app2->setOnline(true);
        $app2->setActiveTime(new \DateTime());

        $manager->persist($app2);

        $app3 = new Application();
        $app3->setNode($node2);
        $app3->setType(Redis::CODE);
        $app3->setPort(6379);
        $app3->setConfig('maxmemory 256mb');
        $app3->setOnline(true);
        $app3->setActiveTime(new \DateTime());

        $manager->persist($app3);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            NodeFixtures::class,
        ];
    }
}
