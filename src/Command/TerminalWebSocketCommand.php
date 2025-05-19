<?php

namespace ServerNodeBundle\Command;

use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;
use ServerNodeBundle\Repository\NodeRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Timer;
use Workerman\Worker;

#[AsCommand(name: 'server-node:terminal-websocket')]
class TerminalWebSocketCommand extends Command
{
    private \WeakMap $timerMap;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly NodeRepository $nodeRepository,
    ) {
        parent::__construct();
        $this->timerMap = new \WeakMap();
    }

    protected function configure(): void
    {
        $this->addArgument('type', InputArgument::REQUIRED, 'Workerman命令');
        $this->addOption('daemon', 'd');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Worker::$pidFile = $this->kernel->getProjectDir() . '/server-ws.pid';
        Worker::$logFile = $this->kernel->getProjectDir() . '/server-ws.log';

        $transport = null;
        $context = [];
        if ($_ENV['SERVER_NODE_WS_CERT'] ?? '') {
            $context['ssl'] = [
                // 请使用绝对路径
                'local_cert' => $_ENV['SERVER_NODE_WS_CERT'], // 也可以是crt文件
                'local_pk' => $_ENV['SERVER_NODE_WS_PK'],
                'verify_peer' => false,
                'allow_self_signed' => true, // 如果是自签名证书需要开启此选项
            ];
            $transport = 'ssl';
        }

        $ws = new Worker('websocket://0.0.0.0:' . $_ENV['SERVER_NODE_WS_PORT'], $context);
        if (null !== $transport) {
            $ws->transport = 'ssl';
        }
        $ws->onConnect = function (TcpConnection $connection) {
            echo "TCP Connection: {$connection->id}\n";
            $connection->onWebSocketConnect = function (TcpConnection $connection, $httpBuffer) {
                // 可以在这里判断连接来源是否合法，不合法就关掉连接
                // $_SERVER['HTTP_ORIGIN']标识来自哪个站点的页面发起的websocket连接
                // if ('https://www.workerman.net' != $_SERVER['HTTP_ORIGIN']) {
                //    $connection->close();
                // }
                // onWebSocketConnect 里面$_GET $_SERVER是可用的
                var_dump($_GET);
                $node = $this->nodeRepository->find($_GET['nodeId']);
                if (!$node) {
                    $connection->close();
                    echo "找不到节点信息\n";

                    return;
                }

                $ssh = new SFTP($node->getSshHost(), $node->getSshPort());
                if (!$ssh->login($node->getSshUser(), $node->getSshPassword())) {
                    $connection->close();
                    echo "[{$node->getId()}]SSH连接失败\n";

                    return;
                }

                $ssh->setTimeout(3);
                while (!empty($data = $ssh->read())) {
                    // echo "发送给浏览器：{$data}\n";
                    $connection->send($data);
                }

                $ssh->setTimeout(0.2);
                $connection->ssh = $ssh;
                $this->timerMap->offsetSet($connection, Timer::add(0.1, function () use ($ssh, $connection) {
                    while (!empty($data = $ssh->read())) {
                        // echo "发送给浏览器：{$data}\n";
                        $connection->send($data);
                    }
                }));
                echo "连接成功\n";
            };
        };

        $ws->onMessage = function (TcpConnection $connection, string $data) {
            // echo "收到消息：" . $data . "\n";
            if ('close' === $data) {
                $connection->close();

                return;
            }

            if (!isset($connection->ssh)) {
                return;
            }
            $ssh = $connection->ssh;
            /* @var SSH2 $ssh */
            $ssh->write($data);
        };

        $ws->onClose = function (TcpConnection $connection) {
            // 定时器去除
            $timerId = $this->timerMap->offsetExists($connection) ? $this->timerMap->offsetGet($connection) : null;
            if ($timerId) {
                Timer::del($timerId);
            }

            if (!isset($connection->ssh)) {
                return;
            }
            $ssh = $connection->ssh;
            /* @var SSH2 $ssh */
            $ssh->disconnect();
        };

        $ws->onError = function (TcpConnection $connection) {
            // 定时器去除
            $timerId = $this->timerMap->offsetExists($connection) ? $this->timerMap->offsetGet($connection) : null;
            if ($timerId) {
                Timer::del($timerId);
            }

            if (!isset($connection->ssh)) {
                return;
            }
            $ssh = $connection->ssh;
            /* @var SSH2 $ssh */
            $ssh->disconnect();
        };

        Worker::runAll();

        return Command::SUCCESS;
    }
}
