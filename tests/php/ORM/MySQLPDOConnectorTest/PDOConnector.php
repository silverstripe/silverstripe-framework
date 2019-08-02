<?php

namespace SilverStripe\ORM\Tests\MySQLPDOConnectorTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\Connect\PDOConnector as OriginalPDOConnector;
use PDO;

class PDOConnector extends OriginalPDOConnector implements TestOnly
{
    public function getPDOConnection(): PDO
    {
        return $this->pdoConnection;
    }
}
