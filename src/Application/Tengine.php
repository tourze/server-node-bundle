<?php

namespace ServerNodeBundle\Application;

use Carbon\CarbonInterface;
use ServerNodeBundle\Entity\Application;
use ServerNodeBundle\Service\PortChecker;
use ServerNodeBundle\Service\ShellOperator;
use ServerNodeBundle\SSH\SSHConnection;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Tourze\EnumExtra\SelectDataFetcher;

/**
 * Tengine Web服务器（阿里巴巴开源的Nginx分支）
 */
#[AutoconfigureTag('application.type.provider')]
class Tengine implements ApplicationInterface, SelectDataFetcher
{
    public const CODE = 'tengine';
    private const TENGINE_VERSION = '2.3.3'; // 当前最新稳定版

    public function __construct(
        private readonly PortChecker $portChecker,
        private readonly ShellOperator $sshOperator,
    ) {
    }

    public function getCode(): string
    {
        return self::CODE;
    }

    public function getLabel(): string
    {
        return 'Tengine Web服务器';
    }

    public function getDefaultPort(): ?int
    {
        return 80;
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
        $tengineDir = "{$remoteDir}/tengine";

        // 创建远程目录
        $ssh->createDirectory($remoteDir);
        $ssh->createDirectory($tengineDir);

        // 安装依赖
        $distro = $this->sshOperator->getDistro($ssh->getSsh());

        if ($distro === 'ubuntu' || $distro === 'debian') {
            $ssh->sudoExec('apt-get update');
            $ssh->sudoExec('apt-get install -y build-essential git libpcre3 libpcre3-dev zlib1g-dev openssl libssl-dev');
        } elseif ($distro === 'centos' || $distro === 'rhel' || $distro === 'fedora') {
            $ssh->sudoExec('yum install -y gcc gcc-c++ git pcre pcre-devel zlib zlib-devel openssl openssl-devel');
        } else {
            // 尝试通用方法
            $ssh->sudoExec('apt-get update && apt-get install -y build-essential git libpcre3 libpcre3-dev zlib1g-dev openssl libssl-dev || yum install -y gcc gcc-c++ git pcre pcre-devel zlib zlib-devel openssl openssl-devel');
        }

        // 下载Tengine源码
        $tengineArchive = "{$remoteDir}/tengine-" . self::TENGINE_VERSION . ".tar.gz";
        $ssh->downloadFile("https://github.com/alibaba/tengine/archive/" . self::TENGINE_VERSION . ".tar.gz", $tengineArchive);
        
        // 解压和编译
        $ssh->sudoExec("tar xzf {$tengineArchive} -C {$remoteDir}");
        $ssh->sudoExec("cd {$remoteDir}/tengine-" . self::TENGINE_VERSION . " && ./configure --prefix=/usr/local/tengine --with-http_ssl_module && make && make install");
        
        // 创建systemd服务文件
        $serviceContent = <<<EOT
[Unit]
Description=Tengine HTTP Server
After=network.target

[Service]
Type=forking
PIDFile=/usr/local/tengine/logs/nginx.pid
ExecStartPre=/usr/local/tengine/sbin/nginx -t
ExecStart=/usr/local/tengine/sbin/nginx
ExecReload=/usr/local/tengine/sbin/nginx -s reload
ExecStop=/usr/local/tengine/sbin/nginx -s stop
KillSignal=SIGQUIT
TimeoutStopSec=5
KillMode=process
PrivateTmp=true

[Install]
WantedBy=multi-user.target
EOT;

        // 写入服务文件
        $ssh->writeFile('/etc/systemd/system/tengine.service', $serviceContent);

        // 修改端口配置
        if ($application->getPort() !== 80) {
            $configPath = "/usr/local/tengine/conf/nginx.conf";
            $ssh->sudoExec("sed -i 's/listen[[:space:]]*80/listen {$application->getPort()}/g' {$configPath}");
        }

        // 启动服务
        $ssh->sudoExec('systemctl daemon-reload');
        $ssh->sudoExec('systemctl enable tengine');
        $ssh->sudoExec('systemctl start tengine');

        return true;
    }

    public function collectStats(Application $application, CarbonInterface $time, array $post): void
    {
        // 收集Tengine状态信息
        // 这里可以实现更复杂的数据收集逻辑，如连接数、请求数等
        $application->setActiveTime($time);
        $application->setOnline(true);
    }

    public function healthCheck(Application $application, CarbonInterface $time): ?bool
    {
        return $this->portChecker->tcpCheck($application->getNode()->getAccessHost(), $application->getPort());
    }
}
