<?php

namespace SilverStripe\Dev\Tests\DevAdminControllerTest;

use SilverStripe\Dev\Command\DevCommand;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\InputInterface;

class TestCommand extends DevCommand
{
    const OK_MSG = 'DevAdminControllerTest_TestCommand TEST OK';

    protected static string $commandName = 'my-test-command';

    protected static string $description = 'my test command';

    public function getTitle(): string
    {
        return 'Test command';
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $output->write(TestCommand::OK_MSG);
        return 0;
    }

    protected function getHeading(): string
    {
        return 'This is a test command';
    }
}
