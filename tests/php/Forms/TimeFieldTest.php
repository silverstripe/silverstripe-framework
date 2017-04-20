<?php

namespace SilverStripe\Forms\Tests;

use IntlDateFormatter;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\TimeField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\i18n\i18n;

class TimeFieldTest extends SapphireTest
{
    protected function setUp()
    {
        parent::setUp();
        i18n::set_locale('en_NZ');
    }

    public function testConstructorWithoutArgs()
    {
        $f = new TimeField('Time');
        $this->assertEquals($f->dataValue(), null);
    }

    public function testConstructorWithString()
    {
        $f = new TimeField('Time', 'Time', '23:00:00');
        $this->assertEquals($f->dataValue(), '23:00:00');
    }

    public function testValidate()
    {
        $f = new TimeField('Time', 'Time', '11pm');
        $this->assertTrue($f->validate(new RequiredFields()));

        $f = new TimeField('Time', 'Time', '23:59');
        $this->assertTrue($f->validate(new RequiredFields()));

        $f = new TimeField('Time', 'Time', 'wrong');
        $this->assertFalse($f->validate(new RequiredFields()));
    }

    public function testValidateLenientWithHtml5()
    {
        $f = new TimeField('Time', 'Time', '23:59:59');
        $f->setHTML5(true);
        $this->assertTrue($f->validate(new RequiredFields()));

        $f = new TimeField('Time', 'Time', '23:59'); // leave out seconds
        $f->setHTML5(true);
        $this->assertTrue($f->validate(new RequiredFields()));
    }

    public function testSetLocale()
    {
        // should get en_NZ by default through setUp()
        $f = new TimeField('Time', 'Time');
        $f->setHTML5(false);
        $f->setLocale('fr_FR');
        // TODO Find a hour format thats actually different
        $f->setValue('23:59');
        $this->assertEquals($f->dataValue(), '23:59:00');
    }

    public function testSetValueWithUseStrToTime()
    {
        $f = new TimeField('Time', 'Time');
        $f->setValue('11pm');
        $this->assertEquals(
            $f->dataValue(),
            '23:00:00',
            'Setting value to "11pm" parses with strtotime enabled'
        );
        $this->assertTrue($f->validate(new RequiredFields()));

        $f = new TimeField('Time', 'Time');
        $f->setValue('11:59pm');
        $this->assertEquals('23:59:00', $f->dataValue());

        $f = new TimeField('Time', 'Time');
        $f->setValue('11:59 pm');
        $this->assertEquals('23:59:00', $f->dataValue());

        $f = new TimeField('Time', 'Time');
        $f->setValue('23:59');
        $this->assertEquals('23:59:00', $f->dataValue());

        $f = new TimeField('Time', 'Time');
        $f->setValue('23:59:38');
        $this->assertEquals('23:59:38', $f->dataValue());

        $f = new TimeField('Time', 'Time');
        $f->setValue('12:00 am');
        $this->assertEquals($f->dataValue(), '00:00:00');
    }

    public function testOverrideWithNull()
    {
        $field = new TimeField('Time', 'Time');
        $field->setValue('11:00:00');
        $field->setValue('');
        $this->assertEquals($field->dataValue(), '');
    }

    /**
     * Test that AM/PM is preserved correctly in various situations
     */
    public function testSetTimeFormat()
    {

        // Test with timeformat that includes hour

        // Check pm
        $f = new TimeField('Time', 'Time');
        $f->setHTML5(false);
        $f->setTimeFormat('h:mm:ss a');
        $f->setValue('3:59 pm');
        $this->assertEquals($f->dataValue(), '15:59:00');

        // Check am
        $f = new TimeField('Time', 'Time');
        $f->setHTML5(false);
        $f->setTimeFormat('h:mm:ss a');
        $f->setValue('3:59 am');
        $this->assertEquals($f->dataValue(), '03:59:00');

        // Check with ISO date/time
        $f = new TimeField('Time', 'Time');
        $f->setHTML5(false);
        $f->setTimeFormat('h:mm:ss a');
        $f->setValue('15:59:00');
        $this->assertEquals($f->dataValue(), '15:59:00');

        // ISO am
        $f = new TimeField('Time', 'Time');
        $f->setHTML5(false);
        $f->setTimeFormat('h:mm:ss a');
        $f->setValue('03:59:00');
        $this->assertEquals($f->dataValue(), '03:59:00');
    }

    public function testLenientSubmissionParseWithoutSecondsOnHtml5()
    {
        $f = new TimeField('Time', 'Time');
        $f->setSubmittedValue('23:59');
        $this->assertEquals($f->Value(), '23:59:00');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessageRegExp /Please opt-out .* if using setTimeFormat/
     */
    public function testHtml5WithCustomFormatThrowsException()
    {
        $f = new TimeField('Time', 'Time');
        $f->setValue('15:59:00');
        $f->setTimeFormat('mm:HH');
        $f->Value();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessageRegExp /Please opt-out .* if using setTimeLength/
     */
    public function testHtml5WithCustomDateLengthThrowsException()
    {
        $f = new TimeField('Time', 'Time');
        $f->setValue('15:59:00');
        $f->setTimeLength(IntlDateFormatter::MEDIUM);
        $f->Value();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessageRegExp /Please opt-out .* if using setLocale/
     */
    public function testHtml5WithCustomLocaleThrowsException()
    {
        $f = new TimeField('Time', 'Time');
        $f->setValue('15:59:00');
        $f->setLocale('de_DE');
        $f->Value();
    }
}
