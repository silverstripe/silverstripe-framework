<?php

namespace SilverStripe\Control\Tests\PolyCommandControllerTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\PolyExecution\PolyCommand;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class TestPolyCommand extends PolyCommand implements TestOnly
{
    protected static string $commandName = 'test:poly';

    protected static string $description = 'simple command for testing controller wrapper';

    protected static bool $canRunInBrowser = true;

    private int $exitCode = 0;

    public function getTitle(): string
    {
        return 'This is the title!';
    }

    public function run(InputInterface $input, PolyOutput $output): int
    {
        $output->writeln('Has option 1: ' . ($input->getOption('option1') ? 'true' : 'false'));
        $output->writeln('option 2 value: ' . $input->getOption('option2'));
        foreach ($input->getOption('option3') ?? [] as $value) {
            $output->writeln('option 3 value: ' . $value);
        }
        return $this->exitCode;
    }

    public function setExitCode(int $code): void
    {
        $this->exitCode = $code;
    }

    public function getOptions(): array
    {
        return [
            new InputOption('option1', null, InputOption::VALUE_NONE),
            new InputOption('option2', null, InputOption::VALUE_REQUIRED),
            new InputOption('option3', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED),
        ];
    }

    public static function canRunInBrowser(): bool
    {
        return static::$canRunInBrowser;
    }

    public static function setCanRunInBrowser(bool $canRun): void
    {
        static::$canRunInBrowser = $canRun;
    }
}
