<?php

namespace ServerNodeBundle\SSH;

use phpseclib3\Exception\ConnectionClosedException;
use phpseclib3\Net\SFTP;
use Psr\Log\LoggerInterface;
use SebastianBergmann\Timer\Timer;
use ServerNodeBundle\Entity\Node;
use ServerNodeBundle\Exception\SshLoginFailedException;
use Symfony\Component\Console\Output\OutputInterface;

class SSHConnection
{
    private SFTP $ssh;

    public function __construct(
        private readonly Node $node,
        private readonly LoggerInterface $logger,
        private readonly ?OutputInterface $output = null,
    ) {
        $this->createSsh();
    }

    private function createSsh(): void
    {
        $ssh = self::createTunnel($this->node);
        if (false === $ssh) {
            throw new SshLoginFailedException('Could not create SSH connection');
        }
        $this->ssh = $ssh;
    }

    /**
     * 获取节点的ssh实例
     */
    public static function createTunnel(Node $node): SFTP|false
    {
        $ssh = new SFTP($node->getSshHost(), $node->getSshPort());
        if (!$ssh->login($node->getSshUser(), $node->getSshPassword())) {
            return false;
        }

        if ('root' !== $node->getSshUser()) {
            // 执行切换到root账号的命令
            $ssh->write("sudo su -\n"); // 使用sudo切换到root账号
            $ssh->read('password for'); // 读取sudo密码提示
            $ssh->write("{$node->getSshPassword()}\n"); // 输入sudo密码
            $ssh->read('root@'); // 读取root账号提示符
        }

        $ssh->setTimeout(0);
        $ssh->disableStatCache();

        return $ssh;
    }

    /**
     * 默认权限执行命令
     */
    public function exec(string $command, int $retryTimes = 5): mixed
    {
        $this->output?->writeln("开始执行命令:{$command}");

        $timer = new Timer();
        $timer->start();

        try {
            $result = $this->ssh->exec($command);
            $duration = $timer->stop();
            $this->logger->info('执行命令成功', [
                'node' => $this->node,
                'command' => $command,
                'result' => $result,
                'duration' => $duration->asString(),
            ]);
            $this->output?->writeln("执行命令成功:{$result}");
        } catch (ConnectionClosedException $exception) {
            // No data received from server
            $this->logger->error('SSH连接报错', [
                'exception' => $exception,
                'node' => $this->node,
            ]);
            if ($retryTimes > 0) {
                // 重新连接一次
                $this->createSsh();

                return $this->exec($command, $retryTimes - 1);
            }
            throw $exception;
        }

        return $result;
    }

    /**
     * SUDO执行命令
     */
    public function sudoExec(string $command, string $pwd = null): mixed
    {
        $this->output?->writeln("开始sudo执行命令:{$command}");

        $timer = new Timer();
        $timer->start();

        if ($pwd !== null) {
            $this->exec("cd {$pwd}");
        }

        $result = null;
        try {
            if ('root' === $this->node->getSshUser()) {
                $result = $this->exec($command);
            } else {
                if ($this->node->getSshPassword()) {
                    $result = $this->exec("echo '{$this->node->getSshPassword()}' | sudo -S {$command}");
                } else {
                    // TODO 使用证书登录的话，我们要怎么切换到root？
                    $result = $this->exec("sudo -S {$command}");
                }
            }
        } finally {
            $duration = $timer->stop();
            $this->logger->info('sudo执行命令成功', [
                'node' => $this->node,
                'command' => $command,
                'result' => $result,
                'duration' => $duration->asString(),
            ]);
            $this->output?->writeln("sudo执行命令结果:{$result}");
        }

        return $result;
    }

    public function createDirectory(string $directory): void
    {
        $this->sudoExec("mkdir -p {$directory}");
        $this->sudoExec("chown -R {$this->node->getSshUser()}:{$this->node->getSshUser()} {$directory}");
    }

    public function fileExists(string $path): bool
    {
        if (!$this->ssh->file_exists($path)) {
            return false;
        }

        // 如果是zip的话，我们校验一下文件完整性
        if (str_ends_with($path, '.zip')) {
            $res = $this->exec("unzip -t {$path}");
            if (!str_contains($res, 'No errors detected')) {
                $this->sudoExec("rm -rf {$path}");

                return false;
            }
        }

        return true;
    }

