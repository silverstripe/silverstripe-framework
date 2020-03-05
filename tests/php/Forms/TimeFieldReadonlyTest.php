<?php
namespace SilverStripe\Forms;

use IntlDateFormatter;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\TimeField;
use SilverStripe\Forms\TimeField_Readonly;
use SilverStripe\i18n\i18n;

class TimeFieldReadonlyTest extends SapphireTest
{
    protected function setUp()
    {
        parent::setUp();
        i18n::set_locale('en_NZ');
    }
    
    public function testPerformReadonly()
    {
        $field = new TimeField('Time', 'Time', '23:00:00');
        $roField = $field->performReadonlyTransformation();
        $this->assertInstanceOf(TimeField_Readonly::class, $roField);
        
        $this->assertTrue($roField->isReadonly());
        $this->assertEquals($roField->dataValue(), '23:00:00');
    }
    
    public function testSettingsCarryOver()
    {
        $field = new TimeField('Time', 'Time');
        $field
            ->setHTML5(false)
            ->setTimeFormat('KK:mma')
            ->setTimezone('America/Halifax')
            ->setLocale('en_US')
            ->setTimeLength(IntlDateFormatter::SHORT)
            ->setValue('23:00:00');
        
        $roField = $field->performReadonlyTransformation();
        $this->assertFalse($roField->getHTML5());
        $this->assertEquals($roField->getTimeFormat(), 'KK:mma');
        $this->assertEquals($roField->getTimezone(), 'America/Halifax');
        $this->assertEquals($roField->getLocale(), 'en_US');
        $this->assertEquals($roField->getTimeLength(), IntlDateFormatter::SHORT);
    }
}
