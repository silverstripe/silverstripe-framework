<?php

namespace SilverStripe\Cli\Tests\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Cli\Command\PolyCommandCliWrapper;
use SilverStripe\Cli\Tests\Command\PolyCommandCliWrapperTest\TestPolyCommand;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class PolyCommandCliWrapperTest extends SapphireTest
{
    protected $usesDatabase = false;

    public static function provideExecute(): array
    {
        return [
            'no-params' => [
                'exitCode' => 0,
                'params' => [],
                'expectedOutput' => 'Has option 1: false' . PHP_EOL
                    . 'option 2 value: ' . PHP_EOL,
            ],
            'with-params' => [
                'exitCode' => 1,
                'params' => [
                    '--option1' => true,
                    '--option2' => 'abc',
                ],
                'expectedOutput' => 'Has option 1: true' . PHP_EOL
                    . 'option 2 value: abc' . PHP_EOL,
            ],
        ];
    }

    #[DataProvider('provideExecute')]
    public function testExecute(int $exitCode, array $params, string $expectedOutput): void
    {
        $polyCommand = new TestPolyCommand();
        $polyCommand->setExitCode($exitCode);
        $wrapper = new PolyCommandCliWrapper($polyCommand);
        $input = new ArrayInput($params, $wrapper->getDefinition());
        $input->setInteractive(false);
        $buffer = new BufferedOutput();
        $output = new PolyOutput(PolyOutput::FORMAT_ANSI, decorated: false, wrappedOutput: $buffer);

        $this->assertSame($exitCode, $wrapper->run($input, $output));
        $this->assertSame($expectedOutput, $buffer->fetch());
    }
}
