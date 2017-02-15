<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\SeparatedDateField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\FieldType\DBDatetime;

class DateFieldTest extends SapphireTest
{
    public function setUp()
    {
        parent::setUp();
        i18n::set_locale('en_NZ');
        DBDatetime::set_mock_now('2011-02-01 8:34:00');
    }

    public function testValidateMinDate()
    {
        $dateField = new DateField('Date');
        $dateField->setMinDate('2009-03-31');
        $dateField->setValue('2010-03-31');
        $this->assertTrue($dateField->validate(new RequiredFields()), 'Date above min date');

        $dateField = new DateField('Date');
        $dateField->setMinDate('2009-03-31');
        $dateField->setValue('1999-03-31');
        $this->assertFalse($dateField->validate(new RequiredFields()), 'Date below min date');

        $dateField = new DateField('Date');
        $dateField->setMinDate('2009-03-31');
        $dateField->setValue('2009-03-31');
        $this->assertTrue($dateField->validate(new RequiredFields()), 'Date matching min date');
    }

    public function testValidateMinDateStrtotime()
    {
        $f = new DateField('Date');
        $f->setMinDate('-7 days');
        $f->setValue(strftime('%Y-%m-%d', strtotime('-8 days', DBDatetime::now()->getTimestamp())));
        $this->assertFalse($f->validate(new RequiredFields()), 'Date below min date, with strtotime');

        $f = new DateField('Date');
        $f->setMinDate('-7 days');
        $f->setValue(strftime('%Y-%m-%d', strtotime('-7 days', DBDatetime::now()->getTimestamp())));
        $this->assertTrue($f->validate(new RequiredFields()), 'Date matching min date, with strtotime');
    }

    public function testValidateMaxDateStrtotime()
    {
        $f = new DateField('Date');
        $f->setMaxDate('7 days');
        $f->setValue(strftime('%Y-%m-%d', strtotime('8 days', DBDatetime::now()->getTimestamp())));
        $this->assertFalse($f->validate(new RequiredFields()), 'Date above max date, with strtotime');

        $f = new DateField('Date');
        $f->setMaxDate('7 days');
        $f->setValue(strftime('%Y-%m-%d', strtotime('7 days', DBDatetime::now()->getTimestamp())));
        $this->assertTrue($f->validate(new RequiredFields()), 'Date matching max date, with strtotime');
    }

    public function testValidateMaxDate()
    {
        $f = new DateField('Date');
        $f->setMaxDate('2009-03-31');
        $f->setValue('1999-03-31');
        $this->assertTrue($f->validate(new RequiredFields()), 'Date above min date');

        $f = new DateField('Date');
        $f->setMaxDate('2009-03-31');
        $f->setValue('2010-03-31');
        $this->assertFalse($f->validate(new RequiredFields()), 'Date above max date');

        $f = new DateField('Date');
        $f->setMaxDate('2009-03-31');
        $f->setValue('2009-03-31');
        $this->assertTrue($f->validate(new RequiredFields()), 'Date matching max date');
    }

    public function testConstructorWithoutArgs()
    {
        $f = new DateField('Date');
        $this->assertEquals($f->dataValue(), null);
    }

    public function testConstructorWithDateString()
    {
        $f = new DateField('Date', 'Date', '29/03/2003');
        $this->assertEquals(null, $f->dataValue());
        $f = new DateField('Date', 'Date', '2003-03-29 12:23:00');
        $this->assertEquals('2003-03-29', $f->dataValue());
    }

    public function testTidyISO8601()
    {
        $f = new DateField('Date', 'Date');
        $this->assertEquals(null, $f->tidyISO8601('notadate'));
        $this->assertEquals('2011-01-31', $f->tidyISO8601('-1 day'));
        $this->assertEquals(null, $f->tidyISO8601('29/03/2003'));
    }

    public function testSetValueWithDateString()
    {
        $f = new DateField('Date', 'Date');
        $f->setSubmittedValue('29/03/2003');
        $this->assertEquals($f->dataValue(), '2003-03-29');
    }

    public function testSetValueWithDateArray()
    {
        $f = new SeparatedDateField('Date', 'Date');
        $f->setSubmittedValue([
            'day' => 29,
            'month' => 03,
            'year' => 2003
        ]);
        $this->assertEquals($f->dataValue(), '2003-03-29');
    }

    public function testConstructorWithIsoDate()
    {
        // used by Form->loadDataFrom()
        $f = new DateField('Date', 'Date', '2003-03-29');
        $this->assertEquals($f->dataValue(), '2003-03-29');
    }

