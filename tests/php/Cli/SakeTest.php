<?php

namespace SilverStripe\Cli\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use SilverStripe\Cli\Sake;
use SilverStripe\Cli\Tests\SakeTest\TestBuildTask;
use SilverStripe\Cli\Tests\SakeTest\TestCommandLoader;
use SilverStripe\Cli\Tests\SakeTest\TestConfigCommand;
use SilverStripe\Cli\Tests\SakeTest\TestConfigPolyCommand;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Core\Manifest\VersionProvider;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\DevelopmentAdmin;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\Tests\DeprecationTest\DeprecationTestException;
use Symfony\Component\Console\Command\DumpCompletionCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class SakeTest extends SapphireTest
{
    protected $usesDatabase = false;

    private $oldErrorHandler = null;

    public static function provideList(): array
    {
        return [
            'display all' => [
                'addExtra' => true,
                'hideCompletion' => true,
            ],
            'display none' => [
                'addExtra' => false,
                'hideCompletion' => false,
            ],
        ];
    }

    /**
     * Test adding commands and command loaders to Sake via configuration API
     */
    #[DataProvider('provideList')]
    public function testList(bool $addExtra, bool $hideCompletion): void
    {
        $sake = new Sake(Injector::inst()->get(Kernel::class));
        $sake->setAutoExit(false);
        $input = new ArrayInput(['list']);
        $input->setInteractive(false);
        $output = new BufferedOutput();

        if ($addExtra) {
            Sake::config()->merge('commands', [
                TestConfigPolyCommand::class,
                TestConfigCommand::class,
            ]);
            Sake::config()->merge('command_loaders', [
                TestCommandLoader::class,
            ]);
        }
        Sake::config()->set('hide_completion_command', $hideCompletion);
        // Make sure all tasks are displayed - we'll test hiding them in testHideTasks
        Sake::config()->set('max_tasks_to_display', 0);

        $sake->run($input, $output);

        $commandNames = [
            'loader:test-command',
            'test:from-config:standard',
            'test:from-config:poly',
        ];
        $commandDescriptions = [
            'command for testing adding custom command loaders',
            'command for testing adding standard commands via config',
            'command for testing adding poly commands via config',
        ];

        $listOutput = $output->fetch();

        // Check if the extra commands are there or not
        if ($addExtra) {
            foreach ($commandNames as $name) {
                $this->assertStringContainsString($name, $listOutput);
            }
            foreach ($commandDescriptions as $description) {
                $this->assertStringContainsString($description, $listOutput);
            }
        } else {
            foreach ($commandNames as $name) {
                $this->assertStringNotContainsString($name, $listOutput);
            }
            foreach ($commandDescriptions as $description) {
                $this->assertStringNotContainsString($description, $listOutput);
            }
        }

        // Build task could display automagically as a matter of class inheritance.
        $task = new TestBuildTask();
        $this->assertStringContainsString($task->getName(), $listOutput);
        $this->assertStringContainsString(TestBuildTask::getDescription(), $listOutput);

        // Check if the completion command is there or not
        $command = new DumpCompletionCommand();
        $completionRegex = "/{$command->getName()}\s+{$command->getDescription()}/";
        if ($hideCompletion) {
            $this->assertDoesNotMatchRegularExpression($completionRegex, $listOutput);
        } else {
            $this->assertMatchesRegularExpression($completionRegex, $listOutput);
        }

        // Make sure the "help" and "list" commands aren't shown
        $this->assertStringNotContainsString($listOutput, 'List commands', 'the list command should not display');
        $this->assertStringNotContainsString($listOutput, 'Display help for a command', 'the help command should not display');
    }

    public function testPolyCommandCanRunInCli(): void
    {
        $kernel = Injector::inst()->get(Kernel::class);
        $sake = new Sake($kernel);
        $sake->setAutoExit(false);
        $input = new ArrayInput(['list']);
        $input->setInteractive(false);
        $output = new BufferedOutput();

        // Add test commands
        Sake::config()->merge('commands', [
            TestConfigPolyCommand::class,
        ]);

        // Disallow these to run in CLI.
        // Note the scenario where all are allowed is in testList().
        TestConfigPolyCommand::config()->set('can_run_in_cli', false);
        TestBuildTask::config()->set('can_run_in_cli', false);
        DevelopmentAdmin::config()->set('allow_all_cli', false);

        // Must not be in dev mode to test permissions, because all PolyCommand can be run in dev mode.
        $origEnvironment = $kernel->getEnvironment();
        $kernel->setEnvironment('live');
        try {
            $sake->run($input, $output);
        } finally {
            $kernel->setEnvironment($origEnvironment);
        }
        $listOutput = $output->fetch();

        $allCommands = [
            TestConfigPolyCommand::class,
            TestBuildTask::class,
        ];
        foreach ($allCommands as $commandClass) {
            $command = new $commandClass();
            $this->assertStringNotContainsString($command->getName(), $listOutput);
            $this->assertStringNotContainsString($commandClass::getDescription(), $listOutput);
        }
    }

    public static function provideHideTasks(): array
    {
        return [
            'task count matches limit' => [
                'taskLimit' => 'same',
                'shouldShow' => true,
            ],
            'task count lower than limit' => [
                'taskLimit' => 'more',
                'shouldShow' => true,
            ],
            'task count greater than limit' => [
                'taskLimit' => 'less',
                'shouldShow' => false,
            ],
            'unlimited tasks allowed' => [
                'taskLimit' => 'all',
                'shouldShow' => true,
            ],
        ];
    }

    #[DataProvider('provideHideTasks')]
    public function testHideTasks(string $taskLimit, bool $shouldShow): void
    {
        $sake = new Sake(Injector::inst()->get(Kernel::class));
        $sake->setAutoExit(false);
        $input = new ArrayInput(['list']);
        $input->setInteractive(false);
        $output = new BufferedOutput();

        // Determine max tasks config value
        $taskInfo = [];
        foreach (ClassInfo::subclassesFor(BuildTask::class, false) as $class) {
            $reflectionClass = new ReflectionClass($class);
            if ($reflectionClass->isAbstract()) {
                continue;
            }
            $singleton = $class::singleton();
            if ($class::canRunInCli() && $singleton->isEnabled()) {
                $taskInfo[$singleton->getName()] = $class::getDescription();
            }
        }
        $maxTasks = match ($taskLimit) {
            'same' => count($taskInfo),
            'more' => count($taskInfo) + 1,
            'less' => count($taskInfo) - 1,
            'all' => 0,
        };

        Sake::config()->set('max_tasks_to_display', $maxTasks);
        $sake->run($input, $output);
        $listOutput = $output->fetch();

        // Check the tasks are showing/hidden as appropriate
        if ($shouldShow) {
            foreach ($taskInfo as $name => $description) {
                $this->assertStringContainsString($name, $listOutput);
                $this->assertStringContainsString($description, $listOutput);
            }
            // Shouldn't display the task command
            $this->assertStringNotContainsString('See a list of build tasks to run', $listOutput);
        } else {
            foreach ($taskInfo as $name => $description) {
                $this->assertStringNotContainsString($name, $listOutput);
                $this->assertStringNotContainsString($description, $listOutput);
            }
            // Should display the task command
            $this->assertStringContainsString('See a list of build tasks to run', $listOutput);
        }

        // Check `sake tasks` ALWAYS shows the tasks
        $input = new ArrayInput(['tasks']);
        $sake->run($input, $output);
        $listOutput = $output->fetch();
        foreach ($taskInfo as $name => $description) {
            $this->assertStringContainsString($name, $listOutput);
            $this->assertStringContainsString($description, $listOutput);
        }
    }

    public function testVersion(): void
    {
        $sake = new Sake(Injector::inst()->get(Kernel::class));
        $sake->setAutoExit(false);
        $versionProvider = new VersionProvider();
        $this->assertSame($versionProvider->getVersion(), $sake->getVersion());
    }

    public function testLegacyDevCommands(): void
    {
        $sake = new Sake(Injector::inst()->get(Kernel::class));
        $sake->setAutoExit(false);
        $input = new ArrayInput(['dev/config']);
        $input->setInteractive(false);
        $output = new BufferedOutput();

        $deprecationsWereEnabled = Deprecation::isEnabled();
        Deprecation::enable();
        $this->expectException(DeprecationTestException::class);
        $expectedErrorString = 'Using the command with the name \'dev/config\' is deprecated. Use \'config:dump\' instead';
        $this->expectExceptionMessage($expectedErrorString);

        $exitCode = $sake->run($input, $output);
        $this->assertSame(0, $exitCode, 'command should run successfully');
        // $this->assertStringContainsString('abababa', $output->fetch());

        $this->allowCatchingDeprecations($expectedErrorString);
        try {
            // call outputNotices() directly because the regular shutdown function that emits
            // the notices within Deprecation won't be called until after this unit-test has finished
            Deprecation::outputNotices();
        } finally {
            restore_error_handler();
            $this->oldErrorHandler = null;
            // Disable if they weren't enabled before.
            if (!$deprecationsWereEnabled) {
                Deprecation::disable();
            }
        }
    }

    private function allowCatchingDeprecations(string $expectedErrorString): void
    {
        // Use custom error handler for two reasons:
        // - Filter out errors for deprecations unrelated to this test class
        // - Allow the use of expectDeprecation(), which doesn't work with E_USER_DEPRECATION by default
        //   https://github.com/laminas/laminas-di/pull/30#issuecomment-927585210
        $this->oldErrorHandler = set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use ($expectedErrorString) {
            if ($errno === E_USER_DEPRECATED) {
                if (str_contains($errstr, $expectedErrorString)) {
                    throw new DeprecationTestException($errstr);
                } else {
                    // Suppress any E_USER_DEPRECATED unrelated to this test class
                    return true;
                }
            }
            if (is_callable($this->oldErrorHandler)) {
                return call_user_func($this->oldErrorHandler, $errno, $errstr, $errfile, $errline);
            }
            // Fallback to default PHP error handler
            return false;
        });
    }
}
