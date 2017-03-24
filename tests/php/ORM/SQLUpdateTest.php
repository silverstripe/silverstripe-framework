<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for {@see SQLUpdate}
 */
class SQLUpdateTest extends SapphireTest
{

    public static $fixture_file = 'SQLUpdateTest.yml';

    protected static $extra_dataobjects = array(
        SQLUpdateTest\TestBase::class,
        SQLUpdateTest\TestChild::class
    );

    public function testEmptyQueryReturnsNothing()
    {
        $query = new SQLUpdate();
        $this->assertSQLEquals('', $query->sql($parameters));
    }

    public function testBasicUpdate()
    {
        $query = SQLUpdate::create()
                ->setTable('"SQLUpdateTestBase"')
                ->assign('"Description"', 'Description 1a')
                ->addWhere(array('"Title" = ?' => 'Object 1'));
        $sql = $query->sql($parameters);

        // Check SQL
        $this->assertSQLEquals('UPDATE "SQLUpdateTestBase" SET "Description" = ? WHERE ("Title" = ?)', $sql);
        $this->assertEquals(array('Description 1a', 'Object 1'), $parameters);

        // Check affected rows
        $query->execute();
        $this->assertEquals(1, DB::affected_rows());

        // Check item updated
        $item = DataObject::get_one(SQLUpdateTest\TestBase::class, array('"Title"' => 'Object 1'));
        $this->assertEquals('Description 1a', $item->Description);
    }
}
