<?php

namespace SilverStripe\ORM\Tests;

use mysqli_driver;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\Connect\DatabaseException;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Tests\MySQLiConnectorTest\MySQLiConnector;
use SilverStripe\Tests\ORM\Utf8\Utf8TestHelper;

/**
 * @requires extension mysqli
 */
class MySQLiConnectorTest extends SapphireTest implements TestOnly
{
    /** @var array project database settings configuration */
    private $config = [];

    private function getConnector(?string $charset = null, ?string $collation = null, bool $selectDb = false)
    {
        $config = $this->config;

        if ($charset) {
            $config['charset'] = $charset;
        }
        if ($collation) {
            $config['collation'] = $collation;
        }

        $config['database'] = 'information_schema';

        $connector = new MySQLiConnector();
        $connector->connect($config, $selectDb);

        return $connector;
    }

    public function setUp(): void
    {
        parent::setUp();

        $config = DB::getConfig();

        if (strtolower(substr($config['type'] ?? '', 0, 5)) !== 'mysql') {
            $this->markTestSkipped("The test only relevant for MySQL - but $config[type] is in use");
        }

        $this->config = $config;
    }

    /**
     * @dataProvider charsetProvider
     */
    public function testConnectionCharsetControl($charset, $defaultCollation)
    {
        $connector = $this->getConnector($charset);
        $connection = $connector->getMysqliConnection();

        $cset = $connection->get_charset();

        // Note: we do not need to update the utf charset here because mysqli with newer
        // version of mysql/mariadb still self-reports as 'utf8' rather than 'utf8mb3'
        // This is unlike self::testConnectionCollationControl()
        $this->assertEquals($charset, $cset->charset);
        $this->assertEquals($defaultCollation, $cset->collation);

        unset($cset, $connection, $connector, $config);
    }

    /**
     * @dataProvider charsetProvider
     */
    public function testConnectionCollationControl($charset, $defaultCollation, $customCollation)
    {
        $connector = $this->getConnector($charset, $customCollation);
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

        $helper = new Utf8TestHelper();
        $this->assertEquals($helper->getUpdatedUtfCharsetForCurrentDB($charset), $cset);
        $this->assertEquals($helper->getUpdatedUtfCollationForCurrentDB($customCollation), $collation);

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
        $connector = $this->getConnector('utf8mb4', 'utf8mb4_general_ci', true);
        $connection = $connector->getMysqliConnection();

        $result = $connection->query(
            "select `a`.`value` from (select 'rst' `value` union select 'rßt' `value`) `a` order by `value`"
        )->fetch_all();

        $this->assertCount(1, $result, '`utf8mb4_general_ci` handles both values as equal to "rst"');
        $this->assertEquals('rst', $result[0][0]);
    }

    public function testUtf8mb4UnicodeCollation()
    {
        $connector = $this->getConnector('utf8mb4', 'utf8mb4_unicode_ci', true);
        $connection = $connector->getMysqliConnection();

        $result = $connection->query(
            "select `a`.`value` from (select 'rst' `value` union select 'rßt' `value`) `a` order by `value`"
        )->fetch_all();

        $this->assertCount(2, $result, '`utf8mb4_unicode_ci` must recognise "rst" and "rßt" as different values');
        $this->assertEquals('rßt', $result[0][0]);
        $this->assertEquals('rst', $result[1][0]);
    }

    public function testQueryThrowsDatabaseErrorOnMySQLiError()
    {
        $connector = $this->getConnector();
        $driver = new mysqli_driver();
        // The default with PHP >= 8.0
        $driver->report_mode = MYSQLI_REPORT_OFF;
        $this->expectException(DatabaseException::class);
        $connector = $this->getConnector(null, null, true);
        $connector->query('force an error with invalid SQL');
    }

    public function testQueryThrowsDatabaseErrorOnMySQLiException()
    {
        $driver = new mysqli_driver();
        // The default since PHP 8.1 - https://www.php.net/manual/en/mysqli-driver.report-mode.php
        $driver->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
        $this->expectException(DatabaseException::class);
        $connector = $this->getConnector(null, null, true);
        $connector->query('force an error with invalid SQL');
    }
}
