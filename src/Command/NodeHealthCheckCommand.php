<?php

namespace ServerNodeBundle\Command;

use ServerNodeBundle\Message\NodeHealthCheckMessage;
use ServerNodeBundle\Repository\NodeRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Tourze\Symfony\CronJob\Attribute\AsCronTask;

#[AsCronTask('* * * * *')]
#[AsCommand(name: NodeHealthCheckCommand::NAME, description: '定时检查服务器状态')]
class NodeHealthCheckCommand extends Command
{
    public const NAME = 'server-node:health-check';

    public function __construct(
        private readonly NodeRepository $nodeRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->nodeRepository->findBy(['valid' => true]) as $node) {
            $message = new NodeHealthCheckMessage();
            $message->setNodeId($node->getId());
            $this->messageBus->dispatch($message);
        }

        return Command::SUCCESS;
    }
}
