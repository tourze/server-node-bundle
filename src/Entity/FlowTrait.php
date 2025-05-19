<?php

namespace ServerNodeBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;

trait FlowTrait
{
    #[TrackColumn]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '总流量'])]
    private ?string $totalFlow = '0';

    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '上传流量'])]
    private ?string $uploadFlow = '0';

    #[ORM\Column(type: Types::BIGINT, options: ['comment' => '下载流量'])]
    private ?string $downloadFlow = '0';

    public function getTotalFlow(): ?string
    {
        return $this->totalFlow;
    }

    public function setTotalFlow(string $totalFlow): static
    {
        $this->totalFlow = $totalFlow;

        return $this;
    }

    public function getUploadFlow(): ?string
    {
        return $this->uploadFlow;
    }

    public function setUploadFlow(string $uploadFlow): static
    {
        $this->uploadFlow = $uploadFlow;

        return $this;
    }

    public function getDownloadFlow(): ?string
    {
        return $this->downloadFlow;
    }

    public function setDownloadFlow(string $downloadFlow): static
    {
        $this->downloadFlow = $downloadFlow;

        return $this;
    }

    public function retrieveFlowArray(): array
    {
        return [
            'totalFlow' => $this->getTotalFlow(),
            'uploadFlow' => $this->getUploadFlow(),
            'downloadFlow' => $this->getDownloadFlow(),
        ];
    }
}