    public function testValidateDMY()
    {
        // Constructor only accepts iso8601
        $f = new DateField('Date', 'Date', '29/03/2003');
        $this->assertFalse($f->validate(new RequiredFields()));

        // Set via submitted value (localised) accepts this, however
        $f = new DateField('Date', 'Date');
        $f->setSubmittedValue('29/03/2003');
        $this->assertTrue($f->validate(new RequiredFields()));

        $f = new DateField('Date', 'Date', '2003-03-29');
        $this->assertTrue($f->validate(new RequiredFields()));

        $f = new DateField('Date', 'Date', 'wrong');
        $this->assertFalse($f->validate(new RequiredFields()));
    }

    public function testEmptyValueValidation()
    {
        $validator = new RequiredFields();
        $field = new SeparatedDateField('Date');
        $this->assertTrue($field->validate($validator));
        $field->setSubmittedValue([
            'day' => '',
            'month' => '',
            'year' => '',
        ]);
        $this->assertTrue($field->validate($validator));
    }

    public function testValidateArray()
    {
        $f = new SeparatedDateField('Date', 'Date');
        $f->setSubmittedValue([
            'day' => 29,
            'month' => 03,
            'year' => 2003
        ]);
        $this->assertTrue($f->validate(new RequiredFields()));

        $f->setValue(null);
        $this->assertTrue($f->validate(new RequiredFields()), 'NULL values are validating TRUE');

        $f->setSubmittedValue(array());
        $this->assertTrue($f->validate(new RequiredFields()), 'Empty array values are validating TRUE');

        $f->setSubmittedValue([
            'day' => null,
            'month' => null,
            'year' => null
        ]);
        $this->assertTrue($f->validate(new RequiredFields()), 'Empty array values with keys are validating TRUE');
        $f->setSubmittedValue([
            'day' => 9999,
            'month' => 9999,
            'year' => 9999
        ]);
        $this->assertFalse($f->validate(new RequiredFields()));
    }

    public function testValidateEmptyArrayValuesSetsNullForValueObject()
    {
        $f = new SeparatedDateField('Date', 'Date');
        $f->setSubmittedValue([
            'day' => '',
            'month' => '',
            'year' => ''
        ]);
        $this->assertNull($f->dataValue());

        $f->setSubmittedValue([
            'day' => null,
            'month' => null,
            'year' => null
        ]);
        $this->assertNull($f->dataValue());
    }

    public function testValidateArrayValue()
    {
        $f = new SeparatedDateField('Date', 'Date');
        $f->setSubmittedValue(['day' => 29, 'month' => 03, 'year' => 2003]);
        $this->assertTrue($f->validate(new RequiredFields()));

        $f->setSubmittedValue(['month' => 03, 'year' => 2003]);
        $this->assertFalse($f->validate(new RequiredFields()));

        $f->setSubmittedValue(array('day' => 99, 'month' => 99, 'year' => 2003));
        $this->assertFalse($f->validate(new RequiredFields()));
    }

    public function testFormatEnNz()
    {
        /* We get YYYY-MM-DD format as the data value for DD/MM/YYYY input value */
        $f = new DateField('Date', 'Date');
        $f->setSubmittedValue('29/03/2003');
        $this->assertEquals($f->dataValue(), '2003-03-29');
    }

    public function testSetLocale()
    {
        // should get en_NZ by default through setUp()
        i18n::set_locale('de_DE');
        $f = new DateField('Date', 'Date', '29/03/2003');
        $f->setValue('29.06.2006');
        $this->assertEquals($f->dataValue(), '2006-06-29');
    }

    /**
     * Note: This is mostly tested for legacy reasons
     */
    public function testMDYFormat()
    {
        $dateField = new DateField('Date', 'Date');
        $dateField->setDateFormat('d/M/y');
        $dateField->setSubmittedValue('31/03/2003');
        $this->assertEquals(
            '2003-03-31',
            $dateField->dataValue(),
            "We get MM-DD-YYYY format as the data value for YYYY-MM-DD input value"
        );

        $dateField2 = new DateField('Date', 'Date');
        $dateField2->setDateFormat('d/M/y');
        $dateField2->setSubmittedValue('04/3/03');
        $this->assertEquals(
            $dateField2->dataValue(),
            '2003-03-04',
            "Even if input value hasn't got leading 0's in it we still get the correct data value"
        );
    }
}
