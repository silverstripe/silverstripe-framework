<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\FieldType\DBDatetime;

/**
 * Tests for {@link Datetime} class.
 */
class DBDatetimeTest extends SapphireTest
{
    protected function setUp(): void
    {
        parent::setUp();
        i18n::set_locale('en_NZ');
    }

    public function testNowWithSystemDate()
    {
        $systemDatetime = DBDatetime::create_field('Datetime', date('Y-m-d H:i:s'));
        $nowDatetime = DBDatetime::now();

        $this->assertEquals($systemDatetime->Date(), $nowDatetime->Date());
    }

    public function testNowWithMockDate()
    {
        // Test setting
        $mockDate = '2001-12-31 22:10:59';
        DBDatetime::set_mock_now($mockDate);
        $systemDatetime = DBDatetime::create_field('Datetime', date('Y-m-d H:i:s'));
        $nowDatetime = DBDatetime::now();
        $this->assertNotEquals($systemDatetime->Date(), $nowDatetime->Date());
        $this->assertEquals($nowDatetime->getValue(), $mockDate);

        // Test clearing
        DBDatetime::clear_mock_now();
        $systemDatetime = DBDatetime::create_field('Datetime', date('Y-m-d H:i:s'));
        $nowDatetime = DBDatetime::now();
        $this->assertEquals($systemDatetime->Date(), $nowDatetime->Date());
    }

    public function testSetNullAndZeroValues()
    {
        $date = DBDatetime::create_field('Datetime', '');
        $this->assertNull($date->getValue(), 'Empty string evaluates to NULL');

        $date = DBDatetime::create_field('Datetime', null);
        $this->assertNull($date->getValue(), 'NULL is set as NULL');

        $date = DBDatetime::create_field('Datetime', false);
        $this->assertNull($date->getValue(), 'Boolean FALSE evaluates to NULL');

        $date = DBDatetime::create_field('Datetime', '0');
        $this->assertEquals('1970-01-01 00:00:00', $date->getValue(), 'String zero is UNIX epoch time');

        $date = DBDatetime::create_field('Datetime', 0);
        $this->assertEquals('1970-01-01 00:00:00', $date->getValue(), 'Numeric zero is UNIX epoch time');
    }

    public function testExtendedDateTimes()
    {
        $date = DBDatetime::create_field('Datetime', '1600-10-10 15:32:24');
        $this->assertEquals('10 Oct 1600 15 32 24', $date->Format('d MMM y H m s'));

        $date = DBDatetime::create_field('Datetime', '3000-10-10 15:32:24');
        $this->assertEquals('10 Oct 3000 15 32 24', $date->Format('d MMM y H m s'));
    }

    /**
     * Coverage for dates using hindi-numerals
     */
    public function testHindiNumerals()
    {
        // Parent locale is english; Can be localised to arabic
        $date = DBDatetime::create_field('Datetime', '1600-10-10 15:32:24');
        $this->assertEquals('10 Oct 1600 15 32 24', $date->Format('d MMM y H m s'));
        $this->assertEquals('١٠ أكتوبر ١٦٠٠ ١٥ ٣٢ ٢٤', $date->Format('d MMM y H m s', 'ar'));

        // Parent locale is arabic; Datavalue uses ISO date
        i18n::set_locale('ar');
        $date = DBDatetime::create_field('Datetime', '1600-10-10 15:32:24');
        $this->assertEquals('١٠ أكتوبر ١٦٠٠ ١٥ ٣٢ ٢٤', $date->Format('d MMM y H m s'));
        $this->assertEquals('1600-10-10 15:32:24', $date->getValue());
    }

    public function testNice()
    {
        $date = DBDatetime::create_field('Datetime', '2001-12-11 22:10:59');

        // note: Some localisation packages exclude the ',' in default medium format
        i18n::set_locale('en_NZ');
        $this->assertRegExp('#11/12/2001(,)? 10:10 PM#i', $date->Nice());

        i18n::set_locale('en_US');
        $this->assertRegExp('#Dec 11(,)? 2001(,)? 10:10 PM#i', $date->Nice());
    }

    public function testDate()
    {
        $date = DBDatetime::create_field('Datetime', '2001-12-31 22:10:59');
        $this->assertEquals('31/12/2001', $date->Date());
    }

    public function testTime()
    {
        $date = DBDatetime::create_field('Datetime', '2001-12-31 22:10:59');
        $this->assertRegexp('#10:10:59 PM#i', $date->Time());
    }

    public function testTime24()
    {
        $date = DBDatetime::create_field('Datetime', '2001-12-31 22:10:59');
        $this->assertEquals('22:10', $date->Time24());
    }

    public function testURLDateTime()
    {
        $date = DBDatetime::create_field('Datetime', '2001-12-31 22:10:59');
        $this->assertEquals('2001-12-31%2022%3A10%3A59', $date->URLDateTime());
    }

