<?php

namespace ServerNodeBundle\Application;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use ServerNodeBundle\Entity\Application;
use ServerNodeBundle\Entity\MinuteStat;
use ServerNodeBundle\Repository\ApplicationRepository;
use ServerNodeBundle\Repository\DailyTrafficRepository;
use ServerNodeBundle\Repository\MonthlyTrafficRepository;
use ServerNodeBundle\Repository\NodeRepository;
use ServerNodeBundle\SSH\SSHConnection;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tourze\DoctrineAsyncBundle\Service\DoctrineService;
use Tourze\EnumExtra\SelectDataFetcher;
use Tourze\TempFileBundle\Service\TemporaryFileService;
use Yiisoft\Arrays\ArrayHelper;

/**
 * 机器数据收集应用
 */
#[AutoconfigureTag('application.type.provider')]
class StatsCollector implements ApplicationInterface, SelectDataFetcher
{
    public const NAME = 'stats-collector';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly NodeRepository $nodeRepository,
        private readonly DoctrineService $doctrineService,
        private readonly ApplicationRepository $applicationRepository,
        private readonly DailyTrafficRepository $dailyTrafficRepository,
        private readonly MonthlyTrafficRepository $monthlyTrafficRepository,
        private readonly TemporaryFileService $temporaryFileService,
    ) {
    }

    public function genSelectData(): array
    {
        return [
            [
                'label' => $this->getLabel(),
                'text' => $this->getLabel(),
                'value' => $this->getCode(),
                'name' => $this->getLabel(),
            ],
        ];
    }

    public function getCode(): string
    {
        return self::NAME;
    }

    public function getLabel(): string
    {
        return '机器状态上报';
    }

    public function install(Application $application, SSHConnection $ssh): bool
    {
        $node = $application->getNode();
        $country = $application->getNode()->getCountry()->toLowerCase();
        $remoteDir = "/data/{$country}_{$node->getId()}";

        $ssh->createDirectory($remoteDir);

        $code = file_get_contents(__DIR__ . '/../../shell/collect.sh');
        // 修改一下，再重新写进入
        $code = str_replace('{{ apiKey }}', "{$node->getApiKey()}_{$application->getId()}", $code);
        $code = str_replace('{{ apiSecret }}', $node->getApiSecret(), $code);
        $code = str_replace('{{ mainInterface }}', $node->getMainInterface(), $code);

        $url = $_ENV['NODE_SERVER_MANAGE_API_DOMAIN'] . $this->urlGenerator->generate('server-node-report', ['id' => $node->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);
        $code = str_replace('{{ collectUrl }}', $url, $code);

        $tempFile = $this->temporaryFileService->generateTemporaryFileName('shell');
        try {
            file_put_contents($tempFile, $code);
            $ssh->uploadFile($tempFile, "{$remoteDir}/collect.sh");
            $ssh->sudoExec("bash {$remoteDir}/collect.sh");
        } finally {
            @unlink($tempFile);
        }

        return true;
    }

    /**
     * 收集应用信息
     */
    public function collectStats(Application $application, CarbonInterface $time, array $post): void
    {
        $node = $application->getNode();

        // 更新静态信息
        $node->setHostname(ArrayHelper::getValue($post, 'hostname'));
        $node->setVirtualizationTech(ArrayHelper::getValue($post, 'virtualization_tech'));
        $node->setCpuModel(ArrayHelper::getValue($post, 'cpu_model'));
        $node->setCpuMaxFreq(ArrayHelper::getValue($post, 'cpu_max_freq'));
        $node->setCpuCount(ArrayHelper::getValue($post, 'cpu_count'));
        $node->setSystemVersion(ArrayHelper::getValue($post, 'system_version'));
        $node->setKernelVersion(ArrayHelper::getValue($post, 'kernel_version'));
        $node->setSystemArch(ArrayHelper::getValue($post, 'system_arch'));
        $node->setSystemUuid(ArrayHelper::getValue($post, 'system_uuid'));
        $node->setTcpCongestionControl(ArrayHelper::getValue($post, 'tcp_congestion_control'));
        if (isset($post['online_ip'])) {
            $node->setOnlineIp($post['online_ip']);
        }
        $this->nodeRepository->save($node);

        // 重复上报一般都是错误的，所以这里我们直接异步插入算了
        $stat = new MinuteStat();
        $stat->setNode($node);
        $stat->setDatetime($time->startOfMinute());

        $stat->setCpuSystemPercent(ArrayHelper::getValue($post, 'cpu_system_percent'));
        $stat->setCpuUserPercent(ArrayHelper::getValue($post, 'cpu_user_percent'));
        $stat->setCpuStolenPercent(ArrayHelper::getValue($post, 'cpu_stolen_percent'));
        $stat->setCpuIdlePercent(ArrayHelper::getValue($post, 'cpu_idle_percent'));

        $stat->setLoadOneMinute(ArrayHelper::getValue($post, 'load_one_minute'));
        $stat->setLoadFiveMinutes(ArrayHelper::getValue($post, 'load_five_minutes'));
        $stat->setLoadFifteenMinutes(ArrayHelper::getValue($post, 'load_fifteen_minutes'));

        $stat->setProcessRunning(ArrayHelper::getValue($post, 'process_running'));
        $stat->setProcessWaitingForRun(ArrayHelper::getValue($post, 'process_waiting_for_run'));
        $stat->setProcessUninterruptibleSleep(ArrayHelper::getValue($post, 'process_uninterruptible_sleep'));
        $stat->setProcessTotal(ArrayHelper::getValue($post, 'process_total'));

        $stat->setMemoryTotal(ArrayHelper::getValue($post, 'memory_total'));
        $stat->setMemoryUsed(ArrayHelper::getValue($post, 'memory_used'));
        $stat->setMemoryFree(ArrayHelper::getValue($post, 'memory_free'));
        $stat->setMemoryShared(ArrayHelper::getValue($post, 'memory_shared'));
        $stat->setMemoryBuffer(ArrayHelper::getValue($post, 'memory_buffer'));
        $stat->setMemoryAvailable(ArrayHelper::getValue($post, 'memory_available'));
        $stat->setMemorySwapUsed(ArrayHelper::getValue($post, 'memory_swap_used'));
        $stat->setMemoryCache(ArrayHelper::getValue($post, 'memory_cache'));

        $stat->setRxBandwidth(ArrayHelper::getValue($post, 'rx_bandwidth'));
        $stat->setTxBandwidth(ArrayHelper::getValue($post, 'tx_bandwidth'));
        $stat->setRxPackets(ArrayHelper::getValue($post, 'rx_packets'));
        $stat->setTxPackets(ArrayHelper::getValue($post, 'tx_packets'));

        $stat->setDiskReadIops(floatval(ArrayHelper::getValue($post, 'disk_read_iops')));
        $stat->setDiskWriteIops(floatval(ArrayHelper::getValue($post, 'disk_write_iops')));
        $stat->setDiskAvgIoTime(floatval(ArrayHelper::getValue($post, 'disk_avg_io_time')));
        $stat->setDiskBusyPercent(floatval(ArrayHelper::getValue($post, 'disk_busy_percent')));
        $stat->setDiskIoWait(floatval(ArrayHelper::getValue($post, 'disk_io_wait')));

        $stat->setUdpCount(intval(ArrayHelper::getValue($post, 'udp_count')));
        $stat->setTcpEstab(intval(ArrayHelper::getValue($post, 'tcp_estab')));
        $stat->setTcpListen(intval(ArrayHelper::getValue($post, 'tcp_listen')));
        $stat->setTcpSynSent(intval(ArrayHelper::getValue($post, 'tcp_syn_sent')));
        $stat->setTcpSynRecv(intval(ArrayHelper::getValue($post, 'tcp_syn_recv')));
        $stat->setTcpFinWait1(intval(ArrayHelper::getValue($post, 'tcp_fin_wait_1')));
        $stat->setTcpFinWait2(intval(ArrayHelper::getValue($post, 'tcp_fin_wait_2')));
        $stat->setTcpTimeWait(intval(ArrayHelper::getValue($post, 'tcp_time_wait')));
        $stat->setTcpCloseWait(intval(ArrayHelper::getValue($post, 'tcp_close_wait')));
        $stat->setTcpClosing(intval(ArrayHelper::getValue($post, 'tcp_closing')));
        $stat->setTcpLastAck(intval(ArrayHelper::getValue($post, 'tcp_last_ack')));

        $this->doctrineService->asyncInsert($stat);

        // 更新节点信息
        $node->setLoadOneMinute($stat->getLoadOneMinute());
        $node->setRxBandwidth($stat->getRxBandwidth() / 1000000);
        $node->setTxBandwidth($stat->getTxBandwidth() / 1000000);
        $this->nodeRepository->persist($node);

        // 更新活跃时间
        $application->setActiveTime(Carbon::now());
        $application->setOnline(true); // 统计服务，能收到就说明可以上线了
        $this->applicationRepository->persist($application);

        $this->applicationRepository->flush();

        // 更新流量统计
        // 日流量入库
        $rx = ArrayHelper::getValue($post, 'vnstat_day_rx');
        if ($rx !== 'null' && $rx !== null) {
            $this->dailyTrafficRepository->saveTraffic(
                $node,
                $node->getOnlineIp(),
                $time,
                intval($post['vnstat_day_rx']),
                intval($post['vnstat_day_tx']),
            );
        }
        // 月流量入库
        $tx = ArrayHelper::getValue($post, 'vnstat_month_rx');
        if ($tx !== 'null' && $tx !== null) {
            $this->monthlyTrafficRepository->saveTraffic(
                $node,
                $node->getOnlineIp(),
                $time,
                intval($post['vnstat_month_rx']),
                intval($post['vnstat_month_tx']),
            );
        }
    }

    public function healthCheck(Application $application, CarbonInterface $time): ?bool
    {
        // 如果一个节点，最近3分钟都没上报过信息，那么可以简单判断他出问题了。默认情况下我们1分钟上报一次
        if (null !== $application->getActiveTime()) {
            if (Carbon::now()->diffInMinutes($application->getActiveTime()) >= 3) {
                return false;
            }

            return true;
        }

        return null;
    }

    public function getDefaultPort(): ?int
    {
        return null;
    }
}
