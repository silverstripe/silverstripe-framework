<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use SilverStripe\i18n\i18n;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

class NumericFieldTest extends SapphireTest
{
    protected $usesDatabase = false;

    public static function provideSetValue()
    {
        return [
            // de
            [
                'locale' => 'de_DE',
                'scale' => 0,
                'input' => '13000',
                'expDataValue' => '13000',
                'expValue' => '13.000',
            ],
            [
                'de_DE',
                'scale' => 0,
                'input' => '15',
                'expDataValue' => '15',
                'expValue' => '15',
            ],
            [
                'locale' => 'de_DE',
                'scale' => null,
                'input' => '12.0',
                'expDataValue' => '12',
                'expValue' => '12,0',
            ],
            [
                'locale' => 'de_DE',
                'scale' => null,
                'input' => '12.1',
                'expDataValue' => '12.1',
                'expValue' => '12,1',
            ],
            [
                'locale' => 'de_DE',
                'scale' => 1,
                'input' => '14000.5',
                'expDataValue' => '14000.5',
                'expValue' => '14.000,5',
            ],
            // de_CH
            [
                'locale' => 'de_CH',
                'scale' => 0,
                'input' => '13000',
                'expDataValue' => '13000',
                'expValue' => "13’000"
            ],
            [
                'locale' => 'de_CH',
                'scale' => 0,
                'input' => '15',
                'expDataValue' => '15',
                'expValue' => '15'
            ],
            [
                'locale' => 'de_CH',
                'scale' => null,
                'input' => '12.0',
                'expDataValue' => '12',
                'expValue' => '12.0'
            ],
            [
                'locale' => 'de_CH',
                'scale' => null,
                'input' => '12.1',
                'expDataValue' => '12.1',
                'expValue' => '12.1'
            ],
            [
                'locale' => 'de_CH',
                'scale' => 1,
                'input' => '14000.5',
                'expDataValue' => '14000.5',
                'expValue' => "14’000.5"
            ],
            // nl
            [
                'locale' => 'nl_NL',
                'scale' => 0,
                'input' => '13000',
                'expDataValue' => '13000',
                'expValue' => '13.000',
            ],
            [
                'locale' => 'nl_NL',
                'scale' => 0,
                'input' => '15',
                'expDataValue' => '15',
                'expValue' => '15',
            ],
            [
                'locale' => 'nl_NL',
                'scale' => null,
                'input' => '12.0',
                'expDataValue' => '12',
                'expValue' => '12,0',
            ],
            [
                'locale' => 'nl_NL',
                'scale' => null,
                'input' => '12.1',
                'expDataValue' => '12.1',
                'expValue' => '12,1',
            ],
            [
                'locale' => 'nl_NL',
                'scale' => 1,
                'input' => '14000.5',
                'expDataValue' => '14000.5',
                'expValue' => '14.000,5',
            ],
            // fr
            [
                'locale' => 'fr_FR',
                'scale' => 0,
                'input' => '13000',
                // With a narrow non breaking space
                'expDataValue' => '13000',
                'expValue' => '13 000',
            ],
            [
                'locale' => 'fr_FR',
                'scale' => 0,
                'input' => '15',
                'expDataValue' => '15',
                'expValue' => '15',
            ],
            [
                'locale' => 'fr_FR',
                'scale' => null,
                'input' => '12.0',
                'expDataValue' => '12',
                'expValue' => '12,0',
            ],
            [
                'locale' => 'fr_FR',
                'scale' => null,
                'input' => '12.1',
                'expDataValue' => '12.1',
                'expValue' => '12,1',
            ],
            [
                'locale' => 'fr_FR',
                'scale' => 1,
                'input' => '14000.5',
                'expDataValue' => '14000.5',
                // With a narrow non breaking space
                'expValue' => '14 000,5',
            ],
            // us
            [
                'locale' => 'en_US',
                'scale' => 0,
                'input' => '13000',
                'expDataValue' => '13000',
                'expValue' => '13,000',
            ],
            [
                'locale' => 'en_US',
                'scale' => 0,
                'input' => '15',
                'expDataValue' => '15',
                'expValue' => '15',
            ],
            [
                'locale' => 'en_US',
                'scale' => null,
                'input' => '12.0',
                'expDataValue' => '12',
                'expValue' => '12.0',
            ],
            [
                'locale' => 'en_US',
                'scale' => null,
                'input' => '12.1',
                'expDataValue' => '12.1',
                'expValue' => '12.1',
            ],
            [
                'locale' => 'en_US',
                'scale' => 1,
                'input' => '14000.5',
                'expDataValue' => '14000.5',
                'expValue' => '14,000.5',
            ],
            // html5
            [
                'locale' => 'html5',
                'scale' => 0,
                'input' => '13000',
                'expDataValue' => '13000',
                'expValue' => '13000',
            ],
            [
                'locale' => 'html5',
                'scale' => 0,
                'input' => '15',
                'expDataValue' => '15',
                'expValue' => '15',
            ],
            [
                'locale' => 'html5',
                'scale' => null,
                'input' => '12.0',
                'expDataValue' => '12',
                'expValue' => '12.0',
            ],
            [
                'locale' => 'html5',
                'scale' => null,
                'input' => '12.1',
                'expDataValue' => '12.1',
                'expValue' => '12.1',
            ],
            [
                'locale' => 'html5',
                'scale' => 1,
                'input' => '14000.5',
                'expDataValue' => '14000.5',
                'expValue' => '14000.5',
            ],
        ];
    }

