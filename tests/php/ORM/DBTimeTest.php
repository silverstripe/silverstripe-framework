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
        $this->assertRegexp('#5:15:55 PM#i', $time->Nice());
    }

    public function testShort()
    {
        $time = DBTime::create_field('Time', '17:15:55');
        $this->assertRegexp('#5:15 PM#i', $time->Short());
    }
}
