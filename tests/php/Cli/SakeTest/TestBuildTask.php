<?php

namespace SilverStripe\Cli\Tests\SakeTest;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Dev\TestOnly;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\InputInterface;

class TestBuildTask extends BuildTask implements TestOnly
{
    protected static string $commandName = 'test-build-task';

    protected string $title = 'my title';

    protected static string $description = 'command for testing build tasks display as expected';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $output->writeln('This output is coming from a build task');
        return 0;
    }
}
