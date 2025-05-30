<?php

namespace ServerNodeBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ServerNodeBundle\Enum\NodeStatus;
use ServerNodeBundle\Repository\NodeRepository;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\DoctrineUserBundle\Attribute\UpdatedByColumn;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;
use Tourze\GBT2659\Alpha2Code as GBT_2659_2000;

#[AsPermission(title: '服务器节点')]
#[ORM\Entity(repositoryClass: NodeRepository::class)]
#[ORM\Table(name: 'ims_server_node', options: ['comment' => '服务器节点'])]
class Node implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    private ?bool $valid = false;

    #[TrackColumn]
    #[ORM\Column(length: 100, options: ['comment' => '名称'])]
    private string $name;

    #[TrackColumn]
    #[ORM\Column(length: 5, nullable: true, enumType: GBT_2659_2000::class, options: ['comment' => '国家'])]
    private ?GBT_2659_2000 $country = GBT_2659_2000::HK;

    #[TrackColumn]
    #[ORM\Column(length: 200, nullable: true, options: ['comment' => '前置域名'])]
    private ?string $frontendDomain = null;

    /**
     * 主要用于识别这个机器的作用，是用户自己分配的，非主机名
     */
    #[TrackColumn]
    #[ORM\Column(length: 120, unique: true, nullable: true, options: ['comment' => '唯一域名'])]
    private ?string $domainName = null;

    #[TrackColumn]
    #[ORM\Column(length: 60, options: ['comment' => 'SSH主机'])]
    private ?string $sshHost = null;

    #[TrackColumn]
    #[ORM\Column(options: ['comment' => 'SSH端口'])]
    private int $sshPort = 22;

    #[TrackColumn]
    #[ORM\Column(length: 60, nullable: true, options: ['comment' => 'SSH用户名'])]
    private ?string $sshUser = null;

    #[TrackColumn]
    #[ORM\Column(length: 120, nullable: true, options: ['comment' => 'SSH密码'])]
    private ?string $sshPassword = null;

    #[TrackColumn]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'SSH私钥'])]
    private ?string $sshPrivateKey = null;

    #[TrackColumn]
    #[ORM\Column(length: 20, nullable: true, options: ['comment' => '主网卡'])]
    private ?string $mainInterface = 'eth0';

    #[TrackColumn]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '总流量'])]
    private ?string $totalFlow = '0';

    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '上传流量'])]
    private ?string $uploadFlow = '0';

    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '下载流量'])]
    private ?string $downloadFlow = '0';

    #[TrackColumn]
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $hostname = null;

    #[TrackColumn]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $virtualizationTech = null;

    #[TrackColumn]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $cpuModel = null;

    #[TrackColumn]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 3, nullable: true)]
    private ?string $cpuMaxFreq = null;

    #[TrackColumn]
    #[ORM\Column(nullable: true)]
    private ?int $cpuCount = null;

    #[TrackColumn]
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $systemVersion = null;

    #[TrackColumn]
    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '内核版本'])]
    private ?string $kernelVersion = null;

    #[TrackColumn]
    #[ORM\Column(length: 10, nullable: true, options: ['comment' => '系统架构'])]
    private ?string $systemArch = null;

    #[TrackColumn]
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $systemUuid = null;

    #[TrackColumn]
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $tcpCongestionControl = null;

    #[ORM\Column(length: 40, nullable: true, enumType: NodeStatus::class, options: ['comment' => '状态'])]
    private ?NodeStatus $status = NodeStatus::INIT;

    #[TrackColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '标签列表'])]
    private ?array $tags = null;

    #[TrackColumn]
    #[ORM\Column(length: 45, nullable: true, options: ['comment' => '在线IP'])]
    private ?string $onlineIp = null;

    #[TrackColumn]
    #[ORM\Column(length: 64, unique: true, nullable: true, options: ['comment' => 'API密钥'])]
    private ?string $apiKey = null;

    #[TrackColumn]
    #[ORM\Column(length: 64, nullable: true, options: ['comment' => 'API密钥'])]
    private ?string $apiSecret = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['comment' => '入带宽'])]
    private ?string $rxBandwidth = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['comment' => '出带宽'])]
    private ?string $txBandwidth = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '负载'])]
    private ?string $loadOneMinute = null;

    #[ORM\Column(nullable: true, options: ['comment' => '在线数'])]
    private ?int $userCount = 0;

    #[CreatedByColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '创建人'])]
    private ?string $createdBy = null;

    #[UpdatedByColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '更新人'])]
    private ?string $updatedBy = null;

    #[IndexColumn]
    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeInterface $createTime = null;

    #[UpdateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '更新时间'])]
    private ?\DateTimeInterface $updateTime = null;

    public function __construct()
    {
        $this->setApiKey('AK' . md5(uniqid()));
        $this->setApiSecret('SK' . md5(uniqid()));
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): self
    {
        $this->valid = $valid;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCountry(): ?GBT_2659_2000
    {
        return $this->country;
    }

    public function setCountry(?GBT_2659_2000 $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getFrontendDomain(): ?string
    {
        return $this->frontendDomain;
    }

    public function setFrontendDomain(?string $frontendDomain): static
    {
        $this->frontendDomain = $frontendDomain;

        return $this;
    }

    public function getDomainName(): ?string
    {
        return $this->domainName;
    }

    public function setDomainName(?string $domainName): static
    {
        $this->domainName = $domainName;

        return $this;
    }

    public function getSshHost(): ?string
    {
        return $this->sshHost;
    }

    public function setSshHost(string $sshHost): static
    {
        $this->sshHost = $sshHost;

        return $this;
    }

    public function getSshPort(): int
    {
        return $this->sshPort;
    }

    public function setSshPort(int $sshPort): static
    {
        $this->sshPort = $sshPort;

        return $this;
    }

    public function getSshUser(): ?string
    {
        return $this->sshUser;
    }

    public function setSshUser(?string $sshUser): static
    {
        $this->sshUser = $sshUser;

        return $this;
    }

    public function getSshPassword(): ?string
    {
        return $this->sshPassword;
    }

    public function setSshPassword(?string $sshPassword): static
    {
        $this->sshPassword = $sshPassword;

        return $this;
    }

    public function getSshPrivateKey(): ?string
    {
        return $this->sshPrivateKey;
    }

    public function setSshPrivateKey(?string $sshPrivateKey): static
    {
        $this->sshPrivateKey = $sshPrivateKey;

        return $this;
    }

    public function getMainInterface(): ?string
    {
        return $this->mainInterface;
    }

    public function setMainInterface(?string $mainInterface): static
    {
        $this->mainInterface = $mainInterface;

        return $this;
    }

    public function getTotalFlow(): ?string
    {
        return $this->totalFlow;
    }

    public function setTotalFlow(string $totalFlow): static
    {
        $this->totalFlow = $totalFlow;

        return $this;
    }

    public function getUploadFlow(): ?string
    {
        return $this->uploadFlow;
    }

    public function setUploadFlow(string $uploadFlow): static
    {
        $this->uploadFlow = $uploadFlow;

        return $this;
    }

    public function getDownloadFlow(): ?string
    {
        return $this->downloadFlow;
    }

    public function setDownloadFlow(string $downloadFlow): static
    {
        $this->downloadFlow = $downloadFlow;

        return $this;
    }

    public function getHostname(): ?string
    {
        return $this->hostname;
    }

    public function setHostname(?string $hostname): static
    {
        $this->hostname = $hostname;

        return $this;
    }

    public function getVirtualizationTech(): ?string
    {
        return $this->virtualizationTech;
    }

    public function setVirtualizationTech(?string $virtualizationTech): static
    {
        $this->virtualizationTech = $virtualizationTech;

        return $this;
    }

    public function getCpuModel(): ?string
    {
        return $this->cpuModel;
    }

    public function setCpuModel(?string $cpuModel): static
    {
        $this->cpuModel = $cpuModel;

        return $this;
    }

    public function getCpuMaxFreq(): ?string
    {
        return $this->cpuMaxFreq;
    }

    public function setCpuMaxFreq(?string $cpuMaxFreq): static
    {
        $this->cpuMaxFreq = $cpuMaxFreq;

        return $this;
    }

    public function getCpuCount(): ?int
    {
        return $this->cpuCount;
    }

    public function setCpuCount(?int $cpuCount): static
    {
        $this->cpuCount = $cpuCount;

        return $this;
    }

    public function getSystemVersion(): ?string
    {
        return $this->systemVersion;
    }

    public function setSystemVersion(?string $systemVersion): static
    {
        $this->systemVersion = $systemVersion;

        return $this;
    }

    public function getKernelVersion(): ?string
    {
        return $this->kernelVersion;
    }

    public function setKernelVersion(?string $kernelVersion): static
    {
        $this->kernelVersion = $kernelVersion;

        return $this;
    }

    public function getSystemArch(): ?string
    {
        return $this->systemArch;
    }

    public function setSystemArch(?string $systemArch): static
    {
        $this->systemArch = $systemArch;

        return $this;
    }

    public function getSystemUuid(): ?string
    {
        return $this->systemUuid;
    }

    public function setSystemUuid(?string $systemUuid): static
    {
        $this->systemUuid = $systemUuid;

        return $this;
    }

    public function getTcpCongestionControl(): ?string
    {
        return $this->tcpCongestionControl;
    }

    public function setTcpCongestionControl(?string $tcpCongestionControl): static
    {
        $this->tcpCongestionControl = $tcpCongestionControl;

        return $this;
    }

    public function getStatus(): ?NodeStatus
    {
        return $this->status;
    }

    public function setStatus(?NodeStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    public function getOnlineIp(): ?string
    {
        return $this->onlineIp;
    }

    public function setOnlineIp(?string $onlineIp): static
    {
        $this->onlineIp = $onlineIp;

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): static
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getApiSecret(): ?string
    {
        return $this->apiSecret;
    }

    public function setApiSecret(?string $apiSecret): static
    {
        $this->apiSecret = $apiSecret;

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

    public function getTxBandwidth(): ?string
    {
        return $this->txBandwidth;
    }

    public function setTxBandwidth(?string $txBandwidth): static
    {
        $this->txBandwidth = $txBandwidth;

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

    public function getUserCount(): ?int
    {
        return $this->userCount;
    }

    public function setUserCount(int $userCount): static
    {
        $this->userCount = $userCount;

        return $this;
    }

    public function getAccessHost(): string
    {
        if ($this->getDomainName()) {
            return $this->getDomainName();
        }

        return $this->getSshHost();
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?string $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

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

    public function getUpdateTime(): ?\DateTimeInterface
    {
        return $this->updateTime;
    }

    public function setUpdateTime(?\DateTimeInterface $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    public function __toString(): string
    {
        if (!$this->getId()) {
            return '';
        }

        return $this->getName();
    }
}
