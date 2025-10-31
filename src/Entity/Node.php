<?php

declare(strict_types=1);

namespace ServerNodeBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ServerNodeBundle\Enum\NodeStatus;
use ServerNodeBundle\Repository\NodeRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\GBT2659\Alpha2Code as GBT_2659_2000;

#[ORM\Entity(repositoryClass: NodeRepository::class)]
#[ORM\Table(name: 'ims_server_node', options: ['comment' => '服务器节点'])]
class Node implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[Assert\Type(type: 'bool')]
    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    private ?bool $valid = false;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[TrackColumn]
    #[ORM\Column(length: 100, options: ['comment' => '名称'])]
    private string $name;

    #[Assert\Choice(callback: [GBT_2659_2000::class, 'cases'])]
    #[TrackColumn]
    #[ORM\Column(length: 5, nullable: true, enumType: GBT_2659_2000::class, options: ['comment' => '国家'])]
    private ?GBT_2659_2000 $country = GBT_2659_2000::HK;

    #[Assert\Length(max: 200)]
    #[TrackColumn]
    #[ORM\Column(length: 200, nullable: true, options: ['comment' => '前置域名'])]
    private ?string $frontendDomain = null;

    #[Assert\Length(max: 120)]
    #[TrackColumn]
    #[ORM\Column(length: 120, unique: true, nullable: true, options: ['comment' => '唯一域名'])]
    private ?string $domainName = null;

    #[Assert\Length(max: 60)]
    #[TrackColumn]
    #[ORM\Column(length: 60, options: ['comment' => 'SSH主机'])]
    private ?string $sshHost = null;

    #[Assert\Range(min: 1, max: 65535)]
    #[TrackColumn]
    #[ORM\Column(options: ['comment' => 'SSH端口'])]
    private int $sshPort = 22;

    #[Assert\Length(max: 60)]
    #[TrackColumn]
    #[ORM\Column(length: 60, nullable: true, options: ['comment' => 'SSH用户名'])]
    private ?string $sshUser = null;

    #[Assert\Length(max: 120)]
    #[TrackColumn]
    #[ORM\Column(length: 120, nullable: true, options: ['comment' => 'SSH密码'])]
    private ?string $sshPassword = null;

    #[Assert\Type(type: 'string')]
    #[Assert\Length(max: 65535)]
    #[TrackColumn]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'SSH私钥'])]
    private ?string $sshPrivateKey = null;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d+$/')]
    #[Assert\Length(max: 20)]
    #[TrackColumn]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '总流量'])]
    private string $totalFlow = '0';

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d+$/')]
    #[Assert\Length(max: 20)]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '上传流量'])]
    private string $uploadFlow = '0';

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d+$/')]
    #[Assert\Length(max: 20)]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '下载流量'])]
    private string $downloadFlow = '0';

    #[Assert\Length(max: 120)]
    #[TrackColumn]
    #[ORM\Column(length: 120, nullable: true, options: ['comment' => '主机名'])]
    private ?string $hostname = null;

    #[Assert\Length(max: 100)]
    #[TrackColumn]
    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '虚拟化技术'])]
    private ?string $virtualizationTech = null;

    #[Assert\Length(max: 100)]
    #[TrackColumn]
    #[ORM\Column(length: 100, nullable: true, options: ['comment' => 'CPU型号'])]
    private ?string $cpuModel = null;

    #[Assert\Regex(pattern: '/^\d+(\.\d{1,3})?$/')]
    #[Assert\Length(max: 14)]
    #[TrackColumn]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 3, nullable: true, options: ['comment' => 'CPU最大频率'])]
    private ?string $cpuMaxFreq = null;

    #[Assert\PositiveOrZero]
    #[TrackColumn]
    #[ORM\Column(nullable: true, options: ['comment' => 'CPU核心数'])]
    private ?int $cpuCount = null;

    #[Assert\Length(max: 120)]
    #[TrackColumn]
    #[ORM\Column(length: 120, nullable: true, options: ['comment' => '系统版本'])]
    private ?string $systemVersion = null;

    #[Assert\Length(max: 100)]
    #[TrackColumn]
    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '内核版本'])]
    private ?string $kernelVersion = null;

    #[Assert\Length(max: 10)]
    #[TrackColumn]
    #[ORM\Column(length: 10, nullable: true, options: ['comment' => '系统架构'])]
    private ?string $systemArch = null;

    #[Assert\Length(max: 64)]
    #[TrackColumn]
    #[ORM\Column(length: 64, nullable: true, options: ['comment' => '系统UUID'])]
    private ?string $systemUuid = null;

    #[Assert\Length(max: 20)]
    #[TrackColumn]
    #[ORM\Column(length: 20, nullable: true, options: ['comment' => 'TCP拥塞控制'])]
    private ?string $tcpCongestionControl = null;

    #[Assert\Choice(callback: [NodeStatus::class, 'cases'])]
    #[ORM\Column(length: 40, nullable: true, enumType: NodeStatus::class, options: ['comment' => '状态'])]
    private ?NodeStatus $status = NodeStatus::INIT;

    /**
     * @var array<string>|null
     * @phpstan-ignore-next-line missingType.iterableValue
     */
    #[Assert\Type(type: 'array')]
    #[TrackColumn]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '标签列表'])]
    private ?array $tags = null;

    #[Assert\Length(max: 45)]
    #[TrackColumn]
    #[ORM\Column(length: 45, nullable: true, options: ['comment' => '在线IP'])]
    private ?string $onlineIp = null;

    #[Assert\Length(max: 64)]
    #[TrackColumn]
    #[ORM\Column(length: 64, unique: true, nullable: true, options: ['comment' => 'API密钥'])]
    private ?string $apiKey = null;

    #[Assert\Length(max: 64)]
    #[TrackColumn]
    #[ORM\Column(length: 64, nullable: true, options: ['comment' => 'API密钥'])]
    private ?string $apiSecret = null;

    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/')]
    #[Assert\Length(max: 13)]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['comment' => '入带宽'])]
    private ?string $rxBandwidth = null;

    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/')]
    #[Assert\Length(max: 13)]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['comment' => '出带宽'])]
    private ?string $txBandwidth = null;

    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/')]
    #[Assert\Length(max: 8)]
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true, options: ['comment' => '负载'])]
    private ?string $loadOneMinute = null;

    #[Assert\PositiveOrZero]
    #[ORM\Column(nullable: false, options: ['comment' => '在线数', 'default' => 0])]
    private int $userCount = 0;

    public function __construct()
    {
        $this->setApiKey('AK' . md5(uniqid()));
        $this->setApiSecret('SK' . md5(uniqid()));
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): void
    {
        $this->valid = $valid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCountry(): ?GBT_2659_2000
    {
        return $this->country;
    }

    public function setCountry(?GBT_2659_2000 $country): void
    {
        $this->country = $country;
    }

    public function getFrontendDomain(): ?string
    {
        return $this->frontendDomain;
    }

    public function setFrontendDomain(?string $frontendDomain): void
    {
        $this->frontendDomain = $frontendDomain;
    }

    public function getDomainName(): ?string
    {
        return $this->domainName;
    }

    public function setDomainName(?string $domainName): void
    {
        $this->domainName = $domainName;
    }

    public function getSshHost(): ?string
    {
        return $this->sshHost;
    }

    public function setSshHost(string $sshHost): void
    {
        $this->sshHost = $sshHost;
    }

    public function getSshPort(): int
    {
        return $this->sshPort;
    }

    public function setSshPort(int $sshPort): void
    {
        $this->sshPort = $sshPort;
    }

    public function getSshUser(): ?string
    {
        return $this->sshUser;
    }

    public function setSshUser(?string $sshUser): void
    {
        $this->sshUser = $sshUser;
    }

    public function getSshPassword(): ?string
    {
        return $this->sshPassword;
    }

    public function setSshPassword(?string $sshPassword): void
    {
        $this->sshPassword = $sshPassword;
    }

    public function getSshPrivateKey(): ?string
    {
        return $this->sshPrivateKey;
    }

    public function setSshPrivateKey(?string $sshPrivateKey): void
    {
        $this->sshPrivateKey = $sshPrivateKey;
    }

    public function getTotalFlow(): string
    {
        return $this->totalFlow;
    }

    public function setTotalFlow(string $totalFlow): void
    {
        $this->totalFlow = $totalFlow;
    }

    public function getUploadFlow(): string
    {
        return $this->uploadFlow;
    }

    public function setUploadFlow(string $uploadFlow): void
    {
        $this->uploadFlow = $uploadFlow;
    }

    public function getDownloadFlow(): string
    {
        return $this->downloadFlow;
    }

    public function setDownloadFlow(string $downloadFlow): void
    {
        $this->downloadFlow = $downloadFlow;
    }

    public function getHostname(): ?string
    {
        return $this->hostname;
    }

    public function setHostname(?string $hostname): void
    {
        $this->hostname = $hostname;
    }

    public function getVirtualizationTech(): ?string
    {
        return $this->virtualizationTech;
    }

    public function setVirtualizationTech(?string $virtualizationTech): void
    {
        $this->virtualizationTech = $virtualizationTech;
    }

    public function getCpuModel(): ?string
    {
        return $this->cpuModel;
    }

    public function setCpuModel(?string $cpuModel): void
    {
        $this->cpuModel = $cpuModel;
    }

    public function getCpuMaxFreq(): ?string
    {
        return $this->cpuMaxFreq;
    }

    public function setCpuMaxFreq(?string $cpuMaxFreq): void
    {
        $this->cpuMaxFreq = $cpuMaxFreq;
    }

    public function getCpuCount(): ?int
    {
        return $this->cpuCount;
    }

    public function setCpuCount(?int $cpuCount): void
    {
        $this->cpuCount = $cpuCount;
    }

    public function getSystemVersion(): ?string
    {
        return $this->systemVersion;
    }

    public function setSystemVersion(?string $systemVersion): void
    {
        $this->systemVersion = $systemVersion;
    }

    public function getKernelVersion(): ?string
    {
        return $this->kernelVersion;
    }

    public function setKernelVersion(?string $kernelVersion): void
    {
        $this->kernelVersion = $kernelVersion;
    }

    public function getSystemArch(): ?string
    {
        return $this->systemArch;
    }

    public function setSystemArch(?string $systemArch): void
    {
        $this->systemArch = $systemArch;
    }

    public function getSystemUuid(): ?string
    {
        return $this->systemUuid;
    }

    public function setSystemUuid(?string $systemUuid): void
    {
        $this->systemUuid = $systemUuid;
    }

    public function getTcpCongestionControl(): ?string
    {
        return $this->tcpCongestionControl;
    }

    public function setTcpCongestionControl(?string $tcpCongestionControl): void
    {
        $this->tcpCongestionControl = $tcpCongestionControl;
    }

    public function getStatus(): ?NodeStatus
    {
        return $this->status;
    }

    public function setStatus(?NodeStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * @return list<string>|null
     * @phpstan-return list<string>|null
     */
    public function getTags(): ?array
    {
        return null !== $this->tags ? array_values($this->tags) : null;
    }

    /**
     * @param list<string>|null $tags
     */
    public function setTags(?array $tags): void
    {
        $this->tags = null !== $tags ? array_values($tags) : null;
    }

    public function getOnlineIp(): ?string
    {
        return $this->onlineIp;
    }

    public function setOnlineIp(?string $onlineIp): void
    {
        $this->onlineIp = $onlineIp;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function getApiSecret(): ?string
    {
        return $this->apiSecret;
    }

    public function setApiSecret(?string $apiSecret): void
    {
        $this->apiSecret = $apiSecret;
    }

    public function getRxBandwidth(): ?string
    {
        return $this->rxBandwidth;
    }

    public function setRxBandwidth(?string $rxBandwidth): void
    {
        $this->rxBandwidth = $rxBandwidth;
    }

    public function getTxBandwidth(): ?string
    {
        return $this->txBandwidth;
    }

    public function setTxBandwidth(?string $txBandwidth): void
    {
        $this->txBandwidth = $txBandwidth;
    }

    public function getLoadOneMinute(): ?string
    {
        return $this->loadOneMinute;
    }

    public function setLoadOneMinute(?string $loadOneMinute): void
    {
        $this->loadOneMinute = $loadOneMinute;
    }

    public function getUserCount(): int
    {
        return $this->userCount;
    }

    public function setUserCount(int $userCount): void
    {
        $this->userCount = $userCount;
    }

    public function getAccessHost(): string
    {
        if (null !== $this->getDomainName()) {
            return $this->getDomainName();
        }

        return $this->getSshHost() ?? '';
    }

    public function __toString(): string
    {
        if (null === $this->getId()) {
            return '';
        }

        return $this->getName();
    }
}
