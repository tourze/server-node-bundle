<?php

namespace ServerNodeBundle\Service;

use ServerNodeBundle\Entity\Node;
use ServerNodeBundle\Repository\MinuteStatRepository;

class NodeMonitorService
{
    public function __construct(
        private readonly MinuteStatRepository $minuteStatRepository,
    ) {
    }

    /**
     * 获取网络监控数据
     */
    public function getNetworkMonitorData(Node $node): array
    {
        $now = new \DateTime();
        
        // 获取过去24小时的时间点
        $hours24 = [];
        $labels24h = [];
        $rxData24h = [];
        $txData24h = [];
        
        // 计算24小时前的时间点
        $startTime24h = (clone $now)->modify('-24 hours');
        
        for ($i = 0; $i < 24; $i++) {
            $hour = (clone $startTime24h)->modify("+{$i} hours");
            $hours24[] = $hour;
            $labels24h[] = $hour->format('H:i');
            
            // 默认值为0
            $rxData24h[] = 0;
            $txData24h[] = 0;
        }
        
        // 获取过去7天的日期
        $days7 = [];
        $labels7d = [];
        $rxData7d = [];
        $txData7d = [];
        
        // 计算7天前的时间点
        $startTime7d = (clone $now)->modify('-7 days')->setTime(0, 0, 0);
        
        for ($i = 0; $i < 7; $i++) {
            $day = (clone $startTime7d)->modify("+{$i} days");
            $days7[] = $day;
            $labels7d[] = $day->format('m-d');
            
            // 默认值为0
            $rxData7d[] = 0;
            $txData7d[] = 0;
        }
        
        // 查询最近24小时的分钟统计数据
        $qb24h = $this->minuteStatRepository->createQueryBuilder('s')
            ->where('s.node = :node')
            ->andWhere('s.datetime >= :startTime')
            ->andWhere('s.datetime <= :endTime')
            ->setParameter('node', $node)
            ->setParameter('startTime', $startTime24h)
            ->setParameter('endTime', $now)
            ->orderBy('s.datetime', 'ASC');
        
        $stats24h = $qb24h->getQuery()->getResult();
        
        // 将分钟数据聚合到小时级别
        $hourlyData = [];
        foreach ($stats24h as $stat) {
            $hourKey = $stat->getDatetime()->format('H');
            
            if (!isset($hourlyData[$hourKey])) {
                $hourlyData[$hourKey] = [
                    'rx' => 0,
                    'tx' => 0,
                    'count' => 0
                ];
            }
            
            // 确保是数字
            $rx = $stat->getRxBandwidth() ? (int)$stat->getRxBandwidth() : 0;
            $tx = $stat->getTxBandwidth() ? (int)$stat->getTxBandwidth() : 0;
            
            $hourlyData[$hourKey]['rx'] += $rx;
            $hourlyData[$hourKey]['tx'] += $tx;
            $hourlyData[$hourKey]['count']++;
        }
        
        // 填充24小时数据
        foreach ($hours24 as $index => $hour) {
            $hourKey = $hour->format('H');
            
            if (isset($hourlyData[$hourKey]) && $hourlyData[$hourKey]['count'] > 0) {
                // 计算该小时的平均值
                $rxData24h[$index] = $hourlyData[$hourKey]['rx'] / $hourlyData[$hourKey]['count'];
                $txData24h[$index] = $hourlyData[$hourKey]['tx'] / $hourlyData[$hourKey]['count'];
            }
        }
        
        // 查询最近7天的分钟统计数据
        $qb7d = $this->minuteStatRepository->createQueryBuilder('s')
            ->where('s.node = :node')
            ->andWhere('s.datetime >= :startTime')
            ->andWhere('s.datetime <= :endTime')
            ->setParameter('node', $node)
            ->setParameter('startTime', $startTime7d)
            ->setParameter('endTime', $now)
            ->orderBy('s.datetime', 'ASC');
        
        $stats7d = $qb7d->getQuery()->getResult();
        
        // 将分钟数据聚合到天级别
        $dailyData = [];
        foreach ($stats7d as $stat) {
            $dayKey = $stat->getDatetime()->format('m-d');
            
            if (!isset($dailyData[$dayKey])) {
                $dailyData[$dayKey] = [
                    'rx' => 0,
                    'tx' => 0,
                    'count' => 0
                ];
            }
            
            // 确保是数字
            $rx = $stat->getRxBandwidth() ? (int)$stat->getRxBandwidth() : 0;
            $tx = $stat->getTxBandwidth() ? (int)$stat->getTxBandwidth() : 0;
            
            $dailyData[$dayKey]['rx'] += $rx;
            $dailyData[$dayKey]['tx'] += $tx;
            $dailyData[$dayKey]['count']++;
        }
        
        // 填充7天数据
        foreach ($days7 as $index => $day) {
            $dayKey = $day->format('m-d');
            
            if (isset($dailyData[$dayKey]) && $dailyData[$dayKey]['count'] > 0) {
                // 计算该天的平均值
                $rxData7d[$index] = $dailyData[$dayKey]['rx'] / $dailyData[$dayKey]['count'];
                $txData7d[$index] = $dailyData[$dayKey]['tx'] / $dailyData[$dayKey]['count'];
            }
        }

        return [
            'labels24h' => $labels24h,
            'rxData24h' => $rxData24h,
            'txData24h' => $txData24h,
            'labels7d' => $labels7d,
            'rxData7d' => $rxData7d,
            'txData7d' => $txData7d,
        ];
    }

