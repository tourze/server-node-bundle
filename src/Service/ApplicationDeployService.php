<?php

namespace ServerNodeBundle\Service;

use Psr\Log\LoggerInterface;
use ServerNodeBundle\Command\InstallApplicationCommand;
use ServerNodeBundle\Dto\ApplicationDeployRequest;
use ServerNodeBundle\Entity\Application;
use ServerNodeBundle\Entity\Node;
use ServerNodeBundle\Repository\ApplicationRepository;
use ServerNodeBundle\Repository\NodeRepository;
use Symfony\Component\Messenger\MessageBusInterface;
use Tourze\Symfony\Async\Message\RunCommandMessage;

class ApplicationDeployService
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly NodeRepository $nodeRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 部署应用
     */
    public function deploy(ApplicationDeployRequest $request): void
    {
        $node = $this->nodeRepository->find($request->getNodeId());
        if (!$node) {
            throw new \InvalidArgumentException('节点不存在');
        }

        if ($request->isDeployAll()) {
            $this->deployAllApplications($node);
            return;
        }

        if ($request->getApplicationId()) {
            $application = $this->applicationRepository->find($request->getApplicationId());
            if (!$application) {
                throw new \InvalidArgumentException('应用不存在');
            }
            $this->deployApplication($node, $application);
            return;
        }

        throw new \InvalidArgumentException('无效的部署请求');
    }

    /**
     * 部署单个应用
     */
    private function deployApplication(Node $node, Application $application): void
    {
        try {
            $this->logger->info('开始部署应用', [
                'node' => $node->getId(),
                'application' => $application->getId(),
            ]);

            $message = new RunCommandMessage();
            $message->setCommand(InstallApplicationCommand::NAME);
            $message->setOptions([
                'node' => $node->getId(),
                'application' => $application->getId(),
            ]);
            $this->messageBus->dispatch($message);

            $this->logger->info('应用部署任务已提交', [
                'node' => $node->getId(),
                'application' => $application->getId(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('应用部署失败', [
                'node' => $node->getId(),
                'application' => $application->getId(),
                'error' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * 部署节点的所有应用
     */
    private function deployAllApplications(Node $node): void
    {
        try {
            $this->logger->info('开始部署所有应用', [
                'node' => $node->getId(),
            ]);

            foreach ($node->getApplications() as $application) {
                $this->deployApplication($node, $application);
            }

            $this->logger->info('所有应用部署任务已提交', [
                'node' => $node->getId(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('部署所有应用失败', [
                'node' => $node->getId(),
                'error' => $e,
            ]);
            throw $e;
        }
    }
}
