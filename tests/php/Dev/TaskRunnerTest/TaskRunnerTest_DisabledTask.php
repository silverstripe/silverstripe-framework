<?php declare(strict_types = 1);

namespace SilverStripe\Dev\Tests\TaskRunnerTest;

use SilverStripe\Dev\BuildTask;

class TaskRunnerTest_DisabledTask extends BuildTask
{
    protected $enabled = false;

    public function run($request)
    {
        // NOOP
    }
}
