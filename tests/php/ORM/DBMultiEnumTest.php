<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBMultiEnum;
use PHPUnit\Framework\Attributes\DataProvider;

class DBMultiEnumTest extends SapphireTest
{
    public static function provideGetValueForValidation(): array
    {
        return [
            'array' => [
                'value' => ['Red', 'Green'],
                'expected' => ['Red', 'Green'],
            ],
            'string' => [
                'value' => 'Red,Green',
                'expected' => ['Red', 'Green'],
            ],
            'string-non-existant-value' => [
                'value' => 'Red,Green,Purple',
                'expected' => ['Red', 'Green', 'Purple'],
            ],
            'empty-string' => [
                'value' => '',
                'expected' => [''],
            ],
            'null' => [
                'value' => null,
                'expected' => [''],
            ],
        ];
    }

    #[DataProvider('provideGetValueForValidation')]
    public function testGetValueForValidation(mixed $value, array $expected): void
    {
        $obj = new DBMultiEnum('TestField', ['Red', 'Green', 'Blue']);
        $obj->setValue($value);
        $this->assertSame($expected, $obj->getValueForValidation());
    }
}
