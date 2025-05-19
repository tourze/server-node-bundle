<?php

namespace ServerNodeBundle\Shell;

use phpseclib3\Net\SSH2;

interface ShellInterface
{
    /**
     * SHELL内容
     */
    public function getContent(): string;

    /**
     * 执行SSH命令
     */
    public function execute(SSH2 $ssh): string;
}
