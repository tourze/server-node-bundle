<?php

namespace ServerNodeBundle\Service;

use Knp\Menu\ItemInterface;
use ServerNodeBundle\Entity\Node;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

#[Autoconfigure(public: true)]
class AdminMenu implements MenuProviderInterface
{
    public function __construct(private readonly LinkGeneratorInterface $linkGenerator)
    {
    }

    public function __invoke(ItemInterface $item): void
    {
        if ($item->getChild('服务器管理') === null) {
            $item->addChild('服务器管理');
        }

        $item->getChild('服务器管理')
            ->addChild('服务器节点')
            ->setUri($this->linkGenerator->getCurdListPage(Node::class))
            ->setAttribute('icon', 'fas fa-server');
    }
}
