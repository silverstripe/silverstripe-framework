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
        $config['database'] = 'information_schema';

        if (strtolower(substr($config['type'] ?? '', 0, 5)) !== 'mysql') {
            return $this->markTestSkipped('The test only relevant for MySQL');
        }

        $connector = new MySQLiConnector();
        $connector->connect($config);
        $connection = $connector->getMysqliConnection();

        $cset = $connection->get_charset();

        $this->assertEquals($charset, $cset->charset);
        $this->assertEquals($defaultCollation, $cset->collation);

        unset($cset, $connection, $connector, $config);
    }

    /**
     * @dataProvider charsetProvider
     */
    public function testConnectionCollationControl($charset, $defaultCollation, $customCollation)
    {
        $config = DB::getConfig();
        $config['charset'] = $charset;
        $config['collation'] = $customCollation;
        $config['database'] = 'information_schema';

        if (strtolower(substr($config['type'] ?? '', 0, 5)) !== 'mysql') {
            return $this->markTestSkipped('The test only relevant for MySQL');
        }

        $connector = new MySQLiConnector();
        $connector->connect($config);
        $connection = $connector->getMysqliConnection();

        $cset = $connection->get_charset();

        $this->assertEquals($charset, $cset->charset);

        /* Warning! This is a MySQLi limitation.
         * If it changes in the future versions, this test may break.
         * We are still testing for it as a limitation and a
         * reminder that it exists.
         *
         * To make sure that we actually have correct collation see
         *  - testUtf8mb4GeneralCollation
         *  - testUtf8mb4UnicodeCollation
         */
        $this->assertEquals(
            $defaultCollation,
            $cset->collation,
            'This is an issue with mysqli. It always returns "default" collation, even if another is active'
        );

        $cset = $connection->query('show variables like "character_set_connection"')->fetch_array()[1];
        $collation = $connection->query('show variables like "collation_connection"')->fetch_array()[1];

        $this->assertEquals($charset, $cset);
        $this->assertEquals($customCollation, $collation);

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

    public function testUtf8mb4GeneralCollation()
    {
        $charset = 'utf8mb4';
        $collation = 'utf8mb4_general_ci';

        $config = DB::getConfig();
        $config['charset'] = $charset;
        $config['collation'] = $collation;
        $config['database'] = 'information_schema';

        if (strtolower(substr($config['type'] ?? '', 0, 5)) !== 'mysql') {
            return $this->markTestSkipped('The test only relevant for MySQL');
        }

        $connector = new MySQLiConnector();
        $connector->connect($config, true);
        $connection = $connector->getMysqliConnection();

        $result = $connection->query(
            "select `a`.`value` from (select 'rst' `value` union select 'rßt' `value`) `a` order by `value`"
        )->fetch_all();

        $this->assertCount(1, $result, '`utf8mb4_general_ci` handles both values as equal to "rst"');
        $this->assertEquals('rst', $result[0][0]);
    }

    public function testUtf8mb4UnicodeCollation()
    {
        $charset = 'utf8mb4';
        $collation = 'utf8mb4_unicode_ci';

        $config = DB::getConfig();
        $config['charset'] = $charset;
        $config['collation'] = $collation;
        $config['database'] = 'information_schema';

        if (strtolower(substr($config['type'] ?? '', 0, 5)) !== 'mysql') {
            return $this->markTestSkipped('The test only relevant for MySQL');
        }

        $connector = new MySQLiConnector();
        $connector->connect($config, true);
        $connection = $connector->getMysqliConnection();

        $result = $connection->query(
            "select `a`.`value` from (select 'rst' `value` union select 'rßt' `value`) `a` order by `value`"
        )->fetch_all();

        $this->assertCount(2, $result, '`utf8mb4_unicode_ci` must recognise "rst" and "rßt" as different values');
        $this->assertEquals('rßt', $result[0][0]);
        $this->assertEquals('rst', $result[1][0]);
    }
}
