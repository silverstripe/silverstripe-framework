<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\Connect\MySQLQuery;
use SilverStripe\ORM\Connect\MySQLStatement;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Connect\MySQLiConnector;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Dev\SapphireTest;

/**
 * @skipUpgrade
 */
class MySQLDatabaseTest extends SapphireTest
{

    protected static $fixture_file = 'MySQLDatabaseTest.yml';

    protected static $extra_dataobjects = [
        MySQLDatabaseTest\Data::class
    ];

    public function testPreparedStatements()
    {
        if (!(DB::get_connector() instanceof MySQLiConnector)) {
            $this->markTestSkipped('This test requires the current DB connector is MySQLi');
        }

        // Test preparation of equivalent statemetns
        $result1 = DB::get_connector()->preparedQuery(
            'SELECT "Sort", "Title" FROM "MySQLDatabaseTest_Data" WHERE "Sort" > ? ORDER BY "Sort"',
            [0]
        );

        $result2 = DB::get_connector()->preparedQuery(
            'SELECT "Sort", "Title" FROM "MySQLDatabaseTest_Data" WHERE "Sort" > ? ORDER BY "Sort"',
            [2]
        );
        $this->assertInstanceOf(MySQLStatement::class, $result1);
        $this->assertInstanceOf(MySQLStatement::class, $result2);

        // Also select non-prepared statement
        $result3 = DB::get_connector()->query('SELECT "Sort", "Title" FROM "MySQLDatabaseTest_Data" ORDER BY "Sort"');
        $this->assertInstanceOf(MySQLQuery::class, $result3);

        // Iterating one level should not buffer, but return the right result
        $this->assertEquals(
            [
                'Sort' => 1,
                'Title' => 'First Item'
            ],
            $result1->next()
        );
        $this->assertEquals(
            [
                'Sort' => 2,
                'Title' => 'Second Item'
            ],
            $result1->next()
        );

        // Test first
        $this->assertEquals(
            [
                'Sort' => 1,
                'Title' => 'First Item'
            ],
            $result1->first()
        );

        // Test seek
        $this->assertEquals(
            [
                'Sort' => 2,
                'Title' => 'Second Item'
            ],
            $result1->seek(1)
        );

        // Test count
        $this->assertEquals(4, $result1->numRecords());

        // Test second statement
        $this->assertEquals(
            [
                'Sort' => 3,
                'Title' => 'Third Item'
            ],
            $result2->next()
        );

        // Test non-prepared query
        $this->assertEquals(
            [
                'Sort' => 1,
                'Title' => 'First Item'
            ],
            $result3->next()
        );
    }

    public function testAffectedRows()
    {
        if (!(DB::get_connector() instanceof MySQLiConnector)) {
            $this->markTestSkipped('This test requires the current DB connector is MySQLi');
        }

        $query = new SQLUpdate('MySQLDatabaseTest_Data');
        $query->setAssignments(['Title' => 'New Title']);

        // Test update which affects no rows
        $query->setWhere(['Title' => 'Bob']);
        $result = $query->execute();
        $this->assertInstanceOf(MySQLQuery::class, $result);
        $this->assertEquals(0, DB::affected_rows());

        // Test update which affects some rows
        $query->setWhere(['Title' => 'First Item']);
        $result = $query->execute();
        $this->assertInstanceOf(MySQLQuery::class, $result);
        $this->assertEquals(1, DB::affected_rows());
    }
}