    #[DataProvider('provideSetValue')]
    public function testSetValue(
        string $locale,
        ?int $scale,
        string $input,
        string $expDataValue,
        string $expValue,
    ): void {
        $field = new NumericField('Number');
        if ($locale === 'html5') {
            $field->setHTML5(true);
        } else {
            $field->setLocale($locale);
        }
        $field->setScale($scale);
        $field->setValue($input);
        $this->assertSame($expDataValue, $field->dataValue());
        $this->assertSame($expValue, $field->getFormattedValue());
        $this->assertTrue($field->validate()->isValid());
    }

    public function testReadonly()
    {
        $field = new NumericField('Number');
        $field->setLocale('de_DE');
        $field->setScale(2);
        $field->setValue(1001.3);
        $html = $field->performReadonlyTransformation()->Field()->forTemplate();
        $this->assertStringContainsString('value="1.001,30"', $html);
        $this->assertStringContainsString('readonly="readonly"', $html);
    }

    public function testNumberTypeOnInputHtml()
    {
        $field = new NumericField('Number');

        $html = $field->Field();
        $this->assertStringContainsString('type="text"', $html, 'number type not set');
    }

    public function testNullSet()
    {
        $field = new NumericField('Number');
        $field->setValue('');
        $this->assertEquals('', $field->getFormattedValue());
        $this->assertNull($field->dataValue());
        $field->setValue(null);
        $this->assertNull($field->getFormattedValue());
        $this->assertNull($field->dataValue());
        $field->setValue(0);
        $this->assertEquals(0, $field->getFormattedValue());
        $this->assertEquals(0, $field->dataValue());
    }

