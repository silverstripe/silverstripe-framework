<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBInt;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\ORM\FieldType\DBDecimal;

class DBDecimalTest extends SapphireTest
{
    public function testDefaultValue(): void
    {
        $field = new DBDecimal('MyField');
        $this->assertSame(0.0, $field->getValue());
    }

    public static function provideSetValue(): array
    {
        return [
            'float' => [
                'value' => 9.123,
                'expected' => 9.123,
            ],
            'negative-float' => [
                'value' => -9.123,
                'expected' => -9.123,
            ],
            'string-float' => [
                'value' => '9.123',
                'expected' => 9.123,
            ],
            'string-negative-float' => [
                'value' => '-9.123',
                'expected' => -9.123,
            ],
            'zero' => [
                'value' => 0,
                'expected' => 0.0,
            ],
            'int' => [
                'value' => 3,
                'expected' => 3.0,
            ],
            'negative-int' => [
                'value' => -3,
                'expected' => -3.0,
            ],
            'string-int' => [
                'value' => '3',
                'expected' => 3.0,
            ],
            'negative-string-int' => [
                'value' => '-3',
                'expected' => -3.0,
            ],
            'string' => [
                'value' => 'fish',
                'expected' => 'fish',
            ],
            'array' => [
                'value' => [],
                'expected' => [],
            ],
            'null' => [
                'value' => null,
                'expected' => null,
            ],
            'true' => [
                'value' => true,
                'expected' => true,
            ],
            'false' => [
                'value' => false,
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideSetValue')]
    public function testSetValue(mixed $value, mixed $expected): void
    {
        $field = new DBDecimal('MyField');
        $field->setValue($value);
        $this->assertSame($expected, $field->getValue());
    }
}
