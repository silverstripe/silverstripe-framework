<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\ORM\DB;
use SilverStripe\Dev\SapphireTest;

class DBTest extends SapphireTest
{

    public function testValidAlternativeDatabaseName()
    {
        /** @var Kernel $kernel */
        $kernel = Injector::inst()->get(Kernel::class);
        $prefix = Environment::getEnv('SS_DATABASE_PREFIX') ?: 'ss_';

        $kernel->setEnvironment(Kernel::DEV);
        $this->assertTrue(DB::valid_alternative_database_name($prefix . 'tmpdb1234567'));
        $this->assertFalse(DB::valid_alternative_database_name($prefix . 'tmpdb12345678'));
        $this->assertFalse(DB::valid_alternative_database_name('tmpdb1234567'));
        $this->assertFalse(DB::valid_alternative_database_name('random'));
        $this->assertFalse(DB::valid_alternative_database_name(''));

        $kernel->setEnvironment(Kernel::LIVE);
        $this->assertFalse(DB::valid_alternative_database_name($prefix . 'tmpdb1234567'));

        $kernel->setEnvironment(Kernel::DEV);
    }

    public function replaceParameterDataProvider()
    {
        return [
            // Test the escape parameter
            ['select ? from dummy', 0, 'ID', false, 'select \'ID\' from dummy'],
            ['select ? from dummy', 0, 'ID', true, 'select ID from dummy'],
            ['select * from dummy where ID = ?', 0, 123, false, 'select * from dummy where ID = 123'],
            ['select * from dummy where enabled = ?', 0, false, false, 'select * from dummy where enabled = 0'],
            ['select * from dummy where enabled = ?', 0, true, false, 'select * from dummy where enabled = 1'],

            // Make sure we don't substitute ? in strings
            [
                'select * from dummy where Title like \'?\' AND ID = ?',
                0, '1', false,
                'select * from dummy where Title like \'?\' AND ID = \'1\''
            ],
            [
                'select * from dummy where Title like \'Start of ?\' AND ID = ?',
                0, '1', false,
                'select * from dummy where Title like \'Start of ?\' AND ID = \'1\''
            ],

            // Make sure we replace ? in the right order
            ['select ? from ?', 0, 'ID', true, 'select ID from ?'],
            ['select ? from ?', 1, 'DummyTbl', true, 'select ? from DummyTbl'],
        ];
    }

    /**
     * @dataProvider replaceParameterDataProvider
     */
    public function testReplaceParameter($sql, $paramIdx, $replacement, $skipEscaping, $expected)
    {
        $actual = DB::replace_parameter($sql, $paramIdx, $replacement, $skipEscaping);
        $this->assertEquals($expected, $actual);
    }
}
