<?php

namespace SilverStripe\Dev\Tests\TaskRunnerTest;

use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\InputInterface;

abstract class TaskRunnerTest_AbstractTask extends BuildTask
{
    protected $enabled = true;

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        return 0;
    }
}