    public static function dataForTestSubmittedValue()
    {
        return [
            [
                'locale' => 'de_DE',
                'scale' => 0,
                'submittedValue' => '13000',
                'dataValue' => '13000',
                'cleanedInput' => '13.000',
            ],
            [
                'locale' => 'de_DE',
                'scale' => 2,
                'submittedValue' => '12,00',
                'dataValue' => '12',
            ],
            [
                'locale' => 'de_DE',
                'scale' => 2,
                'submittedValue' => '12.00',
                'dataValue' => false,
            ],
            [
                'locale' => 'de_DE',
                'scale' => 1,
                'submittedValue' => '11 000',
                'dataValue' => '11000',
                'cleanedInput' => '11.000,0',
            ],
            [
                'locale' => 'de_DE',
                'scale' => 0,
                'submittedValue' => '11.000',
                'dataValue' => '11000',
            ],
            [
                'locale' => 'de_DE',
                'scale' => null,
                'submittedValue' => '11,000',
                'dataValue' => '11',
                'cleanedInput' => '11,0',
            ],
            [
                'locale' => 'de_DE',
                'scale' => 1,
                'submittedValue' => '15 000,5',
                'dataValue' => '15000.5',
                'cleanedInput' => '15.000,5',
            ],
            [
                'locale' => 'de_DE',
                'scale' => 1,
                'submittedValue' => '15 000.5',
                'dataValue' => false,
            ],
            [
                'locale' => 'de_DE',
                'scale' => 1,
                'submittedValue' => '15.000,5',
                'dataValue' => '15000.5',
            ],
            [
                'locale' => 'de_DE',
                'scale' => 1,
                'submittedValue' => '15,000.5',
                'dataValue' => false,
            ],
            // de_CH (Swiss German)
            [
                'locale' => 'de_CH',
                'scale' => 0,
                'submittedValue' => '13000',
                'dataValue' => '13000',
                'cleanedInput' => '13’000'
            ],
            [
                'locale' => 'de_CH',
                'scale' => 2,
                'submittedValue' => '12,00',
                'dataValue' => false
            ],
            [
                'locale' => 'de_CH',
                'scale' => 2,
                'submittedValue' => '12.00',
                'dataValue' => '12'
            ],
            [
                'locale' => 'de_CH',
                'scale' => 1,
                'submittedValue' => '11 000',
                'dataValue' => '11000',
                'cleanedInput' => "11’000.0"
            ],
            [
                'locale' => 'de_CH',
                'scale' => 0,
                'submittedValue' => "11.000",
                'dataValue' => '11',
                'cleanedInput' => "11"
            ],
            [
                'locale' => 'de_CH',
                'scale' => null,
                'submittedValue' => '11,000',
                'dataValue' => '11000',
                'cleanedInput' => '11’000.0'
            ],
            [
                'locale' => 'de_CH',
                'scale' => 1,
                'submittedValue' => '15 000,5',
                'dataValue' => false
            ],
            [
                'locale' => 'de_CH',
                'scale' => 1,
                'submittedValue' => "15 000.5",
                'dataValue' => '15000.5',
                'cleanedInput' => '15’000.5'
            ],
            [
                'locale' => 'de_CH',
                'scale' => 1,
                'submittedValue' => '15.000,5',
                'dataValue' => false
            ],
            [
                'locale' => 'de_CH',
                'scale' => 1,
                'submittedValue' => '15,000.5',
                'dataValue' => '15000.5',
                'cleanedInput' => '15’000.5'
            ],
            // nl_nl (same as de)
            [
                'locale' => 'nl_NL',
                'scale' => 0,
                'submittedValue' => '13000',
                'dataValue' => '13000',
                'cleanedInput' => '13.000',
            ],
            [
                'locale' => 'nl_NL',
                'scale' => 2,
                'submittedValue' => '12,00',
                'dataValue' => '12',
            ],
            [
                'locale' => 'nl_NL',
                'scale' => 2,
                'submittedValue' => '12.00',
                'dataValue' => false,
            ],
            [
                'locale' => 'nl_NL',
                'scale' => 1,
                'submittedValue' => '11 000',
                'dataValue' => '11000',
                'cleanedInput' => '11.000,0',
            ],
            [
                'locale' => 'nl_NL',
                'scale' => 0,
                'submittedValue' => '11.000',
                'dataValue' => '11000',
            ],
            [
                'locale' => 'nl_NL',
                'scale' => null,
                'submittedValue' => '11,000',
                'dataValue' => '11',
                'cleanedInput' => '11,0',
            ],
            [
                'locale' => 'nl_NL',
                'scale' => 1,
                'submittedValue' => '15 000,5',
                'dataValue' => '15000.5',
                'cleanedInput' => '15.000,5',
            ],
            [
                'locale' => 'nl_NL',
                'scale' => 1,
                'submittedValue' => '15 000.5',
                'dataValue' => false,
            ],
            [
                'locale' => 'nl_NL',
                'scale' => 1,
                'submittedValue' => '15.000,5',
                'dataValue' => '15000.5',
            ],
            [
                'locale' => 'nl_NL',
                'scale' => 1,
                'submittedValue' => '15,000.5',
                'dataValue' => false,
            ],
            // fr
            [
                'locale' => 'fr_FR',
                'scale' => 0,
                'submittedValue' => '13000',
                'dataValue' => '13000',
                // With a narrow non breaking space
                'cleanedInput' => '13 000',
            ],
            [
                'locale' => 'fr_FR',
                'scale' => 2,
                'submittedValue' => '12,00',
                'dataValue' => '12',
            ],
            [
                'locale' => 'fr_FR',
                'scale' => 2,
                'submittedValue' => '12.00',
                'dataValue' => false,
            ],
            [
                'locale' => 'fr_FR',
                'scale' => 1,
                'submittedValue' => '11 000',
                'dataValue' => '11000',
                // With a narrow non breaking space
                'cleanedInput' => '11 000,0',
            ],
            [
                'locale' => 'fr_FR',
                'scale' => 0,
                'submittedValue' => '11.000',
                'dataValue' => '11000',
                // With a narrow non breaking space
                'cleanedInput' => '11 000',
            ],
            [
                'locale' => 'fr_FR',
                'scale' => null,
                'submittedValue' => '11,000',
                'dataValue' => '11',
                'cleanedInput' => '11,0',
            ],
            [
                'locale' => 'fr_FR',
                'scale' => 1,
                'submittedValue' => '15 000,5',
                'dataValue' => '15000.5',
                // With a narrow non breaking space
                'cleanedInput' => '15 000,5',
            ],
            [
                'locale' => 'fr_FR',
                'scale' => 1,
                'submittedValue' => '15 000.5',
                'dataValue' => false,
            ],
            [
                'locale' => 'fr_FR',
                'scale' => 1,
                'submittedValue' => '15.000,5',
                'dataValue' => '15000.5',
                // With a narrow non breaking space
                'cleanedInput' => '15 000,5',
            ],
            [
                'locale' => 'fr_FR',
                'scale' => 1,
                'submittedValue' => '15,000.5',
                'dataValue' => false,
            ],
            // us
            [
                'locale' => 'en_US',
                'scale' => 0,
                'submittedValue' => '13000',
                'dataValue' => '13000',
                'cleanedInput' => '13,000',
            ],
            [
                'locale' => 'en_US',
                'scale' => 2,
                'submittedValue' => '12,00',
                'dataValue' => false,
            ],
            [
                'locale' => 'en_US',
                'scale' => 2,
                'submittedValue' => '12.00',
                'dataValue' => '12',
            ],
            [
                'locale' => 'en_US',
                'scale' => 1,
                'submittedValue' => '11 000',
                'dataValue' => '11000',
                'cleanedInput' => '11,000.0',
            ],
            [
                'locale' => 'en_US',
                'scale' => 0,
                'submittedValue' => '11.000',
                'dataValue' => '11',
                'cleanedInput' => '11',
            ],
            [
                'locale' => 'en_US',
                'scale' => null,
                'submittedValue' => '11,000',
                'dataValue' => '11000',
                'cleanedInput' => '11,000.0',
            ],
            [
                'locale' => 'en_US',
                'scale' => 1,
                'submittedValue' => '15 000,5',
                'dataValue' => false,
            ],
            [
                'locale' => 'en_US',
                'scale' => 1,
                'submittedValue' => '15 000.5',
                'dataValue' => '15000.5',
                'cleanedInput' => '15,000.5',
            ],
            [
                'locale' => 'en_US',
                'scale' => 1,
                'submittedValue' => '15.000,5',
                'dataValue' => false,
            ],
            [
                'locale' => 'en_US',
                'scale' => 1,
                'submittedValue' => '15,000.5',
                'dataValue' => '15000.5',
            ],
            // 'html5'
            [
                'locale' => 'html5',
                'scale' => 0,
                'submittedValue' => '13000',
                'dataValue' => '13000',
                'cleanedInput' => '13000',
            ],
            [
                'locale' => 'html5',
                'scale' => 2,
                'submittedValue' => '12,00',
                'dataValue' => false,
            ],
            [
                'locale' => 'html5',
                'scale' => 2,
                'submittedValue' => '12.00',
                'dataValue' => '12',
            ],
            [
                'locale' => 'html5',
                'scale' => 1,
                'submittedValue' => '11 000',
                'dataValue' => false,
                'cleanedInput' => '11 000',
            ],
            [
                'locale' => 'html5',
                'scale' => 0,
                'submittedValue' => '11.000',
                'dataValue' => '11',
                'cleanedInput' => '11',
            ],
            [
                'locale' => 'html5',
                'scale' => null,
                'submittedValue' => '11,000',
                'dataValue' => false,
            ],
            [
                'locale' => 'html5',
                'scale' => 1,
                'submittedValue' => '15 000,5',
                'dataValue' => false,
            ],
            [
                'locale' => 'html5',
                'scale' => 1,
                'submittedValue' => '15 000.5',
                'dataValue' => false,
            ],
            [
                'locale' => 'html5',
                'scale' => 1,
                'submittedValue' => '15.000,5',
                'dataValue' => false,
            ],
            [
                'locale' => 'html5',
                'scale' => 1,
                'submittedValue' => '15,000.5',
                'dataValue' => false,
            ],
        ];
    }

