<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\ORM\FieldType\DBForeignKey;

class DBForiegnKeyTest extends SapphireTest
{
    public static function provideSetValue(): array
    {
        return [
            'int' => [
                'value' => 2,
                'expected' => 2,
            ],
            'string' => [
                'value' => '2',
                'expected' => 2,
            ],
            'zero' => [
                'value' => 0,
                'expected' => 0,
            ],
            'blank-string' => [
                'value' => '',
                'expected' => 0,
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
        $field = new DBForeignKey('TestField');
        $field->setValue($value);
        $this->assertSame($expected, $field->getValue());
    }
}
