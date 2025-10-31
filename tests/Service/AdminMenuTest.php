<?php

declare(strict_types=1);

namespace ServerNodeBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ServerNodeBundle\Entity\Node;
use ServerNodeBundle\Service\AdminMenu;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
    }

    protected function getMenuProviderClass(): string
    {
        return AdminMenu::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getExpectedMenuStructure(): array
    {
        return [
            '服务器管理' => [
                '服务器节点' => [
                    'icon' => 'fas fa-server',
                    'entity' => Node::class,
                ],
            ],
        ];
    }
}
