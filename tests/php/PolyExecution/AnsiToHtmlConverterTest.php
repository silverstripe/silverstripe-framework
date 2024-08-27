<?php

namespace SilverStripe\PolyExecution\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\PolyExecution\AnsiToHtmlConverter;
use Symfony\Component\Console\Formatter\OutputFormatter;

class AnsiToHtmlConverterTest extends SapphireTest
{
    protected $usesDatabase = false;

    public static function provideConvert(): array
    {
        return [
            'no text, no result' => [
                'unformatted' => '',
                'expected' => '',
            ],
            'no empty span' => [
                'unformatted' => 'This text <info></info> is unformatted',
                'expected' => 'This text  is unformatted',
            ],
            'named formats are converted' => [
                'unformatted' => 'This text <info>has some</info> formatting',
                'expected' => 'This text <span style="color: green">has some</span> formatting',
            ],
            'fg and bg are converted' => [
                'unformatted' => 'This text <fg=red;bg=blue>has some</> formatting',
                'expected' => 'This text <span style="background-color: blue; color: darkred">has some</span> formatting',
            ],
            'bold and underscore are converted' => [
                'unformatted' => 'This text <options=bold;options=underscore>has some</> formatting',
                'expected' => 'This text <span style="font-weight: bold; text-decoration: underline">has some</span> formatting',
            ],
            'multiple styles are converted' => [
                'unformatted' => 'This text <options=bold;fg=green>has some</> <comment>formatting</comment>',
                'expected' => 'This text <span style="font-weight: bold; color: green">has some</span> <span style="color: goldenrod">formatting</span>',
            ],
            'hyperlinks are converted' => [
                'unformatted' => 'This text <href=https://www.example.com/>has a</> link',
                'expected' => 'This text <a href="https://www.example.com/">has a</a> link',
            ],
        ];
    }

    #[DataProvider('provideConvert')]
    public function testConvert(string $unformatted, string $expected): void
    {
        $converter = new AnsiToHtmlConverter();
        $ansiFormatter = new OutputFormatter(true);
        $formatted = $ansiFormatter->format($unformatted);

        $this->assertSame($expected, $converter->convert($formatted));
    }
}
