<?php

namespace ServerNodeBundle\Message;

use Tourze\Symfony\Async\Message\AsyncMessageInterface;

class NodeHealthCheckMessage implements AsyncMessageInterface
{
    private string $nodeId;

    public function getNodeId(): string
    {
        return $this->nodeId;
    }

    public function setNodeId(string $nodeId): void
    {
        $this->nodeId = $nodeId;
    }
}
