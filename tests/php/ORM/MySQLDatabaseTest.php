<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\Connect\MySQLQuery;
use SilverStripe\ORM\Connect\MySQLStatement;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Connect\MySQLiConnector;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\ORM\Tests\MySQLSchemaManagerTest\MySQLDBDummy;

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
        $result1Array = [];
        foreach ($result1 as $record) {
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

        // Test count
        $this->assertEquals(4, $result1->numRecords());

        // Test second statement
        $result2Array = [];
        foreach ($result2 as $record) {
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
        foreach ($result3 as $record) {
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

    public function provideSupportsCte()
    {
        return [
            // mysql unsupported
            [
                'version' => '1.1.1',
                'expected' => false,
                'expectedRecursive' => false,
            ],
            [
                'version' => '5.9999.9999',
                'expected' => false,
                'expectedRecursive' => false,
            ],
            [
                'version' => '8.0.0',
                'expected' => false,
                'expectedRecursive' => false,
            ],
            // mysql supported
            [
                'version' => '8.0.1',
                'expected' => true,
                'expectedRecursive' => true,
            ],
            [
                'version' => '10.2.0',
                'expected' => true,
                'expectedRecursive' => true,
            ],
            [
                'version' => '999.999.999',
                'expected' => true,
                'expectedRecursive' => true,
            ],
            // mariaDB unsupported (various formats)
            [
                'version' => '5.5.5-10.2.0-mariadb-1:10.6.8+maria~focal',
                'expected' => false,
                'expectedRecursive' => false,
            ],
            [
                'version' => '10.2.0-mariadb-1:10.6.8+maria~jammy',
                'expected' => false,
                'expectedRecursive' => false,
            ],
            [
                'version' => '10.2.0-mariadb-1:10.2.0+maria~focal',
                'expected' => false,
                'expectedRecursive' => false,
            ],
            // mariadb supported (various formats)
            [
                'version' => '5.5.5-10.2.1-mariadb-1:10.6.8+maria~focal',
                'expected' => true,
                'expectedRecursive' => false,
            ],
            [
                'version' => '10.2.1-mariadb-1:10.6.8+maria~jammy',
                'expected' => true,
                'expectedRecursive' => false,
            ],
            [
                'version' => '10.2.1-mariadb-1:10.2.1+maria~focal',
                'expected' => true,
                'expectedRecursive' => false,
            ],
            [
                'version' => '10.2.2-mariadb-1:10.2.2+maria~jammy',
                'expected' => true,
                'expectedRecursive' => true,
            ],
            [
                'version' => '5.5.5-10.2.2-mariadb-1:10.2.2+maria~jammy',
                'expected' => true,
                'expectedRecursive' => true,
            ],
            [
                'version' => '5.5.5-999.999.999-mariadb-1:10.2.2+maria~jammy',
                'expected' => true,
                'expectedRecursive' => true,
            ],
            // completely invalid versions
            [
                'version' => '999.999.999-some-random-string',
                'expected' => false,
                'expectedRecursive' => false,
            ],
        ];
    }

    /**
     * @dataProvider provideSupportsCte
     */
    public function testSupportsCte(string $version, bool $expected, bool $expectedRecursive)
    {
        $database = new MySQLDBDummy($version);
        $this->assertSame($expected, $database->supportsCteQueries());
        $this->assertSame($expectedRecursive, $database->supportsCteQueries(true));
    }
}
