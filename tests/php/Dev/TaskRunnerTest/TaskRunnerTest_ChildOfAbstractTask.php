<?php

namespace SilverStripe\Dev\Tests\TaskRunnerTest;

use SilverStripe\Dev\Tests\TaskRunnerTest\TaskRunnerTest_AbstractTask;

class TaskRunnerTest_ChildOfAbstractTask extends TaskRunnerTest_AbstractTask
{
    protected $enabled = true;

    public function run($request)
    {
        // NOOP
    }
}
