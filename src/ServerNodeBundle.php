<?php

namespace ServerNodeBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;

class ServerNodeBundle extends Bundle implements BundleDependencyInterface
{
    const COMMAND_INSTALL_APPLICATION = 'server-node:install-application';

    public static function getBundleDependencies(): array
    {
        return [
            \Tourze\TempFileBundle\TempFileBundle::class => ['all' => true],
        ];
    }
}
