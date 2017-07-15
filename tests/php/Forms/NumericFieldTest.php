<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\i18n\i18n;

class NumericFieldTest extends SapphireTest
{
    protected $usesDatabase = false;

    /**
     * In some cases and locales, validation expects non-breaking spaces.
     *
     * Duplicates non-public NumericField::clean method
     *
     * @param  string $input
     * @return string The input value, with all spaces replaced with non-breaking spaces
     */
    protected function clean($input)
    {
        $nbsp = html_entity_decode('&nbsp;', null, 'UTF-8');
        return str_replace(' ', $nbsp, trim($input));
    }

    /**
     * Test that data loaded in via Form::loadDataFrom(DataObject) will populate the field correctly,
     * and can format the database value appropriately for the frontend
     *
     * @dataProvider dataForTestSetValue
     * @param string $locale Locale to test in
     * @param int $scale Scale size (number of decimal places)
     * @param string $input Input string
     * @param int|float $output Expected data value
     */
    public function testSetValue($locale, $scale, $input, $output)
    {
        $field = new NumericField('Number');
        if ($locale === 'html5') {
            $field->setHTML5(true);
        } else {
            $field->setLocale($locale);
        }
        $field->setScale($scale);

        // Load from DB via setValue
        $field->setValue($input);

        // Test value
        $this->assertEquals(
            $input,
            $field->dataValue(),
            "Expected $input loaded via dataobject to be left intact in locale $locale"
        );

        // Test expected formatted value
        $this->assertEquals(
            $this->clean($output),
            $this->clean($field->Value()),
            "Expected $input to be formatted as $output in locale $locale"
        );

        // Input values are always valid
        $this->assertTrue($field->validate(new RequiredFields()));
    }

    /**
     * Test formatting of numbers
     */
    public function dataForTestSetValue()
    {
        return [
            // de
            ['de_DE', 0, '13000', "13.000"],
            ['de_DE', 0, '15', '15'],
            ['de_DE', null, '12.0', '12,0'],
            ['de_DE', null, '12.1', '12,1'],
            ['de_DE', 1, '14000.5', "14.000,5"],
            // nl
            ['nl_NL', 0, '13000', "13.000"],
            ['nl_NL', 0, '15', '15'],
            ['nl_NL', null, '12.0', '12,0'],
            ['nl_NL', null, '12.1', '12,1'],
            ['nl_NL', 1, '14000.5', "14.000,5"],
            // fr
            ['fr_FR', 0, '13000', "13 000"],
            ['fr_FR', 0, '15', '15'],
            ['fr_FR', null, '12.0', '12,0'],
            ['fr_FR', null, '12.1', '12,1'],
            ['fr_FR', 1, '14000.5', "14 000,5"],
            // us
            ['en_US', 0, '13000', "13,000"],
            ['en_US', 0, '15', '15'],
            ['en_US', null, '12.0', '12.0'],
            ['en_US', null, '12.1', '12.1'],
            ['en_US', 1, '14000.5', "14,000.5"],
            // html5
            ['html5', 0, '13000', "13000"],
            ['html5', 0, '15', '15'],
            ['html5', null, '12.0', '12.0'],
            ['html5', null, '12.1', '12.1'],
            ['html5', 1, '14000.5', "14000.5"],
        ];
    }

    public function testReadonly()
    {
        $field = new NumericField('Number');
        $field->setLocale('de_DE');
        $field->setScale(2);
        $field->setValue(1001.3);
        $html = $field->performReadonlyTransformation()->Field()->forTemplate();
        $this->assertContains('value="1.001,30"', $html);
        $this->assertContains('readonly="readonly"', $html);
    }

    public function testNumberTypeOnInputHtml()
    {
        $field = new NumericField('Number');

        $html = $field->Field();

        // @todo - Revert to number one day when html5 number supports proper localisation
        // See https://github.com/silverstripe/silverstripe-framework/pull/4565
        $this->assertContains('type="text"', $html, 'number type not set');
    }

    public function testNullSet()
    {
        $field = new NumericField('Number');
        $field->setValue('');
        $this->assertEquals('', $field->Value());
        $this->assertNull($field->dataValue());
        $field->setValue(null);
        $this->assertNull($field->Value());
        $this->assertNull($field->dataValue());
        $field->setValue(0);
        $this->assertEquals(0, $field->Value());
        $this->assertEquals(0, $field->dataValue());
    }

