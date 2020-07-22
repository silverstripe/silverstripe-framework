<?php

namespace SilverStripe\ORM\Tests\MySQLiConnectorTest;

use SilverStripe\Config\Collections\MutableConfigCollectionInterface;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\ORM\Connect\MySQLiConnector;
use SilverStripe\ORM\DB;

class MySQLiConnectorTest extends SapphireTest
{
    /**
     * @var bool
     */
    protected $usesDatabase = true;

    /**
     * @var array
     */
    protected static $extra_dataobjects = [
        DummyObject::class,
    ];

    /**
     * This test validates that the encoding and collation conrol works for MySQLiConnector
     */
    public function testConnectionCollationControl()
    {
        if (!(DB::get_connector() instanceof MySQLiConnector)) {
            $this->markTestSkipped('This test requires the current DB connector is MySQLi');
        }

        Config::withConfig(function (MutableConfigCollectionInterface $config) {
            $config
                ->set(MySQLDatabase::class, 'connection_charset', 'utf8mb4')
                ->set(MySQLDatabase::class, 'connection_collation', 'utf8mb4_unicode_ci')
                ->set(MySQLDatabase::class, 'charset', 'utf8mb4')
                ->set(MySQLDatabase::class, 'collation', 'utf8mb4_unicode_ci');

            // this query creates a temporary DB column
            // if the collation is not set correctly for this DB connection this will throw
            $sql = sprintf(
                'SELECT \'arbitrary_value\' AS "arbitrary_alias" FROM "%s" HAVING "arbitrary_alias" = ?',
                DummyObject::config()->get('table_name')
            );

            $params = [
                'arbitrary_condition',
            ];

            DB::prepared_query($sql, $params);
        });
    }
}
