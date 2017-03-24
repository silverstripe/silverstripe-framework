<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\i18n\i18n;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBTime;
use SilverStripe\Security\Member;

class DBTimeTest extends SapphireTest
{
    protected function setUp()
    {
        parent::setUp();
        i18n::set_locale('en_NZ');
    }

    public function dataTestParse()
    {
        return [
            // Test am-pm conversion
            ['11:01 pm', '23:01:00'],
            ['11:01 am', '11:01:00'],
            ['12:01 pm', '12:01:00'],
            ['12:01 am', '00:01:00'],
            // Test seconds
            ['11:01.01 pm', '23:01:01'],
            ['12:01.01', '12:01:01'],
        ];
    }

    /**
     * @dataProvider dataTestParse
     * @param string $input
     * @param string $expected
     */
    public function testParse($input, $expected)
    {
        $time = DBField::create_field('Time', $input);
        $this->assertEquals(
            $expected,
            $time->getValue(),
            "Date parsed from {$input} should be {$expected}"
        );
    }

    public function testNice()
    {
        $time = DBTime::create_field('Time', '17:15:55');
        $this->assertEquals('5:15:55 PM', $time->Nice());
    }

    public function testShort()
    {
        $time = DBTime::create_field('Time', '17:15:55');
        $this->assertEquals('5:15 PM', $time->Short());
    }

    public function dataTestFormatFromSettings()
    {
        return [
            ['10:11:01', '10:11:01 (AM)'],
            ['21:11:01', '9:11:01 (PM)'],
        ];
    }

    /**
     * @dataProvider dataTestFormatFromSettings
     * @param string $from
     * @param string $to
     */
    public function testFormatFromSettings($from, $to)
    {
        $member = new Member();
        $member->TimeFormat = 'h:mm:ss (a)';

        $date = DBTime::create_field('Time', $from);
        $this->assertEquals($to, $date->FormatFromSettings($member));
    }

    /**
     * Test that FormatFromSettings without a member defaults to Nice()
     */
    public function testFormatFromSettingsEmpty()
    {
        $date = DBTime::create_field('Time', '10:11:01');
        $this->assertEquals('10:11:01 AM', $date->FormatFromSettings());
    }
}