    public function dataForTestSubmittedValue()
    {
        return [
            ['de_DE', 0, '13000', 13000, '13.000'],
            ['de_DE', 2, '12,00', 12.00],
            ['de_DE', 2, '12.00', false],
            ['de_DE', 1, '11 000', 11000, '11.000,0'],
            ['de_DE', 0, '11.000', 11000],
            ['de_DE', null, '11,000', 11.0, '11,0'],
            ['de_DE', 1, '15 000,5', 15000.5, '15.000,5'],
            ['de_DE', 1, '15 000.5', false],
            ['de_DE', 1, '15.000,5', 15000.5],
            ['de_DE', 1, '15,000.5', false],

            // nl_nl (same as de)
            ['nl_NL', 0, '13000', 13000, '13.000'],
            ['nl_NL', 2, '12,00', 12.00],
            ['nl_NL', 2, '12.00', false],
            ['nl_NL', 1, '11 000', 11000, '11.000,0'],
            ['nl_NL', 0, '11.000', 11000],
            ['nl_NL', null, '11,000', 11.0, '11,0'],
            ['nl_NL', 1, '15 000,5', 15000.5, '15.000,5'],
            ['nl_NL', 1, '15 000.5', false],
            ['nl_NL', 1, '15.000,5', 15000.5],
            ['nl_NL', 1, '15,000.5', false],

            // fr
            ['fr_FR', 0, '13000', 13000, '13 000'],
            ['fr_FR', 2, '12,00', 12.0],
            ['fr_FR', 2, '12.00', false],
            ['fr_FR', 1, '11 000', 11000, '11 000,0'],
            ['fr_FR', 0, '11.000', 11000, '11 000'],
            ['fr_FR', null, '11,000', 11.000, '11,0'],
            ['fr_FR', 1, '15 000,5', 15000.5],
            ['fr_FR', 1, '15 000.5', false],
            ['fr_FR', 1, '15.000,5', 15000.5, '15 000,5'],
            ['fr_FR', 1, '15,000.5', false],
            // us
            ['en_US', 0, '13000', 13000, '13,000'],
            ['en_US', 2, '12,00', false],
            ['en_US', 2, '12.00', 12.00],
            ['en_US', 1, '11 000', 11000.0, '11,000.0'],
            ['en_US', 0, '11.000', 11, '11'],
            ['en_US', null, '11,000', 11000, '11,000.0'],
            ['en_US', 1, '15 000,5', false],
            ['en_US', 1, '15 000.5', 15000.5, '15,000.5'],
            ['en_US', 1, '15.000,5', false],
            ['en_US', 1, '15,000.5', 15000.5],
            // 'html5'
            ['html5', 0, '13000', 13000, '13000'],
            ['html5', 2, '12,00', false],
            ['html5', 2, '12.00', 12.00],
            ['html5', 1, '11 000', false, '11 000'],
            ['html5', 0, '11.000', 11, '11'],
            ['html5', null, '11,000', false],
            ['html5', 1, '15 000,5', false],
            ['html5', 1, '15 000.5', false],
            ['html5', 1, '15.000,5', false],
            ['html5', 1, '15,000.5', false],
        ];
    }

    /**
     * @dataProvider dataForTestSubmittedValue
     * @param string $locale Locale to test in
     * @param int $scale Scale size (number of decimal places)
     * @param string $submittedValue Input string
     * @param int|float $dataValue Expected data value
     * @param string $cleanedInput
     */
    public function testSetSubmittedValue($locale, $scale, $submittedValue, $dataValue, $cleanedInput = null)
    {
        $field = new NumericField('Number');
        if ($locale === 'html5') {
            $field->setHTML5(true);
        } else {
            $field->setLocale($locale);
        }
        $field->setScale($scale);
        $validator = new RequiredFields('Number');

        // Both decimal and thousands B
        $field->setSubmittedValue($submittedValue);

        // Check failure specific behaviour
        if ($dataValue === false) {
            $this->assertFalse(
                $field->validate($validator),
                "Expect validation to fail for input $submittedValue in locale $locale"
            );
            $this->assertEquals(
                0,
                $field->dataValue(),
                "Expect invalid value to be rewritten to 0 in locale $locale"
            );
        } else {
            $this->assertTrue(
                $field->validate($validator),
                "Expect validation to succeed for $submittedValue in locale $locale"
            );
            $this->assertEquals(
                $dataValue,
                $field->dataValue(),
                "Expect value $submittedValue to be mapped to $dataValue in locale $locale"
            );
        }

        // Check that small errors are corrected for
        if (!$cleanedInput) {
            $cleanedInput = $submittedValue;
        }
        $this->assertEquals(
            $this->clean($cleanedInput),
            $this->clean($field->Value()),
            "Expected input $submittedValue to be cleaned up as $cleanedInput in locale $locale"
        );
    }
}
