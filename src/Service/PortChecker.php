<?php

namespace ServerNodeBundle\Service;

class PortChecker
{
    public function tcpCheck(string $ip, int $port, int $timeout = 5): bool
    {
        $connection = @fsockopen($ip, $port, $errno, $errstr, $timeout);
        if (is_resource($connection)) {
            // 端口是开放的
            fclose($connection); // 关闭连接

            return true;
        }

        // 端口是关闭的或不可访问
        return false;
    }
}
