<?php

namespace ServerNodeBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ServerNodeBundle\Repository\MinuteStatRepository;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\EasyAdmin\Attribute\Action\Deletable;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Filter\Filterable;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;
use Tourze\ScheduleEntityCleanBundle\Attribute\AsScheduleClean;

#[AsScheduleClean(expression: '14 5 * * *', defaultKeepDay: 60, keepDayEnv: 'SERVER_NODE_STAT_PERSIST_DAY_NUM')]
#[Deletable]
#[AsPermission(title: '节点统计')]
#[ORM\Table(name: 'ims_server_node_stat', options: ['comment' => '节点统计'])]
#[ORM\Entity(repositoryClass: MinuteStatRepository::class)]
#[ORM\UniqueConstraint(name: 'ims_server_node_stat_idx_unique', columns: ['node_id', 'datetime'])]
class MinuteStat implements AdminArrayInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stats')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Node $node;

    #[ListColumn(sorter: true)]
    #[IndexColumn]
    #[Filterable]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['comment' => '时间'])]
    private ?\DateTimeInterface $datetime = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(nullable: true, options: ['comment' => '系统CPU百分比'])]
    private ?int $cpuSystemPercent = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(nullable: true, options: ['comment' => '用户CPU百分比'])]
    private ?int $cpuUserPercent = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(nullable: true, options: ['comment' => '被偷CPU百分比'])]
    private ?int $cpuStolenPercent = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(nullable: true, options: ['comment' => '空闲CPU百分比'])]
    private ?int $cpuIdlePercent = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '过去1分钟负载'])]
    private ?string $loadOneMinute = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '过去5分钟负载'])]
    private ?string $loadFiveMinutes = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '过去15分钟负载'])]
    private ?string $loadFifteenMinutes = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(nullable: true, options: ['comment' => '运行进程数'])]
    private ?int $processRunning = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(nullable: true, options: ['comment' => '总进程数'])]
    private ?int $processTotal = null;

    #[ORM\Column(nullable: true)]
    private ?int $processUninterruptibleSleep = null;

    #[ORM\Column(nullable: true)]
    private ?int $processWaitingForRun = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(nullable: true, options: ['comment' => '总内存'])]
    private ?int $memoryTotal = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(nullable: true, options: ['comment' => '已用内存'])]
    private ?int $memoryUsed = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(nullable: true, options: ['comment' => '空闲内存'])]
    private ?int $memoryFree = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(nullable: true, options: ['comment' => '可用内存'])]
    private ?int $memoryAvailable = null;

    #[ORM\Column(nullable: true)]
    private ?int $memoryBuffer = null;

    #[ORM\Column(nullable: true)]
    private ?int $memoryCache = null;

    #[ORM\Column(nullable: true)]
    private ?int $memoryShared = null;

    #[ORM\Column(nullable: true)]
    private ?int $memorySwapUsed = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => '入带宽'])]
    private ?string $rxBandwidth = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => '入包量'])]
    private ?string $rxPackets = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => '出带宽'])]
    private ?string $txBandwidth = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(type: Types::BIGINT, nullable: true, options: ['comment' => '出包量'])]
    private ?string $txPackets = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $diskReadIops = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    private ?string $diskWriteIops = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $diskIoWait = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $diskAvgIoTime = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $diskBusyPercent = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(nullable: true, options: ['comment' => 'TCP连接数'])]
    private ?int $tcpEstab = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(nullable: true, options: ['comment' => 'TCP监听数'])]
    private ?int $tcpListen = null;

    #[ORM\Column(nullable: true)]
    private ?int $tcpSynSent = null;

    #[ORM\Column(nullable: true)]
    private ?int $tcpSynRecv = null;

    #[ORM\Column(nullable: true)]
    private ?int $tcpFinWait1 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tcpFinWait2 = null;

    #[ORM\Column(nullable: true)]
    private ?int $tcpTimeWait = null;

    #[ORM\Column(nullable: true)]
    private ?int $tcpCloseWait = null;

    #[ORM\Column(nullable: true)]
    private ?int $tcpClosing = null;

    #[ORM\Column(nullable: true)]
    private ?int $tcpLastAck = null;

    #[ListColumn(sorter: true)]
    #[ORM\Column(nullable: true, options: ['comment' => 'UDP监听数'])]
    private ?int $udpCount = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '在线用户'])]
    private ?array $onlineUsers = null;

    #[Filterable]
    #[IndexColumn]
    #[ListColumn(order: 98, sorter: true)]
    #[ExportColumn]
    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeInterface $createTime = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNode(): Node
    {
        return $this->node;
    }

    public function setNode(?Node $node): static
    {
        $this->node = $node;

        return $this;
    }

    public function getDatetime(): ?\DateTimeInterface
    {
        return $this->datetime;
    }

    public function setDatetime(\DateTimeInterface $datetime): static
    {
        $this->datetime = $datetime;

        return $this;
    }

    public function getCpuSystemPercent(): ?int
    {
        return $this->cpuSystemPercent;
    }

    public function setCpuSystemPercent(?int $cpuSystemPercent): static
    {
        $this->cpuSystemPercent = $cpuSystemPercent;

        return $this;
    }

    public function getCpuUserPercent(): ?int
    {
        return $this->cpuUserPercent;
    }

    public function setCpuUserPercent(?int $cpuUserPercent): static
    {
        $this->cpuUserPercent = $cpuUserPercent;

        return $this;
    }

    public function getCpuStolenPercent(): ?int
    {
        return $this->cpuStolenPercent;
    }

    public function setCpuStolenPercent(?int $cpuStolenPercent): static
    {
        $this->cpuStolenPercent = $cpuStolenPercent;

        return $this;
    }

    public function getCpuIdlePercent(): ?int
    {
        return $this->cpuIdlePercent;
    }

    public function setCpuIdlePercent(?int $cpuIdlePercent): static
    {
        $this->cpuIdlePercent = $cpuIdlePercent;

        return $this;
    }

    public function getLoadOneMinute(): ?string
    {
        return $this->loadOneMinute;
    }

    public function setLoadOneMinute(?string $loadOneMinute): static
    {
        $this->loadOneMinute = $loadOneMinute;

        return $this;
    }

    public function getLoadFiveMinutes(): ?string
    {
        return $this->loadFiveMinutes;
    }

    public function setLoadFiveMinutes(?string $loadFiveMinutes): static
    {
        $this->loadFiveMinutes = $loadFiveMinutes;

        return $this;
    }

    public function getLoadFifteenMinutes(): ?string
    {
        return $this->loadFifteenMinutes;
    }

    public function setLoadFifteenMinutes(?string $loadFifteenMinutes): static
    {
        $this->loadFifteenMinutes = $loadFifteenMinutes;

        return $this;
    }

    public function getProcessRunning(): ?int
    {
        return $this->processRunning;
    }

    public function setProcessRunning(?int $processRunning): static
    {
        $this->processRunning = $processRunning;

        return $this;
    }

    public function getProcessTotal(): ?int
    {
        return $this->processTotal;
    }

    public function setProcessTotal(?int $processTotal): static
    {
        $this->processTotal = $processTotal;

        return $this;
    }

    public function getProcessUninterruptibleSleep(): ?int
    {
        return $this->processUninterruptibleSleep;
    }

    public function setProcessUninterruptibleSleep(?int $processUninterruptibleSleep): static
    {
        $this->processUninterruptibleSleep = $processUninterruptibleSleep;

        return $this;
    }

    public function getProcessWaitingForRun(): ?int
    {
        return $this->processWaitingForRun;
    }

    public function setProcessWaitingForRun(?int $processWaitingForRun): static
    {
        $this->processWaitingForRun = $processWaitingForRun;

        return $this;
    }

    public function getMemoryTotal(): ?int
    {
        return $this->memoryTotal;
    }

    public function setMemoryTotal(?int $memoryTotal): static
    {
        $this->memoryTotal = $memoryTotal;

        return $this;
    }

    public function getMemoryUsed(): ?int
    {
        return $this->memoryUsed;
    }

    public function setMemoryUsed(?int $memoryUsed): static
    {
        $this->memoryUsed = $memoryUsed;

        return $this;
    }

    public function getMemoryFree(): ?int
    {
        return $this->memoryFree;
    }

    public function setMemoryFree(?int $memoryFree): static
    {
        $this->memoryFree = $memoryFree;

        return $this;
    }

    public function getMemoryAvailable(): ?int
    {
        return $this->memoryAvailable;
    }

    public function setMemoryAvailable(?int $memoryAvailable): static
    {
        $this->memoryAvailable = $memoryAvailable;

        return $this;
    }

    public function getMemoryBuffer(): ?int
    {
        return $this->memoryBuffer;
    }

    public function setMemoryBuffer(?int $memoryBuffer): static
    {
        $this->memoryBuffer = $memoryBuffer;

        return $this;
    }

    public function getMemoryCache(): ?int
    {
        return $this->memoryCache;
    }

    public function setMemoryCache(?int $memoryCache): static
    {
        $this->memoryCache = $memoryCache;

        return $this;
    }

    public function getMemoryShared(): ?int
    {
        return $this->memoryShared;
    }

    public function setMemoryShared(?int $memoryShared): static
    {
        $this->memoryShared = $memoryShared;

        return $this;
    }

    public function getMemorySwapUsed(): ?int
    {
        return $this->memorySwapUsed;
    }

    public function setMemorySwapUsed(?int $memorySwapUsed): static
    {
        $this->memorySwapUsed = $memorySwapUsed;

        return $this;
    }

    public function getRxBandwidth(): ?string
    {
        return $this->rxBandwidth;
    }

    public function setRxBandwidth(?string $rxBandwidth): static
    {
        $this->rxBandwidth = $rxBandwidth;

        return $this;
    }

    public function getRxPackets(): ?string
    {
        return $this->rxPackets;
    }

    public function setRxPackets(?string $rxPackets): static
    {
        $this->rxPackets = $rxPackets;

        return $this;
    }

    public function getTxBandwidth(): ?string
    {
        return $this->txBandwidth;
    }

    public function setTxBandwidth(?string $txBandwidth): static
    {
        $this->txBandwidth = $txBandwidth;

        return $this;
    }

    public function getTxPackets(): ?string
    {
        return $this->txPackets;
    }

    public function setTxPackets(?string $txPackets): static
    {
        $this->txPackets = $txPackets;

        return $this;
    }

    public function getDiskReadIops(): ?string
    {
        return $this->diskReadIops;
    }

    public function setDiskReadIops(?string $diskReadIops): static
    {
        $this->diskReadIops = $diskReadIops;

        return $this;
    }

    public function getDiskWriteIops(): ?string
    {
        return $this->diskWriteIops;
    }

    public function setDiskWriteIops(?string $diskWriteIops): static
    {
        $this->diskWriteIops = $diskWriteIops;

        return $this;
    }

    public function getDiskIoWait(): ?string
    {
        return $this->diskIoWait;
    }

    public function setDiskIoWait(?string $diskIoWait): static
    {
        $this->diskIoWait = $diskIoWait;

        return $this;
    }

    public function getDiskAvgIoTime(): ?string
    {
        return $this->diskAvgIoTime;
    }

    public function setDiskAvgIoTime(?string $diskAvgIoTime): static
    {
        $this->diskAvgIoTime = $diskAvgIoTime;

        return $this;
    }

    public function getDiskBusyPercent(): ?string
    {
        return $this->diskBusyPercent;
    }

    public function setDiskBusyPercent(?string $diskBusyPercent): static
    {
        $this->diskBusyPercent = $diskBusyPercent;

        return $this;
    }

    public function getTcpEstab(): ?int
    {
        return $this->tcpEstab;
    }

    public function setTcpEstab(?int $tcpEstab): static
    {
        $this->tcpEstab = $tcpEstab;

        return $this;
    }

    public function getTcpListen(): ?int
    {
        return $this->tcpListen;
    }

    public function setTcpListen(?int $tcpListen): static
    {
        $this->tcpListen = $tcpListen;

        return $this;
    }

    public function getTcpSynSent(): ?int
    {
        return $this->tcpSynSent;
    }

    public function setTcpSynSent(?int $tcpSynSent): static
    {
        $this->tcpSynSent = $tcpSynSent;

        return $this;
    }

    public function getTcpSynRecv(): ?int
    {
        return $this->tcpSynRecv;
    }

    public function setTcpSynRecv(?int $tcpSynRecv): static
    {
        $this->tcpSynRecv = $tcpSynRecv;

        return $this;
    }

    public function getTcpFinWait1(): ?int
    {
        return $this->tcpFinWait1;
    }

    public function setTcpFinWait1(?int $tcpFinWait1): static
    {
        $this->tcpFinWait1 = $tcpFinWait1;

        return $this;
    }

    public function getTcpFinWait2(): ?int
    {
        return $this->tcpFinWait2;
    }

    public function setTcpFinWait2(?int $tcpFinWait2): static
    {
        $this->tcpFinWait2 = $tcpFinWait2;

        return $this;
    }

    public function getTcpTimeWait(): ?int
    {
        return $this->tcpTimeWait;
    }

    public function setTcpTimeWait(?int $tcpTimeWait): static
    {
        $this->tcpTimeWait = $tcpTimeWait;

        return $this;
    }

    public function getTcpCloseWait(): ?int
    {
        return $this->tcpCloseWait;
    }

    public function setTcpCloseWait(?int $tcpCloseWait): static
    {
        $this->tcpCloseWait = $tcpCloseWait;

        return $this;
    }

    public function getTcpClosing(): ?int
    {
        return $this->tcpClosing;
    }

    public function setTcpClosing(?int $tcpClosing): static
    {
        $this->tcpClosing = $tcpClosing;

        return $this;
    }

    public function getTcpLastAck(): ?int
    {
        return $this->tcpLastAck;
    }

    public function setTcpLastAck(?int $tcpLastAck): static
    {
        $this->tcpLastAck = $tcpLastAck;

        return $this;
    }

    public function getUdpCount(): ?int
    {
        return $this->udpCount;
    }

    public function setUdpCount(?int $udpCount): static
    {
        $this->udpCount = $udpCount;

        return $this;
    }

    public function getOnlineUsers(): ?array
    {
        return $this->onlineUsers;
    }

    public function setOnlineUsers(?array $onlineUsers): static
    {
        $this->onlineUsers = $onlineUsers;

        return $this;
    }

    public function getCreateTime(): ?\DateTimeInterface
    {
        return $this->createTime;
    }

    public function setCreateTime(?\DateTimeInterface $createdAt): void
    {
        $this->createTime = $createdAt;
    }

    public function retrieveAdminArray(): array
    {
        return [
            'id' => $this->getId(),
            'datetime' => $this->getDatetime()?->format('Y-m-d H:i:s'),
            'cpuSystemPercent' => $this->getCpuSystemPercent(),
            'cpuUserPercent' => $this->getCpuUserPercent(),
            'cpuStolenPercent' => $this->getCpuStolenPercent(),
            'cpuIdlePercent' => $this->getCpuIdlePercent(),
            'loadOneMinute' => $this->getLoadOneMinute(),
            'loadFiveMinutes' => $this->getLoadFiveMinutes(),
            'loadFifteenMinutes' => $this->getLoadFifteenMinutes(),
            'processRunning' => $this->getProcessRunning(),
            'processTotal' => $this->getProcessTotal(),
            'processUninterruptibleSleep' => $this->getProcessUninterruptibleSleep(),
            'processWaitingForRun' => $this->getProcessWaitingForRun(),
            'memoryTotal' => $this->getMemoryTotal(),
            'memoryUsed' => $this->getMemoryUsed(),
            'memoryFree' => $this->getMemoryFree(),
            'memoryAvailable' => $this->getMemoryAvailable(),
            'rxBandwidth' => $this->getRxBandwidth(),
            'rxPackets' => $this->getRxPackets(),
            'txBandwidth' => $this->getTxBandwidth(),
            'txPackets' => $this->getTxPackets(),
            'tcpEstab' => $this->getTcpEstab(),
            'tcpListen' => $this->getTcpListen(),
            'udpCount' => $this->getUdpCount(),
        ];
    }
}
