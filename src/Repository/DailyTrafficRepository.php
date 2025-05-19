<?php

namespace ServerNodeBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use ServerNodeBundle\Entity\DailyTraffic;
use ServerNodeBundle\Entity\Node;

/**
 * @method DailyTraffic|null find($id, $lockMode = null, $lockVersion = null)
 * @method DailyTraffic|null findOneBy(array $criteria, array $orderBy = null)
 * @method DailyTraffic[] findAll()
 * @method DailyTraffic[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DailyTrafficRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DailyTraffic::class);
    }

    /**
     * 日流量入库
     */
    public function saveTraffic(Node $node, string $ip, \DateTimeInterface $date, int $rx, int $tx): DailyTraffic
    {
        $log = $this->findOneBy([
            'node' => $node,
            'date' => $date,
        ]);
        if (!$log) {
            $log = new DailyTraffic();
            $log->setRx('0');
            $log->setTx('0');
            $log->setNode($node);
            $log->setDate($date);
        }
        $log->setIp($ip);
        if ($log->getRx() < $rx) {
            $log->setRx($rx);
        }
        if ($log->getTx() < $tx) {
            $log->setTx($tx);
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
