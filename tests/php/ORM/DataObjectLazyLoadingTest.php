<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Tests\DataObjectLazyLoadingTest\VersionedObject;
use SilverStripe\ORM\Tests\DataObjectLazyLoadingTest\VersionedSubObject;
use SilverStripe\ORM\Tests\DataObjectTest\SubTeam;
use SilverStripe\ORM\Tests\DataObjectTest\Team;
use SilverStripe\ORM\Tests\VersionedTest\Subclass;
use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\Dev\SapphireTest;

class DataObjectLazyLoadingTest extends SapphireTest
{

    protected static $fixture_file = array(
        'DataObjectTest.yml',
        'VersionedTest.yml'
    );

    protected function getExtraDataObjects()
    {
        return array_merge(
            DataObjectTest::$extra_data_objects,
            ManyManyListTest::$extra_data_objects,
            VersionedTest::$extra_data_objects,
            [
                VersionedObject::class,
                VersionedSubObject::class,
            ]
        );
    }

    public function testQueriedColumnsID()
    {
        $db = DB::get_conn();
        $playerList = new DataList(SubTeam::class);
        $playerList = $playerList->setQueriedColumns(array('ID'));
        $expected = 'SELECT DISTINCT "DataObjectTest_Team"."ClassName", "DataObjectTest_Team"."LastEdited", ' .
            '"DataObjectTest_Team"."Created", "DataObjectTest_Team"."ID", CASE WHEN '.
            '"DataObjectTest_Team"."ClassName" IS NOT NULL THEN "DataObjectTest_Team"."ClassName" ELSE ' .
            $db->quoteString(Team::class).' END AS "RecordClassName", "DataObjectTest_Team"."Title" '.
            'FROM "DataObjectTest_Team" ' .
            'LEFT JOIN "DataObjectTest_SubTeam" ON "DataObjectTest_SubTeam"."ID" = "DataObjectTest_Team"."ID" ' .
            'WHERE ("DataObjectTest_Team"."ClassName" IN (?))' .
            ' ORDER BY "DataObjectTest_Team"."Title" ASC';
        $this->assertSQLEquals($expected, $playerList->sql($parameters));
    }

    public function testQueriedColumnsFromBaseTableAndSubTable()
    {
        $db = DB::get_conn();
        $playerList = new DataList(SubTeam::class);
        $playerList = $playerList->setQueriedColumns(array('Title', 'SubclassDatabaseField'));
        $expected = 'SELECT DISTINCT "DataObjectTest_Team"."ClassName", "DataObjectTest_Team"."LastEdited", ' .
            '"DataObjectTest_Team"."Created", "DataObjectTest_Team"."Title", ' .
            '"DataObjectTest_SubTeam"."SubclassDatabaseField", "DataObjectTest_Team"."ID", CASE WHEN ' .
            '"DataObjectTest_Team"."ClassName" IS NOT NULL THEN "DataObjectTest_Team"."ClassName" ELSE ' .
            $db->quoteString(Team::class).' END AS "RecordClassName" FROM "DataObjectTest_Team" ' .
            'LEFT JOIN "DataObjectTest_SubTeam" ON "DataObjectTest_SubTeam"."ID" = "DataObjectTest_Team"."ID" WHERE ' .
            '("DataObjectTest_Team"."ClassName" IN (?)) ' .
            'ORDER BY "DataObjectTest_Team"."Title" ASC';
        $this->assertSQLEquals($expected, $playerList->sql($parameters));
    }

