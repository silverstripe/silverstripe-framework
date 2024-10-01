<?php

namespace SilverStripe\Cli\Tests\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Cli\Command\NavigateCommand;
use SilverStripe\Cli\Tests\Command\NavigateCommandTest\TestController;
use SilverStripe\Control\Director;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class NavigateCommandTest extends SapphireTest
{
    protected $usesDatabase = true;

    public static function provideExecute(): array
    {
        return [
            [
                'path' => 'missing-route',
                'getVars' => [],
                'expectedExitCode' => 2,
                'expectedOutput' => '',
            ],
            [
                'path' => 'test-controller',
                'getVars' => [],
                'expectedExitCode' => 0,
                'expectedOutput' => 'This is the index for TestController.' . PHP_EOL,
            ],
            [
                'path' => 'test-controller/actionOne',
                'getVars' => [],
                'expectedExitCode' => 0,
                'expectedOutput' => 'This is action one!' . PHP_EOL,
            ],
            [
                'path' => 'test-controller/errorResponse',
                'getVars' => [],
                'expectedExitCode' => 1,
                'expectedOutput' => '',
            ],
            [
                'path' => 'test-controller/missing-action',
                'getVars' => [],
                'expectedExitCode' => 2,
                'expectedOutput' => '',
            ],
            [
                'path' => 'test-controller',
                'getVars' => [
                    'var1=1',
                    'var2=abcd',
                    'var3=',
                    'var4[]=a',
                    'var4[]=b',
                    'var4[]=c',
                ],
                'expectedExitCode' => 0,
                'expectedOutput' => 'This is the index for TestController. var1=1 var2=abcd var4=a,b,c' . PHP_EOL,
            ],
            [
                'path' => 'test-controller',
                'getVars' => [
                    'var1=1&var2=abcd&var3=&var4[]=a&var4[]=b&var4[]=c',
                ],
                'expectedExitCode' => 0,
                'expectedOutput' => 'This is the index for TestController. var1=1 var2=abcd var4=a,b,c' . PHP_EOL,
            ],
        ];
    }

    #[DataProvider('provideExecute')]
    public function testExecute(string $path, array $getVars, int $expectedExitCode, string $expectedOutput): void
    {
        // Intentionally override existing rules
        Director::config()->set('rules', ['test-controller' => TestController::class]);
        $navigateCommand = new NavigateCommand();
        $inputParams = [
            'path' => $path,
            'get-vars' => $getVars,
        ];
        $input = new ArrayInput($inputParams, $navigateCommand->getDefinition());
        $input->setInteractive(false);
        $buffer = new BufferedOutput();
        $output = new PolyOutput(PolyOutput::FORMAT_ANSI, decorated: false, wrappedOutput: $buffer);

        $exitCode = $navigateCommand->run($input, $output);

        // Don't asset specific output for failed or invalid responses
        // The response body for those is handled outside of the navigate command's control
        if ($expectedExitCode === 0) {
            $this->assertSame($expectedOutput, $buffer->fetch());
        }
        $this->assertSame($expectedExitCode, $exitCode);
    }
}
