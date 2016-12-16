<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\TaskRunner;
use SilverStripe\Dev\SapphireTest;
use ReflectionMethod;

class TaskRunnerTest extends SapphireTest
{

    public function testTaskEnabled()
    {
        $runner = new TaskRunner();
        $method = new ReflectionMethod($runner, 'taskEnabled');
        $method->setAccessible(true);

        $this->assertTrue(
            $method->invoke($runner, TaskRunnerTest\TaskRunnerTest_EnabledTask::class),
            'Enabled task incorrectly marked as disabled'
        );
        $this->assertFalse(
            $method->invoke($runner, TaskRunnerTest\TaskRunnerTest_DisabledTask::class),
            'Disabled task incorrectly marked as enabled'
        );
        $this->assertFalse(
            $method->invoke($runner, TaskRunnerTest\TaskRunnerTest_AbstractTask::class),
            'Disabled task incorrectly marked as enabled'
        );
        $this->assertTrue(
            $method->invoke($runner, TaskRunnerTest\TaskRunnerTest_ChildOfAbstractTask::class),
            'Enabled task incorrectly marked as disabled'
        );
    }
}
