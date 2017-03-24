<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\SeparatedDateField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\FieldType\DBDatetime;

class SeparatedDateFieldTest extends SapphireTest
{
    protected function setUp()
    {
        parent::setUp();
        i18n::set_locale('en_NZ');
        DBDatetime::set_mock_now('2011-02-01 8:34:00');
    }

    public function testFieldOrderingBasedOnLocale()
    {
        $dateField = new SeparatedDateField('Date');
        $dateField->setLocale('en_NZ');
        $this->assertRegExp('/.*[day].*[month].*[year]/', $dateField->Field());
    }

    public function testFieldOrderingBasedOnDateFormat()
    {
        $dateField = new SeparatedDateField('Date');
        $dateField->setDateFormat('y/MM/dd');
        $this->assertRegExp('/.*[year].*[month].*[day]/', $dateField->Field());
    }

    public function testCustomSeparator()
    {
        $dateField = new SeparatedDateField('Date');
        $dateField->setDateFormat('dd/MM/y');
        $dateField->setSeparator('###');
        $this->assertRegExp('/.*[day].*###.*[month].*###.*[day]/', $dateField->Field());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid date format
     */
    public function testInvalidDateFormat()
    {
        $dateField = new SeparatedDateField('Date');
        $dateField->setDateFormat('y/MM');
        $dateField->Field();
    }
}