    public function testQueriedColumnsFromBaseTable()
    {
        $db = DB::get_conn();
        $playerList = new DataList(SubTeam::class);
        $playerList = $playerList->setQueriedColumns(array('Title'));
        $expected = 'SELECT DISTINCT "DataObjectTest_Team"."ClassName", "DataObjectTest_Team"."LastEdited", ' .
            '"DataObjectTest_Team"."Created", "DataObjectTest_Team"."Title", "DataObjectTest_Team"."ID", ' .
            'CASE WHEN "DataObjectTest_Team"."ClassName" IS NOT NULL THEN "DataObjectTest_Team"."ClassName" ELSE ' .
            $db->quoteString(Team::class).' END AS "RecordClassName" FROM "DataObjectTest_Team" ' .
            'LEFT JOIN "DataObjectTest_SubTeam" ON "DataObjectTest_SubTeam"."ID" = "DataObjectTest_Team"."ID" WHERE ' .
            '("DataObjectTest_Team"."ClassName" IN (?)) ' .
            'ORDER BY "DataObjectTest_Team"."Title" ASC';
        $this->assertSQLEquals($expected, $playerList->sql($parameters));
    }

    public function testQueriedColumnsFromSubTable()
    {
        $db = DB::get_conn();
        $playerList = new DataList(SubTeam::class);
        $playerList = $playerList->setQueriedColumns(array('SubclassDatabaseField'));
        $expected = 'SELECT DISTINCT "DataObjectTest_Team"."ClassName", "DataObjectTest_Team"."LastEdited", ' .
            '"DataObjectTest_Team"."Created", "DataObjectTest_SubTeam"."SubclassDatabaseField", ' .
            '"DataObjectTest_Team"."ID", CASE WHEN "DataObjectTest_Team"."ClassName" IS NOT NULL THEN ' .
            '"DataObjectTest_Team"."ClassName" ELSE '.$db->quoteString(Team::class).' END ' .
            'AS "RecordClassName", "DataObjectTest_Team"."Title" ' .
            'FROM "DataObjectTest_Team" LEFT JOIN "DataObjectTest_SubTeam" ON "DataObjectTest_SubTeam"."ID" = ' .
            '"DataObjectTest_Team"."ID" WHERE ("DataObjectTest_Team"."ClassName" IN (?)) ' .
            'ORDER BY "DataObjectTest_Team"."Title" ASC';
        $this->assertSQLEquals($expected, $playerList->sql($parameters));
    }

    public function testNoSpecificColumnNamesBaseDataObjectQuery()
    {
        // This queries all columns from base table
        $playerList = new DataList(Team::class);
        // Shouldn't be a left join in here.
        $this->assertEquals(
            0,
            preg_match(
                $this->normaliseSQL(
                    '/SELECT DISTINCT "DataObjectTest_Team"."ID" .* LEFT JOIN .* FROM "DataObjectTest_Team"/'
                ),
                $this->normaliseSQL($playerList->sql($parameters))
            )
        );
    }

    public function testNoSpecificColumnNamesSubclassDataObjectQuery()
    {
        // This queries all columns from base table and subtable
        $playerList = new DataList(SubTeam::class);
        // Should be a left join.
        $this->assertEquals(
            1,
            preg_match(
                $this->normaliseSQL('/SELECT DISTINCT .* LEFT JOIN .* /'),
                $this->normaliseSQL($playerList->sql($parameters))
            )
        );
    }

    public function testLazyLoadedFieldsHasField()
    {
        $subteam1 = $this->objFromFixture(SubTeam::class, 'subteam1');
        $teams = DataObject::get(Team::class); // query parent class
        $subteam1Lazy = $teams->find('ID', $subteam1->ID);

        // TODO Fix hasField() to exclude *_Lazy
        // $this->assertFalse($subteam1Lazy->hasField('SubclassDatabaseField_Lazy'));
        $this->assertTrue($subteam1Lazy->hasField('SubclassDatabaseField'));
    }

    public function testLazyLoadedFieldsGetField()
    {
        $subteam1 = $this->objFromFixture(SubTeam::class, 'subteam1');
        $teams = DataObject::get(Team::class); // query parent class
        $subteam1Lazy = $teams->find('ID', $subteam1->ID);

        $this->assertEquals(
            $subteam1->getField('SubclassDatabaseField'),
            $subteam1Lazy->getField('SubclassDatabaseField')
        );
    }

