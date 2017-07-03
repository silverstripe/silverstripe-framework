<?php

namespace SilverStripe\Forms\Tests;

use IntlDateFormatter;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\FieldType\DBDatetime;

/**
 * @skipUpgrade
 */
class DateFieldTest extends SapphireTest
{
    protected function setUp()
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

    public function testSetValue()
    {
        $f = (new DateField('Date', 'Date'))->setValue('notadate');
        $this->assertNull($f->Value(), 'Invalid input ignored');

        $f = (new DateField('Date', 'Date'))->setValue('-1 day');
        $this->assertEquals($f->Value(), '2011-01-31', 'Relative dates accepted');

        $f = (new DateField('Date', 'Date'))->setValue('2011-01-31');
        $this->assertEquals($f->Value(), '2011-01-31', 'ISO format accepted');

        $f = (new DateField('Date', 'Date'))->setValue('2011-01-31 23:59:59');
        $this->assertEquals($f->Value(), '2011-01-31', 'ISO format with time accepted');
    }

    public function testSetValueWithLocalisedDateString()
    {
        $f = new DateField('Date', 'Date');
        $f->setHTML5(false);
        $f->setSubmittedValue('29/03/2003');
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

    public function testFormatEnNz()
    {
        /* We get YYYY-MM-DD format as the data value for DD/MM/YYYY input value */
        $f = new DateField('Date', 'Date');
        $f->setHTML5(false);
        $f->setSubmittedValue('29/03/2003');
        $this->assertEquals($f->dataValue(), '2003-03-29');
    }

    public function testSetLocale()
    {
        // should get en_NZ by default through setUp()
        i18n::set_locale('de_DE');
        $f = new DateField('Date', 'Date', '29/03/2003');
        $f->setHTML5(false);
        $f->setValue('29.06.2006');
        $this->assertEquals($f->dataValue(), '2006-06-29');
    }

    /**
     * Note: This is mostly tested for legacy reasons
     */
    public function testMDYFormat()
    {
        $dateField = new DateField('Date', 'Date');
        $dateField->setHTML5(false);
        $dateField->setDateFormat('d/M/y');
        $dateField->setSubmittedValue('31/03/2003');
        $this->assertEquals(
            '2003-03-31',
            $dateField->dataValue(),
            "We get MM-DD-YYYY format as the data value for YYYY-MM-DD input value"
        );

        $dateField2 = new DateField('Date', 'Date');
        $dateField2->setHTML5(false);
        $dateField2->setDateFormat('d/M/y');
        $dateField2->setSubmittedValue('04/3/03');
        $this->assertEquals(
            $dateField2->dataValue(),
            '2003-03-04',
            "Even if input value hasn't got leading 0's in it we still get the correct data value"
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessageRegExp /Please opt-out .* if using setDateFormat/
     */
    public function testHtml5WithCustomFormatThrowsException()
    {
        $dateField = new DateField('Date', 'Date');
        $dateField->setValue('2010-03-31');
        $dateField->setDateFormat('d/M/y');
        $dateField->Value();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessageRegExp /Please opt-out .* if using setDateLength/
     */
    public function testHtml5WithCustomDateLengthThrowsException()
    {
        $dateField = new DateField('Date', 'Date');
        $dateField->setValue('2010-03-31');
        $dateField->setDateLength(IntlDateFormatter::MEDIUM);
        $dateField->Value();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessageRegExp /Please opt-out .* if using setLocale/
     */
    public function testHtml5WithCustomLocaleThrowsException()
    {
        $dateField = new DateField('Date', 'Date');
        $dateField->setValue('2010-03-31');
        $dateField->setLocale('de_DE');
        $dateField->Value();
    }
}
