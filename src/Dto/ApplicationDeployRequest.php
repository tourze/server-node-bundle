<?php

namespace ServerNodeBundle\Dto;

class ApplicationDeployRequest
{
    public function __construct(
        private readonly string $nodeId,
        private readonly ?string $applicationId = null,
        private readonly bool $deployAll = false,
    ) {
    }

    public function getNodeId(): string
    {
        return $this->nodeId;
    }

    public function getApplicationId(): ?string
    {
        return $this->applicationId;
    }

    public function isDeployAll(): bool
    {
        return $this->deployAll;
    }
}
