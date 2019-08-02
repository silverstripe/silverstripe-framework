<?php

namespace SilverStripe\ORM\Tests;

use PDO;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\Tests\MySQLPDOConnectorTest\PDOConnector;
use SilverStripe\ORM\DB;


/**
 * @requires extension PDO
 * @requires extension pdo_mysql
 */
class MySQLPDOConnectorTest extends SapphireTest implements TestOnly
{
    /**
     * @dataProvider charsetProvider
     */
    public function testConnectionCharsetControl($charset, $defaultCollation)
    {
        $config = DB::getConfig();
        $config['driver'] = 'mysql';
        $config['charset'] = $charset;
        Config::inst()->set(MySQLDatabase::class, 'connection_collation', $defaultCollation);

        $connector = new PDOConnector();
        $connector->connect($config);
        $connection = $connector->getPDOConnection();

        $cset = $connection->query('show variables like "character_set_connection"')->fetch(PDO::FETCH_NUM)[1];
        $collation = $connection->query('show variables like "collation_connection"')->fetch(PDO::FETCH_NUM)[1];

        $this->assertEquals($charset, $cset);
        $this->assertEquals($defaultCollation, $collation);

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
        $config['driver'] = 'mysql';
        Config::inst()->set(MySQLDatabase::class, 'connection_collation', $customCollation);

        $connector = new PDOConnector();
        $connector->connect($config);
        $connection = $connector->getPDOConnection();

        $cset = $connection->query('show variables like "character_set_connection"')->fetch(PDO::FETCH_NUM)[1];
        $collation = $connection->query('show variables like "collation_connection"')->fetch(PDO::FETCH_NUM)[1];

        $this->assertEquals($charset, $cset);
        $this->assertEquals($customCollation, $collation);

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
