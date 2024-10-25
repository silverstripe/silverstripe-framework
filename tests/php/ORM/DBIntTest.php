<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBInt;
use PHPUnit\Framework\Attributes\DataProvider;

class DBIntTest extends SapphireTest
{
    public function testDefaultValue(): void
    {
        $field = new DBInt('MyField');
        $this->assertSame(0, $field->getValue());
    }

    public static function provideSetValue(): array
    {
        return [
            'int' => [
                'value' => 3,
                'expected' => 3,
            ],
            'string-int' => [
                'value' => '3',
                'expected' => 3,
            ],
            'negative-int' => [
                'value' => -3,
                'expected' => -3,
            ],
            'negative-string-int' => [
                'value' => '-3',
                'expected' => -3,
            ],
            'float' => [
                'value' => 3.5,
                'expected' => 3.5,
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
        ];
    }

    #[DataProvider('provideSetValue')]
    public function testSetValue(mixed $value, mixed $expected): void
    {
        $field = new DBInt('MyField');
        $field->setValue($value);
        $this->assertSame($expected, $field->getValue());
    }

    public static function provideValidate(): array
    {
        return [
            'valid' => [
                'value' => 123,
                'expected' => true,
            ],
            'invalid' => [
                'value' => 'abc',
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideValidate')]
    public function testValidate(mixed $value, bool $expected): void
    {
        $field = new DBInt('MyField');
        $field->setValue($value);
        $result = $field->validate();
        $this->assertSame($expected, $result->isValid());
    }
}
