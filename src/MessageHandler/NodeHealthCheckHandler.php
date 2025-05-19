<?php

namespace ServerNodeBundle\MessageHandler;

use Carbon\Carbon;
use ServerNodeBundle\Enum\NodeStatus;
use ServerNodeBundle\Message\NodeHealthCheckMessage;
use ServerNodeBundle\Repository\ApplicationRepository;
use ServerNodeBundle\Repository\NodeRepository;
use ServerNodeBundle\Service\ApplicationTypeFetcher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class NodeHealthCheckHandler
{
    public function __construct(
        private readonly NodeRepository $nodeRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly ApplicationTypeFetcher $typeFetcher,
    ) {
    }

    public function __invoke(NodeHealthCheckMessage $message): void
    {
        $node = $this->nodeRepository->find($message->getNodeId());
        if (!$node) {
            throw new UnrecoverableMessageHandlingException('找不到节点信息');
        }

        $now = Carbon::now();
        foreach ($node->getApplications() as $application) {
            $component = $this->typeFetcher->getApplicationByCode($application->getType());
            $res = $component->healthCheck($application, $now);
            if (null === $res) {
                $application->setOnline(null);
                $this->applicationRepository->save($application);
                continue;
            }
            $application->setOnline($res);
            $this->applicationRepository->save($application);
        }

        // 如果一个节点，所有有状态的服务都是正常的，那么他就是正常的
        $serviceCount = 0;
        $onlineCount = 0;
        foreach ($node->getApplications() as $application) {
            if (null !== $application->isOnline()) {
                ++$serviceCount;
                if ($application->isOnline()) {
                    ++$onlineCount;
                }
            }
        }
        if (0 === $serviceCount) {
            $node->setStatus(NodeStatus::MAINTAIN);
        } else {
            if ($serviceCount === $onlineCount) {
                $node->setStatus(NodeStatus::ONLINE);
            } else {
                $node->setStatus(NodeStatus::OFFLINE);
            }
        }
        $this->nodeRepository->save($node);
    }
}
