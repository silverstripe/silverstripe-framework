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

    protected static $extra_dataobjects = [
        SQLUpdateTest\TestBase::class,
        SQLUpdateTest\TestChild::class,
        SQLUpdateTest\TestOther::class,
    ];

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
                ->addWhere(['"Title" = ?' => 'Object 1']);
        $sql = $query->sql($parameters);

        // Check SQL
        $this->assertSQLEquals('UPDATE "SQLUpdateTestBase" SET "Description" = ? WHERE ("Title" = ?)', $sql);
        $this->assertEquals(['Description 1a', 'Object 1'], $parameters);

        // Check affected rows
        $query->execute();
        $this->assertEquals(1, DB::affected_rows());

        // Check item updated
        $item = DataObject::get_one(SQLUpdateTest\TestBase::class, ['"Title"' => 'Object 1']);
        $this->assertEquals('Description 1a', $item->Description);
    }

    public function testUpdateWithJoin()
    {
        $query = SQLUpdate::create()
                ->setTable('"SQLUpdateTestBase"')
                ->assign('"SQLUpdateTestBase"."Description"', 'Description 2a')
                ->addInnerJoin('SQLUpdateTestOther', '"SQLUpdateTestOther"."Description" = "SQLUpdateTestBase"."Description"');
        $sql = $query->sql($parameters);

        // Check SQL
        $this->assertSQLEquals('UPDATE "SQLUpdateTestBase" INNER JOIN "SQLUpdateTestOther" ON "SQLUpdateTestOther"."Description" = "SQLUpdateTestBase"."Description" SET "SQLUpdateTestBase"."Description" = ?', $sql);
        $this->assertEquals(['Description 2a'], $parameters);

        // Check affected rows
        $query->execute();
        $this->assertEquals(1, DB::affected_rows());

        // Check item updated
        $item = DataObject::get_one(SQLUpdateTest\TestBase::class, ['"Title"' => 'Object 2']);
        $this->assertEquals('Description 2a', $item->Description);
    }
}
