<?php

namespace SilverStripe\Dev\Tests\TaskRunnerTest;

use SilverStripe\Dev\Tests\TaskRunnerTest\TaskRunnerTest_AbstractTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\InputInterface;

class TaskRunnerTest_ChildOfAbstractTask extends TaskRunnerTest_AbstractTask
{
    protected $enabled = true;

    protected function doRun(InputInterface $input, PolyOutput $output): int
    {
        return 0;
    }
}
