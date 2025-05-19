<?php

namespace ServerNodeBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use ServerNodeBundle\Entity\MinuteStat;
use ServerNodeBundle\Entity\Node;

class MinuteStatFixtures extends Fixture implements DependentFixtureInterface
{
    // 批处理大小
    private const BATCH_SIZE = 100;

    public function load(ObjectManager $manager): void
    {
        $node1 = $this->getReference(NodeFixtures::REFERENCE_NODE_1, Node::class);
        $node2 = $this->getReference(NodeFixtures::REFERENCE_NODE_2, Node::class);

        // 为node1创建24小时的统计数据，每分钟一条
        $now = new \DateTime();
        $batchCounter = 0;

        // node1 - 24小时的分钟数据
        for ($hour = 23; $hour >= 0; $hour--) {
            for ($minute = 59; $minute >= 0; $minute--) {
                $datetime = clone $now;
                $datetime->modify("-$hour hours")->modify("-$minute minutes");

                $stat = $this->createMinuteStat($node1, $datetime, 8 * 1024 * 1024); // 8GB
                $manager->persist($stat);
                
                // 每BATCH_SIZE条数据执行一次flush并清理EntityManager
                $batchCounter++;
                if ($batchCounter % self::BATCH_SIZE === 0) {
                    $manager->flush();
                    $manager->clear();
                    
                    // 重新获取引用，因为clear会清除引用
                    $node1 = $this->getReference(NodeFixtures::REFERENCE_NODE_1, Node::class);
                    $node2 = $this->getReference(NodeFixtures::REFERENCE_NODE_2, Node::class);
                }
            }
        }

        // node2 - 12小时的分钟数据
        for ($hour = 11; $hour >= 0; $hour--) {
            for ($minute = 59; $minute >= 0; $minute--) {
                $datetime = clone $now;
                $datetime->modify("-$hour hours")->modify("-$minute minutes");

                $stat = $this->createMinuteStat($node2, $datetime, 4 * 1024 * 1024); // 4GB
                $manager->persist($stat);
                
                // 每BATCH_SIZE条数据执行一次flush并清理EntityManager
                $batchCounter++;
                if ($batchCounter % self::BATCH_SIZE === 0) {
                    $manager->flush();
                    $manager->clear();
                    
                    // 重新获取引用，因为clear会清除引用
                    $node1 = $this->getReference(NodeFixtures::REFERENCE_NODE_1, Node::class);
                    $node2 = $this->getReference(NodeFixtures::REFERENCE_NODE_2, Node::class);
                }
            }
        }

        // 确保最后一批数据被保存
        if ($batchCounter % self::BATCH_SIZE !== 0) {
            $manager->flush();
        }
    }

