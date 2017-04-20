<?php

namespace SilverStripe\Dev\Tests\TaskRunnerTest;

use SilverStripe\Dev\BuildTask;

class TaskRunnerTest_EnabledTask extends BuildTask
{
    protected $enabled = true;

    public function run($request)
    {
        // NOOP
    }
}
