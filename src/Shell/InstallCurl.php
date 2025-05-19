<?php

namespace ServerNodeBundle\Shell;

use phpseclib3\Net\SSH2;

class InstallCurl implements ShellInterface
{
    public function getContent(): string
    {
        return file_get_contents(__DIR__ . '/InstallCurl.sh');
    }

    public function execute(SSH2 $ssh): string
    {
        // TODO: Implement execute() method.
    }
}
