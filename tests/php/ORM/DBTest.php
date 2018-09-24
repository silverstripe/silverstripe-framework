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
}