    #[DataProvider('dataForTestSubmittedValue')]
    public function testSetSubmittedValue(
        string $locale,
        ?int $scale,
        string $submittedValue,
        string|false $dataValue,
        ?string $cleanedInput = null
    ): void {
        $field = new NumericField('Number');
        if ($locale === 'html5') {
            $field->setHTML5(true);
        } else {
            $field->setLocale($locale);
        }
        $field->setScale($scale);
        $field->setSubmittedValue($submittedValue);
        // Check failure specific behaviour
        if ($dataValue === false) {
            $this->assertFalse($field->validate()->isValid(), 'isValid() A');
            $this->assertSame(false, $field->dataValue(), 'dataValue() A');
        } else {
            $this->assertTrue($field->validate()->isValid(), 'isValid() B');
            $this->assertSame($dataValue, $field->dataValue(), 'dataValue() B');
        }
        // Check that small errors are corrected for
        if (!$cleanedInput) {
            $cleanedInput = $submittedValue;
        }
        $this->assertSame($cleanedInput, $field->getFormattedValue(), 'getFormattedValue()');
    }

    public static function provideSetSubmittedValueNonString(): array
    {
        return [
            'array' => [
                'value' => [1234],
            ],
            'object' => [
                'value' => new stdClass(),
            ],
            'boolean true' => [
                'value' => true,
            ],
        ];
    }

