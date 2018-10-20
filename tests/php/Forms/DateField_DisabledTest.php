<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DateField_Disabled;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\FieldType\DBDatetime;

/**
 * @skipUpgrade
 */
class DateField_DisabledTest extends SapphireTest
{
    protected function setUp()
    {
        parent::setUp();
        i18n::set_locale('en_NZ');
        DBDatetime::set_mock_now('2011-02-01 8:34:00');
    }

    public function testFieldToday()
    {
        // Today date with normal local
        $actual = DateField_Disabled::create('Test')
            ->setValue('2011-02-01')
            ->Field();
        $expected = '<span class="readonly" id="Test">1/02/2011 (today)</span>';
        $this->assertEquals($expected, $actual);

        // Test today's date with time
        $actual = DateField_Disabled::create('Test')
            ->setValue('2011-02-01 10:34:00')
            ->Field();
        $this->assertEquals($expected, $actual);
    }

    public function testFieldWithDifferentDay()
    {
        // Test past
        $actual = DateField_Disabled::create('Test')
            ->setValue('2011-01-27')
            ->Field();
        $expected = '<span class="readonly" id="Test">27/01/2011, 5 days ago</span>';
        $this->assertEquals($expected, $actual);

        // Test future
        $actual = DateField_Disabled::create('Test')
            ->setValue('2011-02-06')
            ->Field();
        $expected = '<span class="readonly" id="Test">6/02/2011, in 5 days</span>';
        $this->assertEquals($expected, $actual);
    }

    public function testFieldWithDifferentLocal()
    {
        // Test different local
        $actual = DateField_Disabled::create('Test')
            ->setValue('2011-02-06')
            ->setHTML5(false)
            ->setLocale('de_DE')
            ->Field();
        $expected = '<span class="readonly" id="Test">06.02.2011, in 5 days</span>';
        $this->assertEquals($expected, $actual);
    }

    public function testFieldWithNonValue()
    {
        // Test none value
        $actual = DateField_Disabled::create('Test')->Field();
        $expected = '<span class="readonly" id="Test"><i>(not set)</i></span>';
        $this->assertEquals($expected, $actual);

        $actual = DateField_Disabled::create('Test')->setValue('This is not a date')->Field();
        $this->assertEquals($expected, $actual);
    }

    public function testType()
    {
        $field = new DateField_Disabled('Test');
        $result = $field->Type();
        $this->assertContains('readonly', $result, 'Disabled field should be treated as readonly');
        $this->assertContains('date_disabled', $result, 'Field should contain date_disabled class');
    }
}