    public function testDBObjectLazyLoadedFields()
    {
        $subteam1 = $this->objFromFixture(SubTeam::class, 'subteam1');
        $teams = DataObject::get(Team::class); // query parent class
        $subteam1Lazy = $teams->find('ID', $subteam1->ID);

        $subteam1DO = $subteam1->dbObject('SubclassDatabaseField');
        $subteam1LazyDO = $subteam1Lazy->dbObject('SubclassDatabaseField');

        $this->assertEquals(
            $subteam1DO->getValue(),
            $subteam1LazyDO->getValue()
        );
    }

    public function testLazyLoadedFieldsSetField()
    {
        $subteam1 = $this->objFromFixture(SubTeam::class, 'subteam1');
        $subteam1ID = $subteam1->ID;
        $teams = DataObject::get(Team::class); // query parent class
        $subteam1Lazy = $teams->find('ID', $subteam1->ID);

        // Updated lazyloaded field
        $subteam1Lazy->SubclassDatabaseField = 'Changed';
        $subteam1Lazy->write();

        // Reload from database
        DataObject::flush_and_destroy_cache();
        $subteam1Reloaded = DataObject::get_by_id(SubTeam::class, $subteam1ID);

        $this->assertEquals(
            'Changed',
            $subteam1Reloaded->getField('SubclassDatabaseField')
        );
    }

    public function testLazyLoadedFieldsWriteWithUnloadedFields()
    {
        $subteam1 = $this->objFromFixture(SubTeam::class, 'subteam1');
        $subteam1ID = $subteam1->ID;
        $teams = DataObject::get(Team::class); // query parent class
        $subteam1Lazy = $teams->find('ID', $subteam1->ID);

        // Updated lazyloaded field
        $subteam1Lazy->Title = 'Changed';
        $subteam1Lazy->write();

        // Reload from database
        DataObject::flush_and_destroy_cache();
        $subteam1Reloaded = DataObject::get_by_id(SubTeam::class, $subteam1ID);

        $this->assertEquals(
            'Subclassed 1',
            $subteam1Reloaded->getField('SubclassDatabaseField')
        );
    }

    public function testLazyLoadedFieldsWriteNullFields()
    {
        $subteam1 = $this->objFromFixture(SubTeam::class, 'subteam1');
        $subteam1ID = $subteam1->ID;
        $teams = DataObject::get(Team::class); // query parent class
        $subteam1Lazy = $teams->find('ID', $subteam1->ID);

        // Updated lazyloaded field
        $subteam1Lazy->SubclassDatabaseField = null;
        $subteam1Lazy->write();

        // Reload from database
        DataObject::flush_and_destroy_cache();
        $subteam1Reloaded = DataObject::get_by_id(SubTeam::class, $subteam1ID);

        $this->assertEquals(
            null,
            $subteam1Reloaded->getField('SubclassDatabaseField')
        );
    }

    public function testLazyLoadedFieldsGetChangedFields()
    {
        $subteam1 = $this->objFromFixture(SubTeam::class, 'subteam1');
        $teams = DataObject::get(Team::class); // query parent class
        $subteam1Lazy = $teams->find('ID', $subteam1->ID);

        // Updated lazyloaded field
        $subteam1Lazy->SubclassDatabaseField = 'Changed';
        $this->assertEquals(
            array('SubclassDatabaseField' => array(
                'before' => 'Subclassed 1',
                'after' => 'Changed',
                'level' => 2
            )),
            $subteam1Lazy->getChangedFields()
        );
    }

    public function testLazyLoadedFieldsHasOneRelation()
    {
        $subteam1 = $this->objFromFixture(SubTeam::class, 'subteam1');
        $parentTeam = $this->objFromFixture(Team::class, 'team1');
        $teams = DataObject::get(Team::class); // query parent class
        $subteam1Lazy = $teams->find('ID', $subteam1->ID);

        $parentTeamLazy = $subteam1Lazy->ParentTeam();
        $this->assertInstanceOf(Team::class, $parentTeamLazy);
        $this->assertEquals($parentTeam->ID, $parentTeamLazy->ID);
    }