    public function downloadFile(string $url, string $path, $maxTry = 3): bool
    {
        if ($this->fileExists($path)) {
            // 看看是否为空
            $size = $this->exec("wc -c < {$path}");
            $size = intval($size);
            if (0 === $size) {
                $this->sudoExec("rm -rf {$path}");
            } else {
                return true;
            }
        }

        if (!str_starts_with($path, '/tmp')) {
            $dirname = dirname($path);
            $this->sudoExec("mkdir -p {$dirname}");
        }

        do {
            if ($maxTry <= 0) {
                return false;
            }

            $this->sudoExec("wget --no-check-certificate {$url} -O {$path}");
            --$maxTry;
        } while (!$this->fileExists($path));

        return true;
    }

    private function listFiles($directory)
    {
        $directory = rtrim($directory, '/');
        $fileList = [];

        if (is_dir($directory)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($files as $file) {
                if ($file->isFile()) {
                    $fileList[] = mb_substr($file->getPathname(), mb_strlen($directory) + 1);
                }
            }
        }

        $this->output?->writeln($directory);
        $this->output?->writeln(var_export($fileList, true));

        return $fileList;
    }

    /**
     * 同步本地文件夹到远程
     */
    public function syncDirectory(string $localDir, string $remoteDir, int $retryTimes = 3): void
    {
        $this->output?->writeln("同步本地目录[{$localDir}]到远程[{$remoteDir}]");

        // 先打包本地文件
        // 因为是临时目录，所以本地和远程一个名字都无所谓
        $localFile = '/tmp/ssh_file_' . uniqid() . '.tar.gz';
        $parentDir = dirname($localDir);
        $dirName = basename($localDir);

        $this->createDirectory($remoteDir);

        try {
            shell_exec("tar -czvf {$localFile} -C {$parentDir} $dirName");

            $sum2 = strtolower(md5_file($localFile));

            $finished = false;
            while (!$finished) {
                // 上传文件可能失败，我们要有重试
                try {
                    $this->ssh->put($localFile, file_get_contents($localFile));
                    if ($this->ssh->file_exists($localFile)) {
                        $sum1 = $this->exec("md5sum {$localFile} | awk '{ print \$1 }'");
                        $sum1 = trim($sum1);
                        if ($sum1 === $sum2) {
                            $finished = true;
                        } else {
                            $this->output?->writeln("{$localFile}上传成功，但是md5sum不匹配, {$sum1} / {$sum2}");
                            $this->ssh->delete($localFile);
                        }
                    }
                } catch (\RuntimeException $exception) {
                    if ($retryTimes <= 0) {
                        throw $exception;
                    }
                    --$retryTimes;
                    $this->output?->writeln('同步文件失败，重试中：' . $retryTimes);
                }
            }

            // 解压啦
            $this->sudoExec("tar -xzvf {$localFile} -C {$remoteDir} --strip-components=1");
            $this->sudoExec("rm -rf {$localFile}");
        } finally {
            @unlink($localFile);
        }
    }

    public function uploadFile(string $localFile, string $remoteFile): bool
    {
        // 如果文件在远程已经存在，那么我们判断下是否跟本地的md5sum一致，一致的话我们跳过
        if ($this->ssh->file_exists($remoteFile)) {
            $sum1 = $this->exec("md5sum {$remoteFile} | awk '{ print \$1 }'");
            $sum1 = strtolower(trim($sum1));
            $this->logger->info("远程文件[{$remoteFile}]MD5值：{$sum1}");
            $sum2 = strtolower(md5_file($localFile));
            $this->logger->info("本地文件[{$localFile}]MD5值：{$sum1}");
            if ($sum1 === $sum2) {
                return true;
            }
        }

        $content = file_get_contents($localFile);

        return $this->writeFile($remoteFile, $content);
    }

    public function writeFile(string $remoteFile, string $content): bool
    {
        $dirName = dirname($remoteFile);
        if (!$this->ssh->is_dir($dirName)) {
            $this->sudoExec("mkdir -p {$dirName}");
            $this->sudoExec("chown -R {$this->node->getSshUser()}:{$this->node->getSshUser()} {$dirName}");
        }
        $this->sudoExec("rm -rf {$remoteFile}");

        return $this->ssh->put($remoteFile, $content);
    }

    /**
     * 批量执行shell命令
     */
    public function batchShellExec(string $remoteDir, array $shells): void
    {
        foreach ($shells as $shell) {
            $shell = realpath($shell);
            if (!is_file($shell)) {
                continue;
            }
            $hash = md5($shell);
            $name = pathinfo($shell, PATHINFO_FILENAME);
            $remoteFile = "{$remoteDir}/{$name}-{$hash}.sh";
            try {
                $this->uploadFile($shell, $remoteFile);
                $this->sudoExec("bash {$remoteFile}");
            } finally {
                $this->ssh->delete($remoteFile);
            }
        }
    }
}
