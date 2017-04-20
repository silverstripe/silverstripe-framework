<?php

namespace SilverStripe\Dev\Tests\TaskRunnerTest;

use SilverStripe\Dev\BuildTask;

abstract class TaskRunnerTest_AbstractTask extends BuildTask
{
    protected $enabled = true;

    public function run($request)
    {
        // NOOP
    }
}
