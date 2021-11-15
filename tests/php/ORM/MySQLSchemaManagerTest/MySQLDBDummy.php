<?php

namespace SilverStripe\ORM\Tests\MySQLSchemaManagerTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\Connect\MySQLDatabase;

class MySQLDBDummy extends MySQLDatabase implements TestOnly
{
    private $dbVersion;

    public function __construct($version)
    {
        $this->dbVersion = $version;
    }

    public function getVersion()
    {
        return $this->dbVersion;
    }
}
