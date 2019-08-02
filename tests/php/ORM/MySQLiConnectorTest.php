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
     * @dataProvider charsetProvider
     */
    public function testConnectionCharsetControl($charset, $defaultCollation)
    {
        $config = DB::getConfig();
        $config['charset'] = $charset;

        $connector = new MySQLiConnector();
        $connector->connect($config);
        $connection = $connector->getMysqliConnection();

        $cset = $connection->get_charset();

        $this->assertEquals($charset, $cset->charset);
        $this->assertEquals($defaultCollation, $cset->collation);

        unset($cset, $connection, $connector, $config);
    }

    /**
     * @depends testConnectionCharsetControl
     * @dataProvider charsetProvider
     */
    public function testConnectionCollationControl($charset, $defaultCollation, $customCollation)
    {
        $config = DB::getConfig();
        $config['charset'] = $charset;
        $config['collation'] = $customCollation;

        $connector = new MySQLiConnector();
        $connector->connect($config);
        $connection = $connector->getMysqliConnection();

        $cset = $connection->get_charset();

        $this->assertEquals($charset, $cset->charset);
        $this->assertEquals($customCollation, $cset->collation);

        $connection->close();
        unset($cset, $connection, $connector, $config);
    }

    public function charsetProvider()
    {
        return [
            ['ascii', 'ascii_general_ci', 'ascii_bin'],
            ['utf8', 'utf8_general_ci', 'utf8_unicode_520_ci'],
            ['utf8mb4', 'utf8mb4_general_ci', 'utf8mb4_unicode_520_ci']
        ];
    }
}
