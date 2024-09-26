<?php

namespace SilverStripe\PolyExecution\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\PolyExecution\HttpRequestInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HttpRequestInputTest extends SapphireTest
{
    protected $usesDatabase = false;

    public static function provideInputOptions(): array
    {
        return [
            'no vars, no options' => [
                'requestVars' => [],
                'commandOptions' => [],
                'expected' => [],
            ],
            'some vars, no options' => [
                'requestVars' => [
                    'var1' => '1',
                    'var2' => 'abcd',
                    'var3' => null,
                    'var4' => ['a', 'b', 'c'],
                ],
                'commandOptions' => [],
                'expected' => [],
            ],
            'no vars, some options' => [
                'requestVars' => [],
                'commandOptions' => [
                    new InputOption('var1', null, InputOption::VALUE_NEGATABLE),
                    new InputOption('var2', null, InputOption::VALUE_REQUIRED),
                    new InputOption('var3', null, InputOption::VALUE_OPTIONAL),
                    new InputOption('var4', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED),
                ],
                'expected' => [
                    'var1' => null,
                    'var2' => null,
                    'var3' => null,
                    'var4' => [],
                ],
            ],
            'no vars, some options (with default values)' => [
                'requestVars' => [],
                'commandOptions' => [
                    new InputOption('var1', null, InputOption::VALUE_NEGATABLE, default: true),
                    new InputOption('var2', null, InputOption::VALUE_REQUIRED, default: 'def'),
                    new InputOption('var3', null, InputOption::VALUE_OPTIONAL, default: false),
                    new InputOption('var4', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, default: [1, 2, 'banana']),
                ],
                'expected' => [
                    'var1' => true,
                    'var2' => 'def',
                    'var3' => false,
                    'var4' => [1, 2, 'banana'],
                ],
            ],
            'some vars and options' => [
                'requestVars' => [
                    'var1' => '1',
                    'var2' => 'abcd',
                    'var3' => 2,
                    'var4' => ['a', 'b', 'c'],
                ],
                'commandOptions' => [
                    new InputOption('var1', null, InputOption::VALUE_NEGATABLE),
                    new InputOption('var2', null, InputOption::VALUE_REQUIRED),
                    new InputOption('var3', null, InputOption::VALUE_OPTIONAL),
                    new InputOption('var4', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED),
                ],
                'expected' => [
                    'var1' => true,
                    'var2' => 'abcd',
                    'var3' => 2,
                    'var4' => ['a', 'b', 'c'],
                ],
            ],
        ];
    }

    #[DataProvider('provideInputOptions')]
    public function testInputOptions(array $requestVars, array $commandOptions, array $expected): void
    {
        $request = new HTTPRequest('GET', 'arbitrary-url', $requestVars);
        $input = new HttpRequestInput($request, $commandOptions);

        foreach ($expected as $option => $value) {
            $this->assertSame($value, $input->getOption($option), 'checking value for ' . $option);
        }

        // If there's no expected values, the success metric is that we didn't throw any exceptions.
        if (empty($expected)) {
            $this->expectNotToPerformAssertions();
        }
    }

    public static function provideGetVerbosity(): array
    {
        return [
            'default to normal' => [
                'requestVars' => [],
                'expected' => OutputInterface::VERBOSITY_NORMAL,
            ],
            'shortcuts are ignored' => [
                'requestVars' => ['v' => 1],
                'expected' => OutputInterface::VERBOSITY_NORMAL,
            ],
            '?verbose=1 is verbose' => [
                'requestVars' => ['verbose' => 1],
                'expected' => OutputInterface::VERBOSITY_VERBOSE,
            ],
            '?verbose=2 is very verbose' => [
                'requestVars' => ['verbose' => 2],
                'expected' => OutputInterface::VERBOSITY_VERY_VERBOSE,
            ],
            '?verbose=3 is debug' => [
                // Check string works as well as int
                'requestVars' => ['verbose' => '3'],
                'expected' => OutputInterface::VERBOSITY_DEBUG,
            ],
            '?quiet=1 is quiet' => [
                'requestVars' => ['quiet' => 1],
                'expected' => OutputInterface::VERBOSITY_QUIET,
            ],
        ];
    }

    #[DataProvider('provideGetVerbosity')]
    public function testGetVerbosity(array $requestVars, int $expected): void
    {
        $request = new HTTPRequest('GET', 'arbitrary-url', $requestVars);
        $input = new HttpRequestInput($request);
        $this->assertSame($expected, $input->getVerbosity());
    }
}