    public function testAgoInPast()
    {
        DBDatetime::set_mock_now('2000-12-31 12:00:00');

        $this->assertEquals(
            '10 years ago',
            DBDatetime::create_field('Datetime', '1990-12-31 12:00:00')->Ago(),
            'Exact past match on years'
        );

        $this->assertEquals(
            '10 years ago',
            DBDatetime::create_field('Datetime', '1990-12-30 12:00:00')->Ago(),
            'Approximate past match on years'
        );

        $this->assertEquals(
            '1 year ago',
            DBDatetime::create_field('Datetime', '1999-12-30 12:00:12')->Ago(true, 1),
            'Approximate past match in singular, significance=1'
        );

        $this->assertEquals(
            '12 months ago',
            DBDatetime::create_field('Datetime', '1999-12-30 12:00:12')->Ago(),
            'Approximate past match in singular'
        );

        $this->assertEquals(
            '50 mins ago',
            DBDatetime::create_field('Datetime', '2000-12-31 11:10:11')->Ago(),
            'Approximate past match on minutes'
        );

        $this->assertEquals(
            '59 secs ago',
            DBDatetime::create_field('Datetime', '2000-12-31 11:59:01')->Ago(),
            'Approximate past match on seconds'
        );

        $this->assertEquals(
            'less than a minute ago',
            DBDatetime::create_field('Datetime', '2000-12-31 11:59:01')->Ago(false),
            'Approximate past match on seconds with $includeSeconds=false'
        );

        $this->assertEquals(
            '1 min ago',
            DBDatetime::create_field('Datetime', '2000-12-31 11:58:50')->Ago(false),
            'Test between 1 and 2 minutes with includeSeconds=false'
        );

        $this->assertEquals(
            '70 secs ago',
            DBDatetime::create_field('Datetime', '2000-12-31 11:58:50')->Ago(true),
            'Test between 1 and 2 minutes with includeSeconds=true'
        );

        $this->assertEquals(
            '4 mins ago',
            DBDatetime::create_field('Datetime', '2000-12-31 11:55:50')->Ago(),
            'Past match on minutes'
        );

        $this->assertEquals(
            '1 hour ago',
            DBDatetime::create_field('Datetime', '2000-12-31 10:50:58')->Ago(true, 1),
            'Past match on hours, significance=1'
        );

        $this->assertEquals(
            '3 hours ago',
            DBDatetime::create_field('Datetime', '2000-12-31 08:50:58')->Ago(),
            'Past match on hours'
        );

        DBDatetime::clear_mock_now();
    }

    public function testAgoInFuture()
    {
        DBDatetime::set_mock_now('2000-12-31 00:00:00');

        $this->assertEquals(
            'in 10 years',
            DBDatetime::create_field('Datetime', '2010-12-31 12:00:00')->Ago(),
            'Exact past match on years'
        );

        $this->assertEquals(
            'in 1 hour',
            DBDatetime::create_field('Datetime', '2000-12-31 1:01:05')->Ago(true, 1),
            'Approximate past match on minutes, significance=1'
        );

        $this->assertEquals(
            'in 61 mins',
            DBDatetime::create_field('Datetime', '2000-12-31 1:01:05')->Ago(),
            'Approximate past match on minutes'
        );

        DBDatetime::clear_mock_now();
    }

    public function testRfc3999()
    {
        // Dates should be formatted as: 2018-01-24T14:05:53+00:00
        $date = DBDatetime::create_field('Datetime', '2010-12-31 16:58:59');
        $this->assertEquals('2010-12-31T16:58:59+00:00', $date->Rfc3339());
    }

    /**
     * @param string $adjustment
     * @param string $expected
     * @dataProvider modifyProvider
     */
    public function testModify($adjustment, $expected)
    {
        DBDatetime::set_mock_now('2019-03-03 12:00:00');
        $result = DBDatetime::now()->modify($adjustment)->Rfc2822();
        $this->assertSame($expected, $result);
    }

    /**
     * @return array[]
     */
    public function modifyProvider()
    {
        return [
            ['+1 day', '2019-03-04 12:00:00'],
            ['-1 day', '2019-03-02 12:00:00'],
            ['+24 hours', '2019-03-04 12:00:00'],
            ['-24 hours', '2019-03-02 12:00:00'],
            ['+2 weeks', '2019-03-17 12:00:00'],
            ['-2 weeks', '2019-02-17 12:00:00'],
            ['+2 years', '2021-03-03 12:00:00'],
            ['-2 years', '2017-03-03 12:00:00'],
            ['+35 minutes', '2019-03-03 12:35:00'],
            ['-35 minutes', '2019-03-03 11:25:00'],
            ['+3 hours', '2019-03-03 15:00:00'],
            ['-3 hours', '2019-03-03 09:00:00'],
            ['+59 seconds', '2019-03-03 12:00:59'],
            ['-59 seconds', '2019-03-03 11:59:01'],
        ];
    }
}
