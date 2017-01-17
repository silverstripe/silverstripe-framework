<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\Connect\PDOQuery;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Connect\PDOConnector;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Dev\SapphireTest;

/**
 * @skipUpgrade
 */
class PDODatabaseTest extends SapphireTest
{
    protected static $fixture_file = 'MySQLDatabaseTest.yml';

    protected static $extra_dataobjects = array(
        MySQLDatabaseTest\Data::class
    );

    public function testPreparedStatements()
    {
        if (!(DB::get_connector() instanceof PDOConnector)) {
            $this->markTestSkipped('This test requires the current DB connector is PDO');
        }

        // Test preparation of equivalent statemetns
        $result1 = DB::get_connector()->preparedQuery(
            'SELECT "Sort", "Title" FROM "MySQLDatabaseTest_Data" WHERE "Sort" > ? ORDER BY "Sort"',
            array(0)
        );

        $result2 = DB::get_connector()->preparedQuery(
            'SELECT "Sort", "Title" FROM "MySQLDatabaseTest_Data" WHERE "Sort" > ? ORDER BY "Sort"',
            array(2)
        );
        $this->assertInstanceOf(PDOQuery::class, $result1);
        $this->assertInstanceOf(PDOQuery::class, $result2);

        // Also select non-prepared statement
        $result3 = DB::get_connector()->query('SELECT "Sort", "Title" FROM "MySQLDatabaseTest_Data" ORDER BY "Sort"');
        $this->assertInstanceOf(PDOQuery::class, $result3);

        // Iterating one level should not buffer, but return the right result
        $result1Array = [];
        foreach($result1 as $record) {
            $result1Array[] = $record;
        }
        $this->assertEquals(
            [
                [ 'Sort' => 1, 'Title' => 'First Item' ],
                [ 'Sort' => 2, 'Title' => 'Second Item' ],
                [ 'Sort' => 3, 'Title' => 'Third Item' ],
                [ 'Sort' => 4, 'Title' => 'Last Item' ],
            ],
            $result1Array
        );

        // Test count
        $this->assertEquals(4, $result1->numRecords());

        // Test second statement
        $result2Array = [];
        foreach($result2 as $record) {
            $result2Array[] = $record;
            break;
        }
        $this->assertEquals(
            [
                [ 'Sort' => 3, 'Title' => 'Third Item' ],
            ],
            $result2Array
        );

        // Test non-prepared query
        $result3Array = [];
        foreach($result3 as $record) {
            $result3Array[] = $record;
            break;
        }
        $this->assertEquals(
            [
                [ 'Sort' => 1, 'Title' => 'First Item' ],
            ],
            $result3Array
        );
    }

    public function testAffectedRows()
    {
        if (!(DB::get_connector() instanceof PDOConnector)) {
            $this->markTestSkipped('This test requires the current DB connector is PDO');
        }

        $query = new SQLUpdate('"MySQLDatabaseTest_Data"');
        $query->setAssignments(array('"Title"' => 'New Title'));

        // Test update which affects no rows
        $query->setWhere(array('"Title"' => 'Bob'));
        $result = $query->execute();
        $this->assertInstanceOf(PDOQuery::class, $result);
        $this->assertEquals(0, DB::affected_rows());

        // Test update which affects some rows
        $query->setWhere(array('"Title"' => 'First Item'));
        $result = $query->execute();
        $this->assertInstanceOf(PDOQuery::class, $result);
        $this->assertEquals(1, DB::affected_rows());
    }
}
