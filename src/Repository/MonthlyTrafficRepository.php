<?php

namespace ServerNodeBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use ServerNodeBundle\Entity\MonthlyTraffic;
use ServerNodeBundle\Entity\Node;

/**
 * @method MonthlyTraffic|null find($id, $lockMode = null, $lockVersion = null)
 * @method MonthlyTraffic|null findOneBy(array $criteria, array $orderBy = null)
 * @method MonthlyTraffic[] findAll()
 * @method MonthlyTraffic[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MonthlyTrafficRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MonthlyTraffic::class);
    }

    /**
     * 月流量入库
     */
    public function saveTraffic(Node $node, string $ip, \DateTimeInterface $date, int $rx, int $tx): MonthlyTraffic
    {
        $log = $this->findOneBy([
            'node' => $node,
            'month' => $date->format('Y-m'),
        ]);
        if (!$log) {
            $log = new MonthlyTraffic();
            $log->setRx('0');
            $log->setTx('0');
            $log->setNode($node);
            $log->setMonth($date->format('Y-m'));
        }
        $log->setIp($ip);
        if ($log->getRx() < $rx) {
            $log->setRx($rx);
        }
        if ($log->getTx() < $rx) {
            $log->setTx($rx);
        }

        try {
            $this->save($log);
            return $log;
        } catch (UniqueConstraintViolationException $exception) {
            return $this->findOneBy([
                'node' => $node,
                'ip' => $ip,
                'date' => $date,
            ]);
        }
    }
}