    public function testLazyLoadedFieldsToMap()
    {
        $subteam1 = $this->objFromFixture(SubTeam::class, 'subteam1');
        $parentTeam = $this->objFromFixture(Team::class, 'team1');
        $teams = DataObject::get(Team::class); // query parent class
        $subteam1Lazy = $teams->find('ID', $subteam1->ID);
        $mapLazy = $subteam1Lazy->toMap();
        $this->assertArrayHasKey('SubclassDatabaseField', $mapLazy);
        $this->assertEquals('Subclassed 1', $mapLazy['SubclassDatabaseField']);
    }

    public function testLazyLoadedFieldsIsEmpty()
    {
        $subteam1 = $this->objFromFixture(SubTeam::class, 'subteam1');
        $parentTeam = $this->objFromFixture(Team::class, 'team1');
        $teams = DataObject::get(Team::class); // query parent class
        $subteam1Lazy = $teams->find('ID', $subteam1->ID);
        $subteam1Lazy->Title = '';
        $subteam1Lazy->DecoratedDatabaseField = '';
        $subteam1Lazy->ParentTeamID = 0;
        // Leave $subteam1Lazy->SubclassDatabaseField intact
        $this->assertFalse($subteam1Lazy->isEmpty());
    }

    public function testLazyLoadedFieldsDuplicate()
    {
        $subteam1 = $this->objFromFixture(SubTeam::class, 'subteam1');
        $parentTeam = $this->objFromFixture(Team::class, 'team1');
        $teams = DataObject::get(Team::class); // query parent class
        $subteam1Lazy = $teams->find('ID', $subteam1->ID);
        $subteam1LazyDup = $subteam1Lazy->duplicate();

        $this->assertEquals('Subclassed 1', $subteam1LazyDup->SubclassDatabaseField);
    }

    public function testLazyLoadedFieldsGetAllFields()
    {
        $subteam1 = $this->objFromFixture(SubTeam::class, 'subteam1');
        $parentTeam = $this->objFromFixture(Team::class, 'team1');
        $teams = DataObject::get(Team::class); // query parent class
        $subteam1Lazy = $teams->find('ID', $subteam1->ID);
        $this->assertArrayNotHasKey('SubclassDatabaseField_Lazy', $subteam1Lazy->toMap());
        $this->assertArrayHasKey('SubclassDatabaseField', $subteam1Lazy->toMap());
    }

    public function testLazyLoadedFieldsOnVersionedRecords()
    {
        // Save another record, sanity check that we're getting the right one
        $obj2 = new Subclass();
        $obj2->Name = "test2";
        $obj2->ExtraField = "foo2";
        $obj2->write();

        // Save the actual inspected record
        $obj1 = new Subclass();
        $obj1->Name = "test";
        $obj1->ExtraField = "foo";
        $obj1->write();
        $version1 = $obj1->Version;
        $obj1->Name = "test2";
        $obj1->ExtraField = "baz";
        $obj1->write();
        $version2 = $obj1->Version;


        $reloaded = Versioned::get_version(VersionedTest\Subclass::class, $obj1->ID, $version1);
        $this->assertEquals($reloaded->Name, 'test');
        $this->assertEquals($reloaded->ExtraField, 'foo');

        $reloaded = Versioned::get_version(VersionedTest\Subclass::class, $obj1->ID, $version2);
        $this->assertEquals($reloaded->Name, 'test2');
        $this->assertEquals($reloaded->ExtraField, 'baz');

        $reloaded = Versioned::get_latest_version(VersionedTest\Subclass::class, $obj1->ID);
        $this->assertEquals($reloaded->Version, $version2);
        $this->assertEquals($reloaded->Name, 'test2');
        $this->assertEquals($reloaded->ExtraField, 'baz');

        $allVersions = Versioned::get_all_versions(VersionedTest\Subclass::class, $obj1->ID);
        $this->assertEquals(2, $allVersions->count());
        $this->assertEquals($allVersions->first()->Version, $version1);
        $this->assertEquals($allVersions->first()->Name, 'test');
        $this->assertEquals($allVersions->first()->ExtraField, 'foo');
        $this->assertEquals($allVersions->last()->Version, $version2);
        $this->assertEquals($allVersions->last()->Name, 'test2');
        $this->assertEquals($allVersions->last()->ExtraField, 'baz');

        $obj1->delete();
    }

