<?php

namespace SilverStripe\Cli\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Cli\LegacyParamArgvInput;
use SilverStripe\Dev\SapphireTest;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class LegacyParamArgvInputTest extends SapphireTest
{
    protected $usesDatabase = false;

    public static function provideHasParameterOption(): array
    {
        return [
            'sake flush=1' => [
                'argv' => [
                    'sake',
                    'flush=1'
                ],
                'checkFor' => '--flush',
                'expected' => true,
            ],
            'sake flush=0' => [
                'argv' => [
                    'sake',
                    'flush=0'
                ],
                'checkFor' => '--flush',
                'expected' => true,
            ],
            'sake flush=1 --' => [
                'argv' => [
                    'sake',
                    'flush=1',
                    '--'
                ],
                'checkFor' => '--flush',
                'expected' => true,
            ],
            'sake -- flush=1' => [
                'argv' => [
                    'sake',
                    '--',
                    'flush=1'
                ],
                'checkFor' => '--flush',
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideHasParameterOption')]
    public function testHasParameterOption(array $argv, string $checkFor, bool $expected): void
    {
        $input = new LegacyParamArgvInput($argv);
        $this->assertSame($expected, $input->hasParameterOption($checkFor));
    }

    public static function provideGetParameterOption(): array
    {
        $scenarios = static::provideHasParameterOption();
        $scenarios['sake flush=1']['expected'] = '1';
        $scenarios['sake flush=0']['expected'] = '0';
        $scenarios['sake flush=1 --']['expected'] = '1';
        $scenarios['sake -- flush=1']['expected'] = false;
        return $scenarios;
    }

    #[DataProvider('provideGetParameterOption')]
    public function testGetParameterOption(array $argv, string $checkFor, false|string $expected): void
    {
        $input = new LegacyParamArgvInput($argv);
        $this->assertSame($expected, $input->getParameterOption($checkFor));
    }

    public static function provideBind(): array
    {
        return [
            'sake flush=1 arg=value' => [
                'argv' => [
                    'sake',
                    'flush=1',
                    'arg=value',
                ],
                'options' => [
                    new InputOption('--flush', null, InputOption::VALUE_NONE),
                    new InputOption('--arg', null, InputOption::VALUE_REQUIRED),
                ],
                'expected' => [
                    'flush' => true,
                    'arg' => 'value',
                ],
            ],
            'sake flush=yes arg=abc' => [
                'argv' => [
                    'sake',
                    'flush=yes',
                    'arg=abc',
                ],
                'options' => [
                    new InputOption('flush', null, InputOption::VALUE_NONE),
                    new InputOption('arg', null, InputOption::VALUE_OPTIONAL),
                ],
                'expected' => [
                    'flush' => true,
                    'arg' => 'abc',
                ],
            ],
            'sake flush=0 arg=' => [
                'argv' => [
                    'sake',
                    'flush=0',
                    'arg=',
                ],
                'options' => [
                    new InputOption('flush', null, InputOption::VALUE_NONE),
                    new InputOption('arg', null, InputOption::VALUE_OPTIONAL),
                ],
                'expected' => [
                    'flush' => false,
                    'arg' => null,
                ],
            ],
            'sake flush=1 -- arg=abc' => [
                'argv' => [
                    'sake',
                    'flush=1',
                    '--',
                    'arg=abc',
                ],
                'options' => [
                    new InputOption('flush', null, InputOption::VALUE_NONE),
                    new InputOption('arg', null, InputOption::VALUE_OPTIONAL),
                    // Since arg=abc is now included as an argument, we need to allow an argument.
                    new InputArgument('needed-to-avoid-error', InputArgument::REQUIRED),
                ],
                'expected' => [
                    'flush' => true,
                    'arg' => null,
                ],
            ],
        ];
    }

    #[DataProvider('provideBind')]
    public function testBind(array $argv, array $options, array $expected): void
    {
        $input = new LegacyParamArgvInput($argv);
        $definition = new InputDefinition($options);
        $input->bind($definition);
        foreach ($expected as $option => $value) {
            $this->assertSame($value, $input->getOption($option));
        }
    }
}
