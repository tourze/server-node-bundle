<?php

namespace ServerNodeBundle\Application;

use Carbon\CarbonInterface;
use ServerNodeBundle\Entity\Application;
use ServerNodeBundle\Service\PortChecker;
use ServerNodeBundle\SSH\SSHConnection;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Tourze\EnumExtra\SelectDataFetcher;

/**
 * Redis缓存服务器
 */
#[AutoconfigureTag('application.type.provider')]
class Redis implements ApplicationInterface, SelectDataFetcher
{
    public const CODE = 'redis';
    private const REDIS_VERSION = '7.0.15'; // 稳定版本

    public function __construct(
        private readonly PortChecker $portChecker,
    ) {
    }

    public function getCode(): string
    {
        return self::CODE;
    }

    public function getLabel(): string
    {
        return 'Redis缓存服务器';
    }

    public function getDefaultPort(): ?int
    {
        return 6379;
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

    public function install(Application $application, SSHConnection $ssh): bool
    {
        if ($application->getPort() <= 0) {
            throw new \RuntimeException('找不到启动端口');
        }

        $node = $application->getNode();
        $country = $node->getCountry()->toLowerCase();
        $remoteDir = "/data/{$country}_{$node->getId()}";
        $redisDir = "{$remoteDir}/redis";

        // 创建远程目录
        $ssh->createDirectory($remoteDir);
        $ssh->createDirectory($redisDir);

        // 安装依赖
        $distro = $ssh->exec('cat /etc/os-release | grep "^ID=" | cut -d= -f2 | tr -d \'"\' | tr -d "\n"');

        if ($distro === 'ubuntu' || $distro === 'debian') {
            $ssh->sudoExec('apt-get update');
            $ssh->sudoExec('apt-get install -y build-essential tcl');
        } elseif ($distro === 'centos' || $distro === 'rhel' || $distro === 'fedora') {
            $ssh->sudoExec('yum install -y gcc make tcl');
        } else {
            // 尝试通用方法
            $ssh->sudoExec('apt-get update && apt-get install -y build-essential tcl || yum install -y gcc make tcl');
        }

        // 下载Redis源码
        $redisArchive = "{$remoteDir}/redis-" . self::REDIS_VERSION . ".tar.gz";
        $ssh->downloadFile("http://download.redis.io/releases/redis-" . self::REDIS_VERSION . ".tar.gz", $redisArchive);

        // 解压和编译
        $ssh->sudoExec("tar xzf {$redisArchive} -C {$remoteDir}");
        $ssh->sudoExec("cd {$remoteDir}/redis-" . self::REDIS_VERSION . " && make && make install");
        
        // 创建必要的目录
        $ssh->sudoExec("mkdir -p /etc/redis");
        $ssh->sudoExec("mkdir -p /var/lib/redis");
        
        // 复制配置文件
        $ssh->sudoExec("cp {$remoteDir}/redis-" . self::REDIS_VERSION . "/redis.conf /etc/redis/");
        
        // 修改Redis配置
        $ssh->sudoExec("sed -i 's/^supervised no/supervised systemd/g' /etc/redis/redis.conf");
        $ssh->sudoExec("sed -i 's/^dir \.\//dir \/var\/lib\/redis/g' /etc/redis/redis.conf");
        
        // 如果端口不是默认的，修改端口配置
        if ($application->getPort() !== 6379) {
            $ssh->sudoExec("sed -i 's/^port 6379/port {$application->getPort()}/g' /etc/redis/redis.conf");
        }
        
        // 创建systemd服务文件
        $serviceContent = <<<EOT
[Unit]
Description=Redis In-Memory Data Store
After=network.target

[Service]
Type=notify
ExecStart=/usr/local/bin/redis-server /etc/redis/redis.conf
ExecStop=/usr/local/bin/redis-cli shutdown
Restart=always

[Install]
WantedBy=multi-user.target
EOT;

        // 写入服务文件
        $ssh->writeFile('/etc/systemd/system/redis.service', $serviceContent);

        // 启动服务
        $ssh->sudoExec('systemctl daemon-reload');
        $ssh->sudoExec('systemctl enable redis');
        $ssh->sudoExec('systemctl start redis');

        return true;
    }

    public function collectStats(Application $application, CarbonInterface $time, array $post): void
    {
        // 收集Redis状态信息
        // 可以采集连接数、内存使用情况、命令执行次数等
        $application->setActiveTime($time);
        $application->setOnline(true);
    }

    public function healthCheck(Application $application, CarbonInterface $time): ?bool
    {
        return $this->portChecker->tcpCheck($application->getNode()->getAccessHost(), $application->getPort());
    }
}