    public function testLazyLoadedFieldsDoNotReferenceVersionsTable()
    {
        // Save another record, sanity check that we're getting the right one
        $obj2 = new Subclass();
        $obj2->Name = "test2";
        $obj2->ExtraField = "foo2";
        $obj2->write();

        $obj1 = new VersionedSubObject();
        $obj1->PageName = "old-value";
        $obj1->ExtraField = "old-value";
        $obj1ID = $obj1->write();
        $obj1->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);

        $obj1 = VersionedSubObject::get()->byID($obj1ID);
        $this->assertEquals(
            'old-value',
            $obj1->PageName,
            "Correct value on base table when fetching base class"
        );
        $this->assertEquals(
            'old-value',
            $obj1->ExtraField,
            "Correct value on sub table when fetching base class"
        );

        $obj1 = VersionedObject::get()->byID($obj1ID);
        $this->assertEquals(
            'old-value',
            $obj1->PageName,
            "Correct value on base table when fetching sub class"
        );
        $this->assertEquals(
            'old-value',
            $obj1->ExtraField,
            "Correct value on sub table when fetching sub class"
        );

        // Force inconsistent state to test behaviour (shouldn't select from *_versions)
        DB::query(
            sprintf(
                "UPDATE \"VersionedLazy_DataObject_Versions\" SET \"PageName\" = 'versioned-value' " .
                "WHERE \"RecordID\" = %d",
                $obj1ID
            )
        );
        DB::query(
            sprintf(
                "UPDATE \"VersionedLazySub_DataObject_Versions\" SET \"ExtraField\" = 'versioned-value' " .
                "WHERE \"RecordID\" = %d",
                $obj1ID
            )
        );

        $obj1 = VersionedSubObject::get()->byID($obj1ID);
        $this->assertEquals(
            'old-value',
            $obj1->PageName,
            "Correct value on base table when fetching base class"
        );
        $this->assertEquals(
            'old-value',
            $obj1->ExtraField,
            "Correct value on sub table when fetching base class"
        );
        $obj1 = VersionedObject::get()->byID($obj1ID);
        $this->assertEquals(
            'old-value',
            $obj1->PageName,
            "Correct value on base table when fetching sub class"
        );
        $this->assertEquals(
            'old-value',
            $obj1->ExtraField,
            "Correct value on sub table when fetching sub class"
        );

        // Update live table only to test behaviour (shouldn't select from *_versions or stage)
        DB::query(
            sprintf(
                'UPDATE "VersionedLazy_DataObject_Live" SET "PageName" = \'live-value\' WHERE "ID" = %d',
                $obj1ID
            )
        );
        DB::query(
            sprintf(
                'UPDATE "VersionedLazySub_DataObject_Live" SET "ExtraField" = \'live-value\' WHERE "ID" = %d',
                $obj1ID
            )
        );

        Versioned::set_stage(Versioned::LIVE);
        $obj1 = VersionedObject::get()->byID($obj1ID);
        $this->assertEquals(
            'live-value',
            $obj1->PageName,
            "Correct value from base table when fetching base class on live stage"
        );
        $this->assertEquals(
            'live-value',
            $obj1->ExtraField,
            "Correct value from sub table when fetching base class on live stage"
        );
    }
}
