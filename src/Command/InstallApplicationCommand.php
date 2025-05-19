<?php

namespace ServerNodeBundle\Command;

use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use ServerNodeBundle\Repository\NodeRepository;
use ServerNodeBundle\Service\ApplicationTypeFetcher;
use ServerNodeBundle\Service\ShellOperator;
use ServerNodeBundle\SSH\SSHConnection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: InstallApplicationCommand::NAME, description: '为指定服务器安装应用')]
class InstallApplicationCommand extends Command
{
    const NAME = 'server-node:install-application';

    public function __construct(
        private readonly NodeRepository $nodeRepository,
        private readonly ApplicationTypeFetcher $typeFetcher,
        private readonly LoggerInterface $logger,
        private readonly ShellOperator $sshOperator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('nodeId', InputArgument::REQUIRED, '节点ID');
        $this->addArgument('type', InputArgument::OPTIONAL, '应用类型');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('nodeId');
        if ($id) {
            $nodes = $this->nodeRepository->findBy(['id' => $id, 'valid' => true]);
        } else {
            $nodes = $this->nodeRepository->findBy(['valid' => true]);
        }
        if (empty($nodes)) {
            throw new \Exception('找不到服务器');
        }

        $type = $input->getArgument('type');

        foreach ($nodes as $node) {
            foreach ($node->getApplications() as $application) {
                if ($type && $application->getType() !== $type) {
                    continue;
                }

                $output->writeln("开始部署 {$node} 的 {$application->getType()}服务:" . Carbon::now()->toDateTimeString());
                $component = $this->typeFetcher->getApplicationByCode($application->getType());
                try {
                    $component->install(
                        $application,
                        new SSHConnection($node, $this->logger, $this->sshOperator, $output),
                    );
                    $output->writeln("结束部署 {$node} 的 {$application->getType()}服务:" . Carbon::now()->toDateTimeString());
                } catch (\Throwable $exception) {
                    $this->logger->error('部署应用失败', [
                        'node' => $application->getNode(),
                        'application' => $application,
                        'exception' => $exception,
                    ]);
                }
            }
        }

        return Command::SUCCESS;
    }
}
