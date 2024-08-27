<?php

namespace SilverStripe\Dev\Tests\TaskRunnerTest;

use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\InputInterface;

class TaskRunnerTest_DisabledTask extends BuildTask
{
    private static bool $is_enabled = false;

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        return 0;
    }
}