    /**
     * 创建一条分钟统计数据
     * 
     * @param Node $node 节点对象
     * @param \DateTime $datetime 时间点
     * @param int $memoryTotal 总内存(KB)
     * @return MinuteStat
     */
    private function createMinuteStat(Node $node, \DateTime $datetime, int $memoryTotal): MinuteStat
    {
        $stat = new MinuteStat();
        $stat->setNode($node);
        $stat->setDatetime($datetime);

        // CPU数据
        $stat->setCpuSystemPercent(mt_rand(2, 15));
        $stat->setCpuUserPercent(mt_rand(10, 60));
        $stat->setCpuStolenPercent(mt_rand(0, 3));
        $stat->setCpuIdlePercent(mt_rand(30, 85));

        // 负载数据
        $stat->setLoadOneMinute(sprintf('%.2f', mt_rand(10, 100) / 100));
        $stat->setLoadFiveMinutes(sprintf('%.2f', mt_rand(8, 90) / 100));
        $stat->setLoadFifteenMinutes(sprintf('%.2f', mt_rand(5, 80) / 100));

        // 进程数据
        $stat->setProcessRunning(mt_rand(1, 10));
        $stat->setProcessTotal(mt_rand(50, 300));
        $stat->setProcessUninterruptibleSleep(mt_rand(0, 3));
        $stat->setProcessWaitingForRun(mt_rand(0, 5));

        // 内存数据
        $stat->setMemoryTotal($memoryTotal);
        $memUsed = mt_rand((int)($memoryTotal * 0.3), (int)($memoryTotal * 0.7));
        $stat->setMemoryUsed($memUsed);
        $stat->setMemoryFree($memoryTotal - $memUsed);
        $stat->setMemoryAvailable($memoryTotal - $memUsed + mt_rand(500, 1000) * 1024);
        $stat->setMemoryBuffer(mt_rand(200, 500) * 1024);
        $stat->setMemoryCache(mt_rand(800, 1500) * 1024);
        $stat->setMemoryShared(mt_rand(100, 200) * 1024);
        $stat->setMemorySwapUsed(mt_rand(0, 1024) * 1024);

        // 网络数据 - 添加随机波动模拟真实情况
        $baseRx = 30000000 + ($node === $this->getReference(NodeFixtures::REFERENCE_NODE_1, Node::class) ? 20000000 : 0);
        $baseTx = 15000000 + ($node === $this->getReference(NodeFixtures::REFERENCE_NODE_1, Node::class) ? 5000000 : 0);
        
        $hourFactor = (int)$datetime->format('G'); // 0-23小时
        $minuteFactor = (int)$datetime->format('i'); // 0-59分钟
        
        // 模拟一天内的流量波动 (高峰时段流量增加)
        $timeMultiplier = 1.0;
        if ($hourFactor >= 9 && $hourFactor <= 18) {
            $timeMultiplier = 1.5; // 工作时间流量增加
        } elseif ($hourFactor >= 0 && $hourFactor <= 5) {
            $timeMultiplier = 0.6; // 深夜流量减少
        }
        
        // 添加随机波动
        $rxBandwidth = (int)($baseRx * $timeMultiplier) + mt_rand(-5000000, 5000000);
        $txBandwidth = (int)($baseTx * $timeMultiplier) + mt_rand(-2000000, 2000000);
        
        $stat->setRxBandwidth((string)max(0, $rxBandwidth));
        $stat->setTxBandwidth((string)max(0, $txBandwidth));
        $stat->setRxPackets((string)mt_rand(30000, 200000));
        $stat->setTxPackets((string)mt_rand(20000, 150000));

        // 磁盘IO数据
        $stat->setDiskReadIops((string)mt_rand(50, 500));
        $stat->setDiskWriteIops((string)mt_rand(30, 300));
        $stat->setDiskIoWait((string)(mt_rand(5, 20) / 10));
        $stat->setDiskAvgIoTime((string)(mt_rand(10, 50) / 10));
        $stat->setDiskBusyPercent((string)(mt_rand(20, 70) / 10));

        // TCP/UDP连接数据
        $stat->setTcpEstab(mt_rand(50, 500));
        $stat->setTcpListen(mt_rand(5, 30));
        $stat->setTcpSynSent(mt_rand(0, 10));
        $stat->setTcpSynRecv(mt_rand(0, 5));
        $stat->setTcpFinWait1(mt_rand(0, 3));
        $stat->setTcpFinWait2(mt_rand(0, 3));
        $stat->setTcpTimeWait(mt_rand(10, 50));
        $stat->setTcpCloseWait(mt_rand(0, 5));
        $stat->setTcpClosing(mt_rand(0, 2));
        $stat->setTcpLastAck(mt_rand(0, 2));
        $stat->setUdpCount(mt_rand(5, 20));

        // 在线用户数据 - 根据时间调整在线用户数
        $baseUserCount = ($node === $this->getReference(NodeFixtures::REFERENCE_NODE_1, Node::class)) ? 
            mt_rand(3, 8) : mt_rand(1, 4);
            
        // 工作时间用户增加，夜间用户减少
        $userMultiplier = 1.0;
        if ($hourFactor >= 9 && $hourFactor <= 18) {
            $userMultiplier = 1.5;
        } elseif ($hourFactor >= 0 && $hourFactor <= 5) {
            $userMultiplier = 0.5;
        }
        
        $userCount = max(1, (int)($baseUserCount * $userMultiplier));
        
        $onlineUsers = [];
        for ($j = 0; $j < $userCount; $j++) {
            $onlineUsers[] = [
                'user' => 'user' . ($j + 1),
                'ip' => '192.168.1.' . mt_rand(10, 250),
                'loginTime' => (clone $datetime)->modify('-' . mt_rand(1, 60) . ' minutes')->format('Y-m-d H:i:s'),
            ];
        }
        $stat->setOnlineUsers($onlineUsers);

        return $stat;
    }

    public function getDependencies(): array
    {
        return [
            NodeFixtures::class,
        ];
    }
}
