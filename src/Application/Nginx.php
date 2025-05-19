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
 * Nginx Web服务器
 */
#[AutoconfigureTag('application.type.provider')]
class Nginx implements ApplicationInterface, SelectDataFetcher
{
    public const CODE = 'nginx';

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
        return 'Nginx Web服务器';
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

        // 创建远程目录
        $ssh->createDirectory($remoteDir);

        // 安装基础环境
        $ssh->batchShellExec($remoteDir, [
            __DIR__ . '/../../shell/basic.sh',
        ]);

        // 安装Nginx
        $distro = $this->sshOperator->getDistro($ssh->getSsh());

        if ($distro === 'ubuntu' || $distro === 'debian') {
            $ssh->sudoExec('apt-get update');
            $ssh->sudoExec('apt-get install -y nginx');
        } elseif ($distro === 'centos' || $distro === 'rhel' || $distro === 'fedora') {
            $ssh->sudoExec('yum install -y epel-release');
            $ssh->sudoExec('yum install -y nginx');
        } else {
            // 尝试通用方法
            $ssh->sudoExec('apt-get update && apt-get install -y nginx || yum install -y nginx');
        }

        // 配置Nginx端口
        $configPath = "/etc/nginx/sites-available/default";
        if (!$ssh->fileExists($configPath)) {
            $configPath = "/etc/nginx/conf.d/default.conf";
        }
        if (!$ssh->fileExists($configPath)) {
            $configPath = "/etc/nginx/nginx.conf";
        }

        // 修改Nginx配置
        if ($application->getPort() !== 80) {
            $ssh->sudoExec("sed -i 's/listen 80/listen {$application->getPort()}/g' {$configPath}");
        }

        // 启动Nginx服务
        $ssh->sudoExec('systemctl enable nginx');
        $ssh->sudoExec('systemctl restart nginx');

        return true;
    }

    public function collectStats(Application $application, CarbonInterface $time, array $post): void
    {
        // 收集Nginx状态信息
        // 这里可以实现更复杂的数据收集逻辑，如连接数、请求数等
        $application->setActiveTime($time);
        $application->setOnline(true);
    }

    public function healthCheck(Application $application, CarbonInterface $time): ?bool
    {
        return $this->portChecker->tcpCheck($application->getNode()->getAccessHost(), $application->getPort());
    }
}