    /**
     * 获取负载监控数据
     */
    public function getLoadMonitorData(Node $node): array
    {
        // 获取过去24小时的时间点
        $now = new \DateTime();
        $startTime = (clone $now)->modify('-24 hours');
        
        $labels = [];
        
        // CPU数据数组
        $cpuUserData = [];
        $cpuSystemData = [];
        $cpuStolenData = [];
        $cpuIdleData = [];
        
        // 负载数据数组
        $loadOneData = [];
        $loadFiveData = [];
        $loadFifteenData = [];
        
        // 内存数据数组
        $memoryTotalData = [];
        $memoryUsedData = [];
        $memoryFreeData = [];
        $memoryAvailableData = [];
        
        // 进程数据数组
        $processRunningData = [];
        $processTotalData = [];
        
        // 查询最近24小时的负载数据
        $qb = $this->minuteStatRepository->createQueryBuilder('s')
            ->where('s.node = :node')
            ->andWhere('s.datetime >= :startTime')
            ->andWhere('s.datetime <= :endTime')
            ->setParameter('node', $node)
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $now)
            ->orderBy('s.datetime', 'ASC');
        
        $stats = $qb->getQuery()->getResult();
        
        // 按小时对数据进行分组
        $hourlyData = [];
        foreach ($stats as $stat) {
            $hourKey = $stat->getDatetime()->format('Y-m-d H:00');
            
            if (!isset($hourlyData[$hourKey])) {
                $hourlyData[$hourKey] = [
                    'label' => $stat->getDatetime()->format('H:i'),
                    'count' => 0,
                    'cpuUser' => 0,
                    'cpuSystem' => 0,
                    'cpuStolen' => 0,
                    'cpuIdle' => 0,
                    'loadOne' => 0,
                    'loadFive' => 0,
                    'loadFifteen' => 0,
                    'memoryTotal' => 0,
                    'memoryUsed' => 0,
                    'memoryFree' => 0,
                    'memoryAvailable' => 0,
                    'processRunning' => 0,
                    'processTotal' => 0,
                ];
            }
            
            // 收集CPU数据
            if ($stat->getCpuUserPercent() !== null) {
                $hourlyData[$hourKey]['cpuUser'] += $stat->getCpuUserPercent();
            }
            if ($stat->getCpuSystemPercent() !== null) {
                $hourlyData[$hourKey]['cpuSystem'] += $stat->getCpuSystemPercent();
            }
            if ($stat->getCpuStolenPercent() !== null) {
                $hourlyData[$hourKey]['cpuStolen'] += $stat->getCpuStolenPercent();
            }
            if ($stat->getCpuIdlePercent() !== null) {
                $hourlyData[$hourKey]['cpuIdle'] += $stat->getCpuIdlePercent();
            }
            
            // 收集负载数据
            if ($stat->getLoadOneMinute() !== null) {
                $hourlyData[$hourKey]['loadOne'] += (float) $stat->getLoadOneMinute();
            }
            if ($stat->getLoadFiveMinutes() !== null) {
                $hourlyData[$hourKey]['loadFive'] += (float) $stat->getLoadFiveMinutes();
            }
            if ($stat->getLoadFifteenMinutes() !== null) {
                $hourlyData[$hourKey]['loadFifteen'] += (float) $stat->getLoadFifteenMinutes();
            }
            
            // 收集内存数据
            if ($stat->getMemoryTotal() !== null) {
                $hourlyData[$hourKey]['memoryTotal'] += $stat->getMemoryTotal();
            }
            if ($stat->getMemoryUsed() !== null) {
                $hourlyData[$hourKey]['memoryUsed'] += $stat->getMemoryUsed();
            }
            if ($stat->getMemoryFree() !== null) {
                $hourlyData[$hourKey]['memoryFree'] += $stat->getMemoryFree();
            }
            if ($stat->getMemoryAvailable() !== null) {
                $hourlyData[$hourKey]['memoryAvailable'] += $stat->getMemoryAvailable();
            }
            
            // 收集进程数据
            if ($stat->getProcessRunning() !== null) {
                $hourlyData[$hourKey]['processRunning'] += $stat->getProcessRunning();
            }
            if ($stat->getProcessTotal() !== null) {
                $hourlyData[$hourKey]['processTotal'] += $stat->getProcessTotal();
            }
            
            $hourlyData[$hourKey]['count']++;
        }
        
