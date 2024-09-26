<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\Tests\BuildTaskTest\TestBuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\ORM\FieldType\DBDatetime;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class BuildTaskTest extends SapphireTest
{
    public function testRunOutput(): void
    {
        DBDatetime::set_mock_now('2024-01-01 12:00:00');
        $task = new TestBuildTask();
        $task->setTimeTo = '2024-01-01 12:00:15';
        $buffer = new BufferedOutput();
        $output = new PolyOutput(PolyOutput::FORMAT_ANSI, wrappedOutput: $buffer);
        $input = new ArrayInput([]);
        $input->setInteractive(false);

        $task->run($input, $output);

        $this->assertSame("Running task 'my title'\nThis output is coming from a build task\n\nTask 'my title' completed successfully in 15 seconds\n", $buffer->fetch());
    }
}