    #[DataProvider('provideSetSubmittedValueNonString')]
    public function testSetSubmittedValueNonString(mixed $value): void
    {
        $expectedFormatted = $value;
        if ($value === true) {
            // explicit true is an exception to the rule - it gets formatted to string 1
            $expectedFormatted = '1';
        }
        $field = new NumericField('Number');
        $field->setSubmittedValue($value);
        $this->assertSame($value, $field->dataValue());
        $this->assertSame($expectedFormatted, $field->getFormattedValue());
    }

    public static function provideDataType(): array
    {
        return [
            'int-scale-0' => [
                'value' => 3,
                'scale' => 0,
                'expValue' => '3',
                'expFormattedValue' => '3',
                'expValueForValidation' => '3',
                'expDataValue' => '3',
            ],
            'int-scale-1' => [
                'value' => 3,
                'scale' => 1,
                'expValue' => '3',
                'expFormattedValue' => '3.0',
                'expValueForValidation' => '3',
                'expDataValue' => '3',
            ],
            'float-scale-0' => [
                'value' => 3.4,
                'scale' => 0,
                'expValue' => '3',
                'expFormattedValue' => '3',
                'expValueForValidation' => '3',
                'expDataValue' => '3',
            ],
            'float-scale-1' => [
                'value' => 3.4,
                'scale' => 1,
                'expValue' => '3.4',
                'expFormattedValue' => '3.4',
                'expValueForValidation' => '3.4',
                'expDataValue' => '3.4',
            ],
            'int-string-scale-0' => [
                'value' => '3',
                'scale' => 0,
                'expValue' => '3',
                'expValue' => '3',
                'expFormattedValue' => '3',
                'expValueForValidation' => '3',
                'expDataValue' => '3',
            ],
            'int-string-scale-1' => [
                'value' => '3',
                'scale' => 1,
                'expValue' => '3',
                'expFormattedValue' => '3.0',
                'expValueForValidation' => '3',
                'expDataValue' => '3',
            ],
            'float-string-scale-0' => [
                'value' => '3.4',
                'scale' => 0,
                'expValue' => '3',
                'expFormattedValue' => '3',
                'expValueForValidation' => '3',
                'expDataValue' => '3',
            ],
            'float-string-scale-1' => [
                'value' => '3.4',
                'scale' => 1,
                'expValue' => '3.4',
                'expFormattedValue' => '3.4',
                'expValueForValidation' => '3.4',
                'expDataValue' => '3.4',
            ],
            'null' => [
                'value' => null,
                'scale' => 0,
                'expValue' => null,
                'expFormattedValue' => null,
                'expValueForValidation' => null,
                'expDataValue' => null,
            ],
            'bool' => [
                'value' => true,
                'scale' => 0,
                'expValue' => true,
                'expFormattedValue' => '1',
                'expValueForValidation' => true,
                'expDataValue' => true,
            ],
        ];
    }

