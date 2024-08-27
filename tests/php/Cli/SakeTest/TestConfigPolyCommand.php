<?php

namespace SilverStripe\Cli\Tests\SakeTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\PolyExecution\PolyCommand;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\InputInterface;

class TestConfigPolyCommand extends PolyCommand implements TestOnly
{
    protected static string $commandName = 'test:from-config:poly';

    protected static string $description = 'command for testing adding poly commands via config';

    public function getTitle(): string
    {
        return 'This is a poly command';
    }

    public function run(InputInterface $input, PolyOutput $output): int
    {
        $output->writeln('This output is coming from a poly command');
        return 0;
    }
}
