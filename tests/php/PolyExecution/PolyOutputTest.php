<?php

namespace SilverStripe\PolyExecution\Tests;

use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Output\BufferedOutput;

class PolyOutputTest extends SapphireTest
{
    protected $usesDatabase = false;

    public static function provideWriteForHtml(): array
    {
        return [
            'html for html' => [
                'outputFormat' => PolyOutput::FORMAT_HTML,
                'messages' => ['one message', 'two message'],
                'expected' => "one message<br>\ntwo message<br>\n",
            ],
            'ansi for html' => [
                'outputFormat' => PolyOutput::FORMAT_ANSI,
                'messages' => ['one message', 'two message'],
                'expected' => '',
            ],
        ];
    }

    #[DataProvider('provideWriteForHtml')]
    public function testWriteForHtml(
        string $outputFormat,
        string|iterable $messages,
        string $expected
    ): void {
        $buffer = new BufferedOutput();
        $output = new PolyOutput($outputFormat, wrappedOutput: $buffer);
        $output->writeForHtml($messages, true);
        $this->assertSame($expected, $buffer->fetch());
    }

    public static function provideWriteForAnsi(): array
    {
        return [
            'html for ansi' => [
                'outputFormat' => PolyOutput::FORMAT_HTML,
                'messages' => ['one message', 'two message'],
                'expected' => '',
            ],
            'ansi for ansi' => [
                'outputFormat' => PolyOutput::FORMAT_ANSI,
                'messages' => ['one message', 'two message'],
                'expected' => "one message\ntwo message\n",
            ],
        ];
    }

    #[DataProvider('provideWriteForAnsi')]
    public function testWriteForAnsi(
        string $outputFormat,
        string|iterable $messages,
        string $expected
    ): void {
        $buffer = new BufferedOutput();
        $output = new PolyOutput($outputFormat, wrappedOutput: $buffer);
        $output->writeForAnsi($messages, true);
        $this->assertSame($expected, $buffer->fetch());
    }

    public static function provideList(): array
    {
        return [
            'empty list ANSI' => [
                'outputFormat' => PolyOutput::FORMAT_ANSI,
                'list' => [
                    'type' => PolyOutput::LIST_UNORDERED,
                    'items' => []
                ],
                'expected' => '',
            ],
            'empty list HTML' => [
                'outputFormat' => PolyOutput::FORMAT_HTML,
                'list' => [
                    'type' => PolyOutput::LIST_UNORDERED,
                    'items' => []
                ],
                'expected' => '<ul></ul>',
            ],
            'single list UL ANSI' => [
                'outputFormat' => PolyOutput::FORMAT_ANSI,
                'list' => [
                    'type' => PolyOutput::LIST_UNORDERED,
                    'items' => ['item 1', 'item 2']
                ],
                'expected' => <<< EOL
                 * item 1
                 * item 2

                EOL,
            ],
            'single list OL ANSI' => [
                'outputFormat' => PolyOutput::FORMAT_ANSI,
                'list' => [
                    'type' => PolyOutput::LIST_ORDERED,
                    'items' => ['item 1', 'item 2']
                ],
                'expected' => <<< EOL
                 1. item 1
                 2. item 2

                EOL,
            ],
            'single list UL HTML' => [
                'outputFormat' => PolyOutput::FORMAT_HTML,
                'list' => [
                    'type' => PolyOutput::LIST_UNORDERED,
                    'items' => ['item 1', 'item 2']
                ],
                'expected' => '<ul><li>item 1</li><li>item 2</li></ul>',
            ],
            'single list OL HTML' => [
                'outputFormat' => PolyOutput::FORMAT_HTML,
                'list' => [
                    'type' => PolyOutput::LIST_ORDERED,
                    'items' => ['item 1', 'item 2']
                ],
                'expected' => '<ol><li>item 1</li><li>item 2</li></ol>',
            ],
            'nested list ANSI' => [
                'outputFormat' => PolyOutput::FORMAT_ANSI,
                'list' => [
                    'type' => PolyOutput::LIST_UNORDERED,
                    'items' => [
                        'item 1',
                        'item 2',
                        [
                            'type' => PolyOutput::LIST_ORDERED,
                            'items' => [
                                'item 2a',
                                ['item 2b','item 2c'],
                                'item 2d',
                            ]
                        ],
                        'item 3',
                    ]
                ],
                'expected' => <<< EOL
                 * item 1
                 * item 2
                  1. item 2a
                  2. item 2b
                  3. item 2c
                  4. item 2d
                 * item 3

                EOL,
            ],
            'nested list HTML' => [
                'outputFormat' => PolyOutput::FORMAT_HTML,
                'list' => [
                    'type' => PolyOutput::LIST_UNORDERED,
                    'items' => [
                        'item 1',
                        'item 2',
                        'list' => [
                            'type' => PolyOutput::LIST_ORDERED,
                            'items' => [
                                'item 2a',
                                ['item 2b','item 2c'],
                                'item 2d',
                            ]
                        ],
                        'item 3',
                    ]
                ],
                'expected' => '<ul><li>item 1</li><li>item 2</li><ol><li>item 2a</li><li>item 2b</li><li>item 2c</li><li>item 2d</li></ol><li>item 3</li></ul>',
            ],
        ];
    }

    #[DataProvider('provideList')]
    public function testList(string $outputFormat, array $list, string $expected): void
    {
        $buffer = new BufferedOutput();
        $output = new PolyOutput($outputFormat, wrappedOutput: $buffer);
        $this->makeListRecursive($output, $list);
        $this->assertSame($expected, $buffer->fetch());
    }

    public static function provideListMustBeStarted(): array
    {
        return [
            [PolyOutput::FORMAT_ANSI],
            [PolyOutput::FORMAT_HTML],
        ];
    }

    #[DataProvider('provideListMustBeStarted')]
    public function testListMustBeStarted(string $outputFormat): void
    {
        $output = new PolyOutput($outputFormat);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No lists started. Call startList() first.');
        $output->writeListItem('');
    }

    private function makeListRecursive(PolyOutput $output, array $list): void
    {
        $output->startList($list['type']);
        foreach ($list['items'] as $item) {
            if (isset($item['type'])) {
                $this->makeListRecursive($output, $item);
                continue;
            }
            $output->writeListItem($item);
        }
        $output->stopList();
    }
}