    #[DataProvider('provideDataType')]
    public function testDataType(
        mixed $value,
        int $scale,
        mixed $expValue,
        mixed $expFormattedValue,
        mixed $expValueForValidation,
        mixed $expDataValue
    ): void {
        $field = new NumericField('Test');
        $field->setScale($scale);
        $field->setValue($value);
        $this->assertSame($expValue, $field->getValue(), 'getValue()');
        $this->assertSame($expFormattedValue, $field->getFormattedValue(), 'getFormattedValue()');
        $this->assertSame($expValueForValidation, $field->getValueForValidation(), 'getValueForValidation()');
        $this->assertSame($expDataValue, $field->dataValue(), 'dataValue()');
    }

    public static function provideValidateSubmittedValue(): array
    {
        return [
            'valid' => [
                'value' => '123',
                'expected' => true,
            ],
            'invalid-huge-number' => [
                'value' => '9999999999999999999999999999999999999999',
                'expected' => false,
            ],
            'invalid-not-numeric' => [
                'value' => 'fish',
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideValidateSubmittedValue')]
    public function testValidateSubmittedValue(string $value, bool $expected): void
    {
        // This unit test is only only testing the validation logic contained in NumericField::validate()
        // It is not testing the FieldValidator validation logic
        $field = new NumericField('Test');
        $field->setSubmittedValue($value);
        $this->assertSame($expected, $field->validate()->isValid());
    }
}
