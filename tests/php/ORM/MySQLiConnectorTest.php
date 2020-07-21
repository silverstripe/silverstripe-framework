<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\Tests\MySQLiConnectorTest\MySQLiConnector;
use SilverStripe\ORM\DB;

/**
 * @requires extension mysqli
 */
class MySQLiConnectorTest extends SapphireTest implements TestOnly
{
    /**
     * @param $charset
     * @param $customCollation
     * @dataProvider charsetProvider
     */
    public function testConnectionCollationControl($charset, $customCollation)
    {
        $config = DB::getConfig();
        $config['charset'] = $charset;
        $config['collation'] = $customCollation;

        $connector = new MySQLiConnector();
        $connector->connect($config);
        $connection = $connector->getMysqliConnection();

        $connectionCharset = $connection->get_charset();

        $this->assertEquals($charset, $connectionCharset->charset);
        $this->assertEquals($customCollation, $connectionCharset->collation);

        $connection->close();
        unset($connectionCharset, $connection, $connector, $config);
    }

    public function charsetProvider()
    {
        return [
            ['ascii', 'ascii_bin'],
            ['utf8', 'utf8_unicode_520_ci'],
            ['utf8mb4', 'utf8mb4_unicode_520_ci']
        ];
    }
}
