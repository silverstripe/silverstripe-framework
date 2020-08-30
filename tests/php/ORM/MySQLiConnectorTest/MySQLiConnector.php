<?php

namespace SilverStripe\ORM\Tests\MySQLiConnectorTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\Connect\MySQLiConnector as OriginalMySQLiConnector;
use mysqli;

class MySQLiConnector extends OriginalMySQLiConnector implements TestOnly
{
    public function getMysqliConnection(): mysqli
    {
        return $this->dbConn;
    }
}
