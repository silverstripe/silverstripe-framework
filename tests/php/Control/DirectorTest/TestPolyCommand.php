<?php

namespace SilverStripe\Control\Tests\DirectorTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\PolyExecution\PolyCommand;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\InputInterface;

class TestPolyCommand extends PolyCommand implements TestOnly
{
    protected static string $commandName = 'test:poly';

    protected static string $description = 'simple command for testing Director routing to PolyCommand';

    public function getTitle(): string
    {
        return 'This is the title!';
    }

    public function run(InputInterface $input, PolyOutput $output): int
    {
        $output->write('Successful poly command request!');
        return 0;
    }
}
