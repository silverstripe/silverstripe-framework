<?php

namespace SilverStripe\Dev\Tests\BuildTaskTest;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Dev\TestOnly;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\ORM\FieldType\DBDatetime;
use Symfony\Component\Console\Input\InputInterface;

class TestBuildTask extends BuildTask implements TestOnly
{
    protected static string $commandName = 'test-build-task';

    protected string $title = 'my title';

    protected static string $description = 'command for testing build tasks display as expected';

    public string $setTimeTo;

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        DBDatetime::set_mock_now($this->setTimeTo);
        $output->writeln('This output is coming from a build task');
        return 0;
    }
}