        // 计算总平均值(用于汇总卡片)
        $totalData = [
            'cpuUsage' => 0,
            'loadAvg' => 0,
            'memUsage' => 0,
            'process' => 0,
            'count' => 0,
        ];
        
        // 处理分组后的数据并计算平均值
        foreach ($hourlyData as $hour => $data) {
            $count = $data['count'];
            if ($count > 0) {
                // 添加时间标签
                $labels[] = $data['label'];
                
                // 计算CPU平均值
                $cpuUserData[] = $data['cpuUser'] / $count;
                $cpuSystemData[] = $data['cpuSystem'] / $count;
                $cpuStolenData[] = $data['cpuStolen'] / $count;
                $cpuIdleData[] = $data['cpuIdle'] / $count;
                
                // 计算负载平均值
                $loadOneData[] = $data['loadOne'] / $count;
                $loadFiveData[] = $data['loadFive'] / $count;
                $loadFifteenData[] = $data['loadFifteen'] / $count;
                
                // 计算内存平均值
                $memoryTotalData[] = $data['memoryTotal'] / $count;
                $memoryUsedData[] = $data['memoryUsed'] / $count;
                $memoryFreeData[] = $data['memoryFree'] / $count;
                $memoryAvailableData[] = $data['memoryAvailable'] / $count;
                
                // 计算进程平均值
                $processRunningData[] = $data['processRunning'] / $count;
                $processTotalData[] = $data['processTotal'] / $count;
                
                // 累加总平均值计算
                $totalData['cpuUsage'] += (($data['cpuUser'] + $data['cpuSystem'] + $data['cpuStolen']) / $count);
                $totalData['loadAvg'] += ($data['loadOne'] / $count);
                
                // 计算内存使用率
                if ($data['memoryTotal'] > 0) {
                    $memUsage = ($data['memoryUsed'] / $data['memoryTotal']) * 100;
                    $totalData['memUsage'] += $memUsage;
                }
                
                $totalData['process'] += ($data['processTotal'] / $count);
                $totalData['count']++;
            }
        }
        
        // 计算所有小时的平均值
        $avgCpuUsage = $totalData['count'] > 0 ? $totalData['cpuUsage'] / $totalData['count'] : 0;
        $avgLoad = $totalData['count'] > 0 ? $totalData['loadAvg'] / $totalData['count'] : 0;
        $avgMemUsage = $totalData['count'] > 0 ? $totalData['memUsage'] / $totalData['count'] : 0;
        $avgProcess = $totalData['count'] > 0 ? $totalData['process'] / $totalData['count'] : 0;

        return [
            'labels' => $labels,
            'cpuUserData' => $cpuUserData,
            'cpuSystemData' => $cpuSystemData,
            'cpuStolenData' => $cpuStolenData,
            'cpuIdleData' => $cpuIdleData,
            'loadOneData' => $loadOneData,
            'loadFiveData' => $loadFiveData,
            'loadFifteenData' => $loadFifteenData,
            'memoryTotalData' => $memoryTotalData,
            'memoryUsedData' => $memoryUsedData,
            'memoryFreeData' => $memoryFreeData,
            'memoryAvailableData' => $memoryAvailableData,
            'processRunningData' => $processRunningData,
            'processTotalData' => $processTotalData,
            'avgCpuUsage' => $avgCpuUsage,
            'avgLoad' => $avgLoad,
            'avgMemUsage' => $avgMemUsage,
            'avgProcess' => $avgProcess,
        ];
    }
}
