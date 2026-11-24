<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\ORM\FieldType\DBBoolean;

class DBBooleanTest extends SapphireTest
{
    public function testDefaultValue(): void
    {
        $field = new DBBoolean('MyField');
        $this->assertSame(false, $field->getValue());
    }

    public static function provideSetValue(): array
    {
        return [
            'true' => [
                'value' => true,
                'expected' => true,
            ],
            'false' => [
                'value' => false,
                'expected' => false,
            ],
            '1-int' => [
                'value' => 1,
                'expected' => true,
            ],
            '1-string' => [
                'value' => '1',
                'expected' => true,
            ],
            '0-int' => [
                'value' => 0,
                'expected' => false,
            ],
            '0-string' => [
                'value' => '0',
                'expected' => false,
            ],
            't' => [
                'value' => 't',
                'expected' => true,
            ],
            'f' => [
                'value' => 'f',
                'expected' => false,
            ],
            'T' => [
                'value' => 'T',
                'expected' => true,
            ],
            'F' => [
                'value' => 'F',
                'expected' => false,
            ],
            'blank-string' => [
                'value' => '',
                'expected' => false,
            ],
            'true-string' => [
                'value' => 'true',
                'expected' => true,
            ],
            'false-string' => [
                'value' => 'false',
                'expected' => false,
            ],
            'fish-string' => [
                'value' => 'fish',
                'expected' => 'fish',
            ],
            '2-int' => [
                'value' => 2,
                'expected' => 2,
            ],
            '0.0' => [
                'value' => 0.0,
                'expected' => false,
            ],
            '1.0' => [
                'value' => 1.0,
                'expected' => true,
            ],
            'null' => [
                'value' => null,
                'expected' => null,
            ],
            'array' => [
                'value' => [],
                'expected' => [],
            ],
        ];
    }

    #[DataProvider('provideSetValue')]
    public function testSetValue(mixed $value, mixed $expected): void
    {
        $field = new DBBoolean('MyField');
        $field->setValue($value);
        $this->assertSame($expected, $field->getValue());
    }
}
