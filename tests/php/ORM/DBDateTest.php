<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;

/**
 * @skipUpgrade
 */
class DBDateTest extends SapphireTest
{
    protected $oldError = null;

    protected function setUp()
    {
        parent::setUp();
        $this->oldError = error_reporting();
        // Validate setup
        assert(date_default_timezone_get() === 'UTC');
        i18n::set_locale('en_NZ');
    }

    protected function tearDown()
    {
        $this->restoreNotices();
        parent::tearDown();
    }

    /**
     * Temporarily disable notices
     */
    protected function suppressNotices()
    {
        error_reporting(error_reporting() & ~E_USER_NOTICE);
        \PHPUnit_Framework_Error_Notice::$enabled = false;
    }

    /**
     * Restore notices
     */
    protected function restoreNotices()
    {
        error_reporting($this->oldError);
        \PHPUnit_Framework_Error_Notice::$enabled = true;
    }

    public function testNiceDate()
    {
        $this->assertEquals(
            '31/03/2008',
            DBField::create_field('Date', 1206968400)->Nice(),
            "Date->Nice() works with timestamp integers"
        );
        $this->assertEquals(
            '30/03/2008',
            DBField::create_field('Date', 1206882000)->Nice(),
            "Date->Nice() works with timestamp integers"
        );
        $this->assertEquals(
            '31/03/2008',
            DBField::create_field('Date', '1206968400')->Nice(),
            "Date->Nice() works with timestamp strings"
        );
        $this->assertEquals(
            '30/03/2008',
            DBField::create_field('Date', '1206882000')->Nice(),
            "Date->Nice() works with timestamp strings"
        );
        $this->assertEquals(
            '4/03/2003',
            DBField::create_field('Date', '4.3.2003')->Nice(),
            "Date->Nice() works with D.M.YYYY format"
        );
        $this->assertEquals(
            '4/03/2003',
            DBField::create_field('Date', '04.03.2003')->Nice(),
            "Date->Nice() works with DD.MM.YYYY format"
        );
        $this->assertEquals(
            '4/03/2003',
            DBField::create_field('Date', '2003-3-4')->Nice(),
            "Date->Nice() works with YYYY-M-D format"
        );
        $this->assertEquals(
            '4/03/2003',
            DBField::create_field('Date', '2003-03-04')->Nice(),
            "Date->Nice() works with YYYY-MM-DD format"
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid date: '3/16/2003'. Use y-MM-dd to prevent this error.
     */
    public function testMDYConversion()
    {
        DBField::create_field('Date', '3/16/2003');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid date: '03-03-04'. Use y-MM-dd to prevent this error.
     */
    public function testY2kCorrection()
    {
        DBField::create_field('Date', '03-03-04');
    }

    public function testInvertedYearCorrection()
    {
        // iso8601 expects year first, but support year last
        $this->assertEquals(
            '4/03/2003',
            DBField::create_field('Date', '04-03-2003')->Nice(),
            "Date->Nice() works with DD-MM-YYYY format"
        );
    }

    public function testYear()
    {
        $this->assertEquals(
            '2008',
            DBField::create_field('Date', 1206968400)->Year(),
            "Date->Year() works with timestamp integers"
        );
    }

    public function testDayOfWeek()
    {
        $this->assertEquals(
            'Monday',
            DBField::create_field('Date', 1206968400)->DayOfWeek(),
            "Date->Day() works with timestamp integers"
        );
    }

    public function testMonth()
    {
        $this->assertEquals(
            'March',
            DBField::create_field('Date', 1206968400)->Month(),
            "Date->Month() works with timestamp integers"
        );
    }

    public function testShortMonth()
    {
        $this->assertEquals(
            'Mar',
            DBField::create_field('Date', 1206968400)->ShortMonth(),
            "Date->ShortMonth() works with timestamp integers"
        );
    }

    public function testLongDate()
    {
        $this->assertEquals(
            '31 March 2008',
            DBField::create_field('Date', 1206968400)->Long(),
            "Date->Long() works with numeric timestamp"
        );
        $this->assertEquals(
            '31 March 2008',
            DBField::create_field('Date', '1206968400')->Long(),
            "Date->Long() works with string timestamp"
        );
        $this->assertEquals(
            '30 March 2008',
            DBField::create_field('Date', 1206882000)->Long(),
            "Date->Long() works with numeric timestamp"
        );
        $this->assertEquals(
            '30 March 2008',
            DBField::create_field('Date', '1206882000')->Long(),
            "Date->Long() works with numeric timestamp"
        );
        $this->assertEquals(
            '3 April 2003',
            DBField::create_field('Date', '2003-4-3')->Long(),
            "Date->Long() works with YYYY-M-D"
        );
        $this->assertEquals(
            '3 April 2003',
            DBField::create_field('Date', '3.4.2003')->Long(),
            "Date->Long() works with D.M.YYYY"
        );
    }

    public function testFull()
    {
        $this->assertEquals(
            'Monday, 31 March 2008',
            DBField::create_field('Date', 1206968400)->Full(),
            "Date->Full() works with timestamp integers"
        );
    }

    public function testSetNullAndZeroValues()
    {
        $date = DBField::create_field('Date', '');
        $this->assertNull($date->getValue(), 'Empty string evaluates to NULL');

        $date = DBField::create_field('Date', null);
        $this->assertNull($date->getValue(), 'NULL is set as NULL');

        $date = DBField::create_field('Date', false);
        $this->assertNull($date->getValue(), 'Boolean FALSE evaluates to NULL');

        $date = DBField::create_field('Date', array());
        $this->assertNull($date->getValue(), 'Empty array evaluates to NULL');

        $date = DBField::create_field('Date', '0');
        $this->assertEquals('1970-01-01', $date->getValue(), 'Zero is UNIX epoch date');

        $date = DBField::create_field('Date', 0);
        $this->assertEquals('1970-01-01', $date->getValue(), 'Zero is UNIX epoch date');

        $date = DBField::create_field('Date', '0000-00-00 00:00:00');
        $this->assertNull($date->getValue(), '0000-00-00 00:00:00 is set as NULL');

        $date = DBField::create_field('Date', '00/00/0000');
        $this->assertNull($date->getValue(), '00/00/0000 is set as NULL');
    }

    public function testDayOfMonth()
    {
        $date = DBField::create_field('Date', '2000-10-10');
        $this->assertEquals('10', $date->DayOfMonth());
        $this->assertEquals('10th', $date->DayOfMonth(true));

        $range = $date->RangeString(DBField::create_field('Date', '2000-10-20'));
        $this->assertEquals('10 - 20 Oct 2000', $range);
        $range = $date->RangeString(DBField::create_field('Date', '2000-10-20'), true);
        $this->assertEquals('10th - 20th Oct 2000', $range);
    }

    public function testExtendedDates()
    {
        $date = DBField::create_field('Date', '1800-10-10');
        $this->assertEquals('10 Oct 1800', $date->Format('dd MMM y'));

        // Note: Fails around 1500 or older
        $date = DBField::create_field('Date', '1600-10-10');
        $this->assertEquals('10 Oct 1600', $date->Format('dd MMM y'));

        $date = DBField::create_field('Date', '3000-4-3');
        $this->assertEquals('03 Apr 3000', $date->Format('dd MMM y'));
    }

    public function testAgoInPast()
    {
        DBDatetime::set_mock_now('2000-12-31 12:00:00');

        $this->assertEquals(
            '1 month ago',
            DBField::create_field('Date', '2000-11-26')->Ago(true, 1),
            'Past match on days, less than two months, lowest significance'
        );

        $this->assertEquals(
            '50 days ago', // Rounded from 49.5 days up
            DBField::create_field('Date', '2000-11-12')->Ago(),
            'Past match on days, less than two months'
        );

        $this->assertEquals(
            '2 months ago',
            DBField::create_field('Date', '2000-10-27')->Ago(),
            'Past match on days, over two months'
        );

        $this->assertEquals(
            '66 days ago', // rounded from 65.5 days up
            DBField::create_field('Date', '2000-10-27')->Ago(true, 3),
            'Past match on days, over two months, significance of 3'
        );

        $this->assertEquals(
            '10 years ago',
            DBField::create_field('Date', '1990-12-31')->Ago(),
            'Exact past match on years'
        );

        $this->assertEquals(
            '10 years ago',
            DBField::create_field('Date', '1990-12-30')->Ago(),
            'Approximate past match on years'
        );

        $this->assertEquals(
            '1 year ago',
            DBField::create_field('Date', '1999-12-30')->Ago(true, 1),
            'Approximate past match in singular, lowest significance'
        );

        $this->assertEquals(
            '12 months ago',
            DBField::create_field('Date', '1999-12-30')->Ago(),
            'Approximate past match in singular'
        );

        DBDatetime::clear_mock_now();
    }

    public function testAgoInFuture()
    {
        DBDatetime::set_mock_now('2000-12-31 00:00:00');

        $this->assertEquals(
            'in 10 years',
            DBField::create_field('Date', '2010-12-31')->Ago(),
            'Exact past match on years'
        );

        $this->assertEquals(
            'in 1 day',
            DBField::create_field('Date', '2001-01-01')->Ago(true, 1),
            'Approximate past match on minutes'
        );

        $this->assertEquals(
            'in 24 hours',
            DBField::create_field('Date', '2001-01-01')->Ago(),
            'Approximate past match on minutes'
        );

        DBDatetime::clear_mock_now();
    }

    public function testRfc3999()
    {
        // Dates should be formatted as: 2018-01-24T14:05:53+00:00
        $date = DBDate::create_field('Date', '2010-12-31');
        $this->assertEquals('2010-12-31T00:00:00+00:00', $date->Rfc3339());
    }
}
