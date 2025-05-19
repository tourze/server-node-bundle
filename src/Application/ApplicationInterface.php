<?php

namespace ServerNodeBundle\Application;

use Carbon\CarbonInterface;
use ServerNodeBundle\Entity\Application;
use ServerNodeBundle\SSH\SSHConnection;

interface ApplicationInterface
{
    /**
     * 唯一编码
     */
    public function getCode(): string;

    /**
     * 标题
     */
    public function getLabel(): string;

    /**
     * 默认端口
     */
    public function getDefaultPort(): ?int;

    /**
     * 安装服务时执行
     */
    public function install(Application $application, SSHConnection $ssh): bool;

    /**
     * 收集运行情况
     */
    public function collectStats(Application $application, CarbonInterface $time, array $post): void;

    /**
     * 健康检查
     * 返回null的话，代表不需要进行健康检查
     */
    public function healthCheck(Application $application, CarbonInterface $time): ?bool;
}
