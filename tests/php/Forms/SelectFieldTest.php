<?php

namespace SilverStripe\Forms\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\SelectField;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\ArrayData;

class SelectFieldTest extends SapphireTest
{
    public static function provideCastSubmittedValue(): array
    {
        return [
            'int-keys' => [
                'source' => [1 => 'cat', 2 => 'dog'],
                'value' => '1',
                'expected' => 1,
            ],
            'string-int-keys' => [
                // note that string int are converted to int by PHP
                'source' => ['1' => 'cat', '2' => 'dog'],
                'value' => '1',
                'expected' => 1,
            ],
            'negative-int-keys' => [
                'source' => [-1 => 'cat', -2 => 'dog'],
                'value' => '-2',
                'expected' => -2,
            ],
            'string-negative-int-keys' => [
                // note that string int are converted to int by PHP
                'source' => ['-1' => 'cat', '-2' => 'dog'],
                'value' => '-2',
                'expected' => -2
            ],
            'string-keys' => [
                'source' => ['cat' => 'cat', 'dog' => 'dog'],
                'value' => 'cat',
                'expected' => 'cat',
            ],
            'mixed-keys' => [
                'source' => [1 => 'one', 'two' => 'two',  '3' => 'three'],
                'value' => '1',
                'expected' => 1,
            ],
            'mixed-keys-not-an-option' => [
                'source' => [1 => 'one', 'two' => 'two',  '3' => 'three'],
                'value' => '4',
                'expected' => '4',
            ]
        ];
    }

    #[DataProvider('provideCastSubmittedValue')]
    public function testCastSubmittedValue(array $source, string $value, mixed $expected): void
    {
        $field = new class ('Test', 'Test', $source) extends SelectField {
            public function castSubmittedValue(mixed $value): mixed
            {
                return parent::castSubmittedValue($value);
            }
        };
        $this->assertSame($expected, $field->castSubmittedValue($value));
    }

    public static function provideGetListValues(): array
    {
        return [
            'empty-array' => [
                'values' => [],
                'expected' => [],
            ],
            'key-array' => [
                'values' => ['cat' => 'cat', 'dog' => 'dog'],
                'expected' => ['cat', 'dog'],
            ],
            'int-array' => [
                'values' => [1, 2, 3],
                'expected' => [1, 2, 3],
            ],
            'arraylist' => [
                'values' => '{ArrayList}',
                'expected' => [1, 2],
            ],
            'string-untrimmed' => [
                'values' => '1,2,3 ',
                'expected' => ['1,2,3'],
            ],
        ];
    }

    #[DataProvider('provideGetListValues')]
    public function testGetListValues(mixed $values, mixed $expected): void
    {
        $field = new class ('Test') extends SelectField {
            public function getListValues($values)
            {
                return parent::getListValues($values);
            }
        };
        if ($values === '{ArrayList}') {
            $values = new ArrayList([
                new ArrayData(['ID' => 1]),
                new ArrayData(['ID' => 2]),
            ]);
        }
        $this->assertSame($expected, $field->getListValues($values));
    }
}
