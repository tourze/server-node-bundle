<?php

declare(strict_types=1);

namespace ServerNodeBundle\Service;

use Knp\Menu\ItemInterface;
use ServerNodeBundle\Entity\Node;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(private LinkGeneratorInterface $linkGenerator)
    {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('服务器管理')) {
            $item->addChild('服务器管理');
        }

        $serverManagementMenu = $item->getChild('服务器管理');
        if (null !== $serverManagementMenu) {
            $serverManagementMenu
                ->addChild('服务器节点')
                ->setUri($this->linkGenerator->getCurdListPage(Node::class))
                ->setAttribute('icon', 'fas fa-server')
            ;
        }
    }
}
