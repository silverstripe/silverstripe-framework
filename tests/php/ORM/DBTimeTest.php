<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\i18n\i18n;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBTime;
use SilverStripe\Security\Member;
use PHPUnit\Framework\Attributes\DataProvider;

class DBTimeTest extends SapphireTest
{
    protected function setUp(): void
    {
        parent::setUp();
        i18n::set_locale('en_NZ');
    }

    public static function provideSetValue()
    {
        return [
            'time-11pm' => [
                'value' => '11:01 pm',
                'expected' => '23:01:00'
            ],
            'time-11am' => [
                'value' => '11:01 am',
                'expected' => '11:01:00'
            ],
            'time-12am' => [
                'value' => '12:01 am',
                'expected' => '00:01:00'
            ],
            'time-12pm' => [
                'value' => '12:01 pm',
                'expected' => '12:01:00'
            ],
            'time-11pm-seconds' => [
                'value' => '11:01.01 pm',
                'expected' => '23:01:01'
            ],
            'time-12-seconds' => [
                'value' => '12:01.01',
                'expected' => '12:01:01'
            ],
            'wrong-format-works' => [
                'value' => '12.34.56',
                'expected' => '12:34:56',
            ],
            'int' => [
                'value' => 6789,
                'expected' => '01:53:09'
            ],
            'int-string' => [
                'value' => '6789',
                'expected' => '01:53:09'
            ],
            'zero-string' => [
                'value' => '0',
                'expected' => '00:00:00'
            ],
            'zero-int' => [
                'value' => 0,
                'expected' => '00:00:00'
            ],
            'blank-string' => [
                'value' => '',
                'expected' => ''
            ],
            'null' => [
                'value' => null,
                'expected' => null
            ],
            'false' => [
                'value' => false,
                'expected' => false
            ],
            'empty-array' => [
                'value' => [],
                'expected' => []
            ],
        ];
    }

    #[DataProvider('provideSetValue')]
    public function testSetValue(mixed $value, mixed $expected)
    {
        $field = new DBTime('MyField');
        $field->setValue($value);
        $this->assertSame($expected, $field->getValue());
    }

    public function testNice()
    {
        $time = DBTime::create_field('Time', '17:15:55');
        $this->assertMatchesRegularExpression('#5:15:55\hPM#iu', $time->Nice());
    }

    public function testShort()
    {
        $time = DBTime::create_field('Time', '17:15:55');
        $this->assertMatchesRegularExpression('#5:15\hPM#iu', $time->Short());
    }
}
