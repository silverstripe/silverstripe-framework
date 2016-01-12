<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\Connect\PDOQuery;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Connect\PDOConnector;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Dev\SapphireTest;
use PDO;

class PDODatabaseTest extends SapphireTest
{

    protected static $fixture_file = 'MySQLDatabaseTest.yml';

    protected $extraDataObjects = array(
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
        $this->assertEquals(
            array(
                'Sort' => 1,
                'Title' => 'First Item'
            ),
            $result1->next()
        );
        $this->assertEquals(
            array(
                'Sort' => 2,
                'Title' => 'Second Item'
            ),
            $result1->next()
        );

        // Test first
        $this->assertEquals(
            array(
                'Sort' => 1,
                'Title' => 'First Item'
            ),
            $result1->first()
        );

        // Test seek
        $this->assertEquals(
            array(
                'Sort' => 2,
                'Title' => 'Second Item'
            ),
            $result1->seek(1)
        );

        // Test count
        $this->assertEquals(4, $result1->numRecords());

        // Test second statement
        $this->assertEquals(
            array(
                'Sort' => 3,
                'Title' => 'Third Item'
            ),
            $result2->next()
        );

        // Test non-prepared query
        $this->assertEquals(
            array(
                'Sort' => 1,
                'Title' => 'First Item'
            ),
            $result3->next()
        );
    }

    public function testAffectedRows()
    {
        if (!(DB::get_connector() instanceof PDOConnector)) {
            $this->markTestSkipped('This test requires the current DB connector is PDO');
        }

        $query = new SQLUpdate('MySQLDatabaseTest_Data');
        $query->setAssignments(array('Title' => 'New Title'));

        // Test update which affects no rows
        $query->setWhere(array('Title' => 'Bob'));
        $result = $query->execute();
        $this->assertInstanceOf(PDOQuery::class, $result);
        $this->assertEquals(0, DB::affected_rows());

        // Test update which affects some rows
        $query->setWhere(array('Title' => 'First Item'));
        $result = $query->execute();
        $this->assertInstanceOf(PDOQuery::class, $result);
        $this->assertEquals(1, DB::affected_rows());
    }

    public function testTypeRetained()
    {
        if (!(DB::get_connector() instanceof PDOConnector)) {
            $this->markTestSkipped('This test requires the current DB connector is PDO');
        }

        $reflectionProperty = new \ReflectionProperty(PDOConnector::class, 'pdoConnection');
        $reflectionProperty->setAccessible(true);
        $connection = $reflectionProperty->getValue(DB::get_connector());

        //store the current value so we can reset it back at the end
        $origStringify = $connection->getAttribute(PDO::ATTR_STRINGIFY_FETCHES);
        $connection->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);


        $result = DB::get_connector()->query(
            'SELECT "ID", "Title" FROM "MySQLDatabaseTest_Data" LIMIT 1'
        );

        $row = $result->next();

        $this->assertInternalType("int", $row['ID']);
        $this->assertInternalType("string", $row['Title']);

        $connection->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);

        $result = DB::get_connector()->query(
            'SELECT "ID", "Title" FROM "MySQLDatabaseTest_Data" LIMIT 1'
        );

        $row = $result->next();

        $this->assertInternalType("string", $row['ID']);
        $this->assertInternalType("string", $row['Title']);

        $connection->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, $origStringify);
    }
}
