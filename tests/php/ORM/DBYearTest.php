<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\FieldType\DBYear;
use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;

class DBYearTest extends SapphireTest
{

    /**
     * Test that the scaffolding form field works
     */
    public function testScaffoldFormFieldFirst()
    {
        $year = new DBYear();
        $field = $year->scaffoldFormField("YearTest");
        $this->assertEquals(DropdownField::class, get_class($field));

        //This should be a list of years from the current one, counting down to 1901
        $source = $field->getSource();

        $lastValue = end($source);
        $lastKey = key($source ?? []);

        //Keys and values should be the same - and the last one should be 1901
        $this->assertEquals(1901, $lastValue);
        $this->assertEquals(1901, $lastKey);
    }

    public function testScaffoldFormFieldLast()
    {
        $year = new DBYear();
        $field = $year->scaffoldFormField("YearTest");
        $source = $field->getSource();

        //The first one should be the current year
        $currentYear = (int)date('Y');
        $firstValue = reset($source);
        $firstKey = key($source ?? []);

        $this->assertEquals($currentYear, $firstValue);
        $this->assertEquals($currentYear, $firstKey);
    }

    public static function provideSetValue(): array
    {
        return [
            '4-int' => [
                'value' => 2024,
                'expected' => 2024,
            ],
            '2-int' => [
                'value' => 24,
                'expected' => 2024,
            ],
            '0-int' => [
                'value' => 0,
                'expected' => 0,
            ],
            '4-string' => [
                'value' => '2024',
                'expected' => 2024,
            ],
            '2-string' => [
                'value' => '24',
                'expected' => 2024,
            ],
            '0-string' => [
                'value' => '0',
                'expected' => 0,
            ],
            '00-string' => [
                'value' => '00',
                'expected' => 2000,
            ],
            '0000-string' => [
                'value' => '0000',
                'expected' => 0,
            ],
            '4-int-low' => [
                'value' => 1900,
                'expected' => 1900,
            ],
            '4-int-low' => [
                'value' => 2156,
                'expected' => 2156,
            ],
            '4-string-low' => [
                'value' => '1900',
                'expected' => 1900,
            ],
            '4-string-low' => [
                'value' => '2156',
                'expected' => 2156,
            ],
            'int-negative' => [
                'value' => -2024,
                'expected' => -2024,
            ],
            'string-negative' => [
                'value' => '-2024',
                'expected' => '-2024',
            ],
            'float' => [
                'value' => 2024.0,
                'expected' => 2024.0,
            ],
            'string-float' => [
                'value' => '2024.0',
                'expected' => '2024.0',
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
            'array' => [
                'value' => [],
                'expected' => [],
            ],
        ];
    }

    #[DataProvider('provideSetValue')]
    public function testSetValue(mixed $value, mixed $expected): void
    {
        $field = new DBYear('MyField');
        $result = $field->setValue($value);
        $this->assertSame($expected, $field->getValue());
    }
}
