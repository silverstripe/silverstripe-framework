<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Forms\CurrencyField;
use SilverStripe\ORM\FieldType\DBCurrency;
use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;

class DBCurrencyTest extends SapphireTest
{
    public function testNiceFormatting()
    {
        // Test a bunch of different data values and results in Nice() and Whole()
        $tests = [
            // Test basic operation
            '$50.00' => ['$50.00', '$50'],

            // Test negative numbers
            '-1000' => ['($1,000.00)','($1,000)'],
            '-$2,000' => ['($2,000.00)', '($2,000)'],

            // Test thousands comma
            '5000' => ['$5,000.00', '$5,000'],

            // Test scientific notation
            '5.68434188608E-14' => ['$0.00', '$0'],
            '5.68434188608E7' => ['$56,843,418.86', '$56,843,419'],
        ];

        foreach ($tests as $value => $niceValues) {
            $c = new DBCurrency('MyField');
            $c->setValue($value);
            $this->assertEquals($niceValues[0], $c->Nice());
            $this->assertEquals($niceValues[1], $c->Whole());
        }
    }

    public function testScaffoldedField()
    {
        // Test DBCurrency scaffolds a CurrencyField
        $currencyDbField = DBCurrency::create('Currency');
        $scaffoldedField = $currencyDbField->scaffoldFormField();

        $this->assertInstanceOf(CurrencyField::class, $scaffoldedField);
    }

    public static function provideSetValue(): array
    {
        // Most test cases covered by DBCurrencyTest, only testing a subset here
        return [
            'currency' => [
                'value' => '$1.23',
                'expected' => 1.23,
            ],
            'negative-currency' => [
                'value' => "-$1.23",
                'expected' => -1.23,
            ],
            'scientific-1' => [
                'value' => 5.68434188608E-14,
                'expected' => 5.68434188608E-14,
            ],
            'scientific-2' => [
                'value' => 5.68434188608E7,
                'expected' => 56843418.8608,
            ],
            'scientific-1-string' => [
                'value' => '5.68434188608E-14',
                'expected' => 5.68434188608E-14,
            ],
            'scientific-2-string' => [
                'value' => '5.68434188608E7',
                'expected' => 56843418.8608,
            ],
            'int' => [
                'value' => 1,
                'expected' => 1.0,
            ],
            'string-int' => [
                'value' => "1",
                'expected' => 1.0,
            ],
            'string-float' => [
                'value' => '1.2',
                'expected' => 1.2,
            ],
            'value-in-string' => [
                'value' => 'this is 50.29 dollars',
                'expected' => 'this is 50.29 dollars',
            ],
            'scientific-value-in-string' => [
                'value' => '5.68434188608E7 a string',
                'expected' => '5.68434188608E7 a string',
            ],
            'value-in-brackets' => [
                'value' => '(100)',
                'expected' => '(100)',
            ],
            'non-numeric' => [
                'value' => 'fish',
                'expected' => 'fish',
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
        $field = new DBCurrency('MyField');
        $field->setValue($value);
        $this->assertSame($expected, $field->getValue());
    }
}
