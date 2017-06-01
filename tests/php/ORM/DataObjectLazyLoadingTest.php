<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Tests\DataObjectTest\SubTeam;
use SilverStripe\ORM\Tests\DataObjectTest\Team;
use SilverStripe\Dev\SapphireTest;

class DataObjectLazyLoadingTest extends SapphireTest
{
    protected static $fixture_file = array(
        'DataObjectTest.yml',
    );

    protected static function getExtraDataObjects()
    {
        return array_merge(
            DataObjectTest::$extra_data_objects,
            ManyManyListTest::$extra_data_objects
        );
    }

    public function testQueriedColumnsID()
    {
        $playerList = new DataList(SubTeam::class);
        $playerList = $playerList->setQueriedColumns(array('ID'));
        $expected = sprintf(
            'SELECT DISTINCT %s, %s, %s, %s, CASE WHEN %s IS NOT NULL THEN %s ELSE %s END AS %s, %s FROM %s LEFT JOIN %s ON %s = %s WHERE (%s IN (?)) ORDER BY %s ASC',
            Convert::symbol2sql('DataObjectTest_Team.ClassName'),
            Convert::symbol2sql('DataObjectTest_Team.LastEdited'),
            Convert::symbol2sql('DataObjectTest_Team.Created'),
            Convert::symbol2sql('DataObjectTest_Team.ID'),
            Convert::symbol2sql('DataObjectTest_Team.ClassName'),
            Convert::symbol2sql('DataObjectTest_Team.ClassName'),
            Convert::raw2sql(Team::class, true),
            Convert::symbol2sql('RecordClassName'),
            Convert::symbol2sql('DataObjectTest_Team.Title'),
            Convert::symbol2sql('DataObjectTest_Team'),
            Convert::symbol2sql('DataObjectTest_SubTeam'),
            Convert::symbol2sql('DataObjectTest_SubTeam.ID'),
            Convert::symbol2sql('DataObjectTest_Team.ID'),
            Convert::symbol2sql('DataObjectTest_Team.ClassName'),
            Convert::symbol2sql('DataObjectTest_Team.Title')
        );
        $this->assertSQLEquals($expected, $playerList->sql($parameters));
    }

    public function testQueriedColumnsFromBaseTableAndSubTable()
    {
        $playerList = new DataList(SubTeam::class);
        $playerList = $playerList->setQueriedColumns(array('Title', 'SubclassDatabaseField'));
        $expected = sprintf(
            'SELECT DISTINCT %s, %s, %s, %s, %s, %s, CASE WHEN %s IS NOT NULL THEN %s ELSE %s END AS %s FROM %s LEFT JOIN %s ON %s = %s WHERE (%s IN (?)) ORDER BY %s ASC',
            Convert::symbol2sql('DataObjectTest_Team.ClassName'),
            Convert::symbol2sql('DataObjectTest_Team.LastEdited'),
            Convert::symbol2sql('DataObjectTest_Team.Created'),
            Convert::symbol2sql('DataObjectTest_Team.Title'),
            Convert::symbol2sql('DataObjectTest_SubTeam.SubclassDatabaseField'),
            Convert::symbol2sql('DataObjectTest_Team.ID'),
            Convert::symbol2sql('DataObjectTest_Team.ClassName'),
            Convert::symbol2sql('DataObjectTest_Team.ClassName'),
            Convert::raw2sql(Team::class, true),
            Convert::symbol2sql('RecordClassName'),
            Convert::symbol2sql('DataObjectTest_Team'),
            Convert::symbol2sql('DataObjectTest_SubTeam'),
            Convert::symbol2sql('DataObjectTest_SubTeam.ID'),
            Convert::symbol2sql('DataObjectTest_Team.ID'),
            Convert::symbol2sql('DataObjectTest_Team.ClassName'),
            Convert::symbol2sql('DataObjectTest_Team.Title')
        );
        $this->assertSQLEquals($expected, $playerList->sql($parameters));
    }

    public function testQueriedColumnsFromBaseTable()
    {
        $playerList = new DataList(SubTeam::class);
        $playerList = $playerList->setQueriedColumns(array('Title'));
        $expected = sprintf(
            'SELECT DISTINCT %s, %s, %s, %s, %s, CASE WHEN %s IS NOT NULL THEN %s ELSE %s END AS %s FROM %s LEFT JOIN %s ON %s = %s WHERE (%s IN (?)) ORDER BY %s ASC',
            Convert::symbol2sql('DataObjectTest_Team.ClassName'),
            Convert::symbol2sql('DataObjectTest_Team.LastEdited'),
            Convert::symbol2sql('DataObjectTest_Team.Created'),
            Convert::symbol2sql('DataObjectTest_Team.Title'),
            Convert::symbol2sql('DataObjectTest_Team.ID'),
            Convert::symbol2sql('DataObjectTest_Team.ClassName'),
            Convert::symbol2sql('DataObjectTest_Team.ClassName'),
            Convert::raw2sql(Team::class, true),
            Convert::symbol2sql('RecordClassName'),
            Convert::symbol2sql('DataObjectTest_Team'),
            Convert::symbol2sql('DataObjectTest_SubTeam'),
            Convert::symbol2sql('DataObjectTest_SubTeam.ID'),
            Convert::symbol2sql('DataObjectTest_Team.ID'),
            Convert::symbol2sql('DataObjectTest_Team.ClassName'),
            Convert::symbol2sql('DataObjectTest_Team.Title')
        );
        $this->assertSQLEquals($expected, $playerList->sql($parameters));
    }

    public function testQueriedColumnsFromSubTable()
    {
        $playerList = new DataList(SubTeam::class);
        $playerList = $playerList->setQueriedColumns(array('SubclassDatabaseField'));

        $expected = sprintf(
            'SELECT DISTINCT %s, %s, %s, %s, %s, CASE WHEN %s IS NOT NULL THEN %s ELSE %s END AS %s, %s FROM %s LEFT JOIN %s ON %s = %s WHERE (%s IN (?)) ORDER BY %s ASC',
            Convert::symbol2sql('DataObjectTest_Team.ClassName'),
            Convert::symbol2sql('DataObjectTest_Team.LastEdited'),
            Convert::symbol2sql('DataObjectTest_Team.Created'),
            Convert::symbol2sql('DataObjectTest_SubTeam.SubclassDatabaseField'),
            Convert::symbol2sql('DataObjectTest_Team.ID'),
            Convert::symbol2sql('DataObjectTest_Team.ClassName'),
            Convert::symbol2sql('DataObjectTest_Team.ClassName'),
            Convert::raw2sql(Team::class, true),
            Convert::symbol2sql('RecordClassName'),
            Convert::symbol2sql('DataObjectTest_Team.Title'),
            Convert::symbol2sql('DataObjectTest_Team'),
            Convert::symbol2sql('DataObjectTest_SubTeam'),
            Convert::symbol2sql('DataObjectTest_SubTeam.ID'),
            Convert::symbol2sql('DataObjectTest_Team.ID'),
            Convert::symbol2sql('DataObjectTest_Team.ClassName'),
            Convert::symbol2sql('DataObjectTest_Team.Title')
        );
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
                    sprintf(
                        '/SELECT DISTINCT %s.* LEFT JOIN .* FROM %s/',
                        Convert::symbol2sql('DataObjectTest_Team.ID'),
                        Convert::symbol2sql('DataObjectTest_Team')
                    )
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
}
