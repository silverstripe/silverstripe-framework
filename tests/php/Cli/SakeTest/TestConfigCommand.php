<?php

namespace SilverStripe\Cli\Tests\SakeTest;

use SilverStripe\Dev\TestOnly;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('test:from-config:standard', 'command for testing adding standard commands via config')]
class TestConfigCommand extends Command implements TestOnly
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return 'This is a standard command';
        return 0;
    }
}
