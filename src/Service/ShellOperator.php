<?php

namespace ServerNodeBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use phpseclib3\Net\SSH2;
use Psr\Log\LoggerInterface;
use SebastianBergmann\Timer\Timer;

#[WithMonologChannel('ssh')]
class ShellOperator
{
    public function __construct(
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * 获取系统发行版
     */
    public function getDistro(SSH2 $ssh): string
    {
        $distro = $this->exec($ssh, 'cat /etc/os-release | grep "^ID=" | cut -d= -f2 | tr -d \'"\' | tr -d "\n"');
        return trim($distro);
    }

    /**
     * 默认权限执行命令
     */
    public function exec(SSH2 $ssh, string $command): mixed
    {
        $this->logger->info("开始执行命令:{$command}");

        $timer = new Timer();
        $timer->start();

        $result = $ssh->exec($command);
        $duration = $timer->stop();
        $this->logger->info('执行命令成功', [
            'command' => $command,
            'result' => $result,
            'duration' => $duration->asString(),
        ]);
        $this->logger->info("执行命令成功:{$result}");

        return $result;
    }
}
