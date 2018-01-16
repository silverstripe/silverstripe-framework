<?php

namespace SilverStripe\ORM\Tests;

use InvalidArgumentException;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\InjectorNotFoundException;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Filterable;
use SilverStripe\ORM\Filters\ExactMatchFilter;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Tests\DataObjectTest\Bracket;
use SilverStripe\ORM\Tests\DataObjectTest\EquipmentCompany;
use SilverStripe\ORM\Tests\DataObjectTest\Fan;
use SilverStripe\ORM\Tests\DataObjectTest\Player;
use SilverStripe\ORM\Tests\DataObjectTest\Sortable;
use SilverStripe\ORM\Tests\DataObjectTest\SubTeam;
use SilverStripe\ORM\Tests\DataObjectTest\Team;
use SilverStripe\ORM\Tests\DataObjectTest\TeamComment;
use SilverStripe\ORM\Tests\DataObjectTest\ValidatedObject;
use SilverStripe\ORM\Tests\DataObjectTest\Staff;

/**
 * @skipUpgrade
 */
class DataListTest extends SapphireTest
{

    // Borrow the model from DataObjectTest
    protected static $fixture_file = 'DataObjectTest.yml';

    public static function getExtraDataObjects()
    {
        return array_merge(
            DataObjectTest::$extra_data_objects,
            ManyManyListTest::$extra_data_objects
        );
    }


    public function testFilterDataObjectByCreatedDate()
    {
        // create an object to test with
        $obj1 = new ValidatedObject();
        $obj1->Name = 'test obj 1';
        $obj1->write();
        $this->assertTrue($obj1->isInDB());

        // reload the object from the database and reset its Created timestamp to a known value
        $obj1 = ValidatedObject::get()->filter(array('Name' => 'test obj 1'))->first();
        $this->assertTrue(is_object($obj1));
        $this->assertEquals('test obj 1', $obj1->Name);
        $obj1->Created = '2013-01-01 00:00:00';
        $obj1->write();

        // reload the object again and make sure that our Created date was properly persisted
        $obj1 = ValidatedObject::get()->filter(array('Name' => 'test obj 1'))->first();
        $this->assertTrue(is_object($obj1));
        $this->assertEquals('test obj 1', $obj1->Name);
        $this->assertEquals('2013-01-01 00:00:00', $obj1->Created);

        // now save a second object to the DB with an automatically-set Created value
        $obj2 = new ValidatedObject();
        $obj2->Name = 'test obj 2';
        $obj2->write();
        $this->assertTrue($obj2->isInDB());

        // and a third object
        $obj3 = new ValidatedObject();
        $obj3->Name = 'test obj 3';
        $obj3->write();
        $this->assertTrue($obj3->isInDB());

        // now test the filtering based on Created timestamp
        $list = ValidatedObject::get()
            ->filter(array('Created:GreaterThan' => '2013-02-01 00:00:00'))
            ->toArray();
        $this->assertEquals(2, count($list));
    }

    public function testSubtract()
    {
        $comment1 = $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment1');
        $subtractList = TeamComment::get()->filter('ID', $comment1->ID);
        $fullList = TeamComment::get();
        $newList = $fullList->subtract($subtractList);
        $this->assertEquals(2, $newList->Count(), 'List should only contain two objects after subtraction');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSubtractBadDataclassThrowsException()
    {
        $teamsComments = TeamComment::get();
        $teams = Team::get();
        $teamsComments->subtract($teams);
    }

    public function testListCreationSortAndLimit()
    {
        // By default, a DataList will contain all items of that class
        $list = TeamComment::get()->sort('ID');

        // We can iterate on the DataList
        $names = array();
        foreach ($list as $item) {
            $names[] = $item->Name;
        }
        $this->assertEquals(array('Joe', 'Bob', 'Phil'), $names);

        // If we don't want to iterate, we can extract a single column from the list with column()
        $this->assertEquals(array('Joe', 'Bob', 'Phil'), $list->column('Name'));

        // We can sort a list
        $list = $list->sort('Name');
        $this->assertEquals(array('Bob', 'Joe', 'Phil'), $list->column('Name'));

        // We can also restrict the output to a range
        $this->assertEquals(array('Joe', 'Phil'), $list->limit(2, 1)->column('Name'));
    }

    public function testLimitAndOffset()
    {
        $list = TeamComment::get();
        $check = $list->limit(3);

        $this->assertEquals(3, $check->count());

        $check = $list->limit(1);
        $this->assertEquals(1, $check->count());

        $check = $list->limit(1, 1);
        $this->assertEquals(1, $check->count());

        $check = $list->limit(false);
        $this->assertEquals(3, $check->count());

        $check = $list->limit(null);
        $this->assertEquals(3, $check->count());

        $check = $list->limit(null, 2);
        $this->assertEquals(1, $check->count());

        // count()/first()/last() methods may alter limit/offset, so run the query and manually check the count
        $check = $list->limit(null, 1)->toArray();
        $this->assertEquals(2, count($check));
    }

    public function testDistinct()
    {
        $list = TeamComment::get();
        $this->assertContains('SELECT DISTINCT', $list->dataQuery()->sql($params), 'Query is set as distinct by default');

        $list = $list->distinct(false);
        $this->assertNotContains('SELECT DISTINCT', $list->dataQuery()->sql($params), 'Query does not contain distinct');

        $list = $list->distinct(true);
        $this->assertContains('SELECT DISTINCT', $list->dataQuery()->sql($params), 'Query contains distinct');
    }

    public function testDataClass()
    {
        $list = TeamComment::get();
        $this->assertEquals(DataObjectTest\TeamComment::class, $list->dataClass());
    }

    public function testDataClassCaseInsensitive()
    {
        $list = DataList::create(strtolower(DataObjectTest\TeamComment::class));
        $this->assertTrue($list->exists());
    }

    public function testClone()
    {
        $list = TeamComment::get();
        $this->assertEquals($list, clone($list));
    }

    public function testSql()
    {
        $db = DB::get_conn();
        $list = TeamComment::get();
        $expected = 'SELECT DISTINCT "DataObjectTest_TeamComment"."ClassName", '
            . '"DataObjectTest_TeamComment"."LastEdited", "DataObjectTest_TeamComment"."Created", '
            . '"DataObjectTest_TeamComment"."Name", "DataObjectTest_TeamComment"."Comment", '
            . '"DataObjectTest_TeamComment"."TeamID", "DataObjectTest_TeamComment"."ID", '
            . 'CASE WHEN "DataObjectTest_TeamComment"."ClassName" IS NOT NULL '
            . 'THEN "DataObjectTest_TeamComment"."ClassName" ELSE '
            . $db->quoteString(DataObjectTest\TeamComment::class)
            . ' END AS "RecordClassName" FROM "DataObjectTest_TeamComment"'
            . ' ORDER BY "DataObjectTest_TeamComment"."Name" ASC';
        $this->assertSQLEquals($expected, $list->sql($parameters));
    }

    public function testInnerJoin()
    {
        $db = DB::get_conn();

        $list = TeamComment::get();
        $list = $list->innerJoin(
            'DataObjectTest_Team',
            '"DataObjectTest_Team"."ID" = "DataObjectTest_TeamComment"."TeamID"',
            'Team'
        );

        $expected = 'SELECT DISTINCT "DataObjectTest_TeamComment"."ClassName", '
            . '"DataObjectTest_TeamComment"."LastEdited", "DataObjectTest_TeamComment"."Created", '
            . '"DataObjectTest_TeamComment"."Name", "DataObjectTest_TeamComment"."Comment", '
            . '"DataObjectTest_TeamComment"."TeamID", "DataObjectTest_TeamComment"."ID", '
            . 'CASE WHEN "DataObjectTest_TeamComment"."ClassName" IS NOT NULL'
            . ' THEN "DataObjectTest_TeamComment"."ClassName" ELSE '
            . $db->quoteString(DataObjectTest\TeamComment::class)
            . ' END AS "RecordClassName" FROM "DataObjectTest_TeamComment" INNER JOIN '
            . '"DataObjectTest_Team" AS "Team" ON "DataObjectTest_Team"."ID" = '
            . '"DataObjectTest_TeamComment"."TeamID"'
            . ' ORDER BY "DataObjectTest_TeamComment"."Name" ASC';


        $this->assertSQLEquals($expected, $list->sql($parameters));
        $this->assertEmpty($parameters);
    }

    public function testInnerJoinParameterised()
    {
        $db = DB::get_conn();

        $list = TeamComment::get();
        $list = $list->innerJoin(
            'DataObjectTest_Team',
            '"DataObjectTest_Team"."ID" = "DataObjectTest_TeamComment"."TeamID" '
            . 'AND "DataObjectTest_Team"."Title" LIKE ?',
            'Team',
            20,
            array('Team%')
        );

        $expected = 'SELECT DISTINCT "DataObjectTest_TeamComment"."ClassName", '
            . '"DataObjectTest_TeamComment"."LastEdited", "DataObjectTest_TeamComment"."Created", '
            . '"DataObjectTest_TeamComment"."Name", "DataObjectTest_TeamComment"."Comment", '
            . '"DataObjectTest_TeamComment"."TeamID", "DataObjectTest_TeamComment"."ID", '
            . 'CASE WHEN "DataObjectTest_TeamComment"."ClassName" IS NOT NULL'
            . ' THEN "DataObjectTest_TeamComment"."ClassName" ELSE '
            . $db->quoteString(DataObjectTest\TeamComment::class)
            . ' END AS "RecordClassName" FROM "DataObjectTest_TeamComment" INNER JOIN '
            . '"DataObjectTest_Team" AS "Team" ON "DataObjectTest_Team"."ID" = '
            . '"DataObjectTest_TeamComment"."TeamID" '
            . 'AND "DataObjectTest_Team"."Title" LIKE ?'
            . ' ORDER BY "DataObjectTest_TeamComment"."Name" ASC';

        $this->assertSQLEquals($expected, $list->sql($parameters));
        $this->assertEquals(array('Team%'), $parameters);
    }

    public function testLeftJoin()
    {
        $db = DB::get_conn();

        $list = TeamComment::get();
        $list = $list->leftJoin(
            'DataObjectTest_Team',
            '"DataObjectTest_Team"."ID" = "DataObjectTest_TeamComment"."TeamID"',
            'Team'
        );

        $expected = 'SELECT DISTINCT "DataObjectTest_TeamComment"."ClassName", '
            . '"DataObjectTest_TeamComment"."LastEdited", "DataObjectTest_TeamComment"."Created", '
            . '"DataObjectTest_TeamComment"."Name", "DataObjectTest_TeamComment"."Comment", '
            . '"DataObjectTest_TeamComment"."TeamID", "DataObjectTest_TeamComment"."ID", '
            . 'CASE WHEN "DataObjectTest_TeamComment"."ClassName" IS NOT NULL '
            . 'THEN "DataObjectTest_TeamComment"."ClassName" ELSE '
            . $db->quoteString(DataObjectTest\TeamComment::class)
            . ' END AS "RecordClassName" FROM "DataObjectTest_TeamComment" LEFT JOIN "DataObjectTest_Team" '
            . 'AS "Team" ON "DataObjectTest_Team"."ID" = "DataObjectTest_TeamComment"."TeamID"'
            . ' ORDER BY "DataObjectTest_TeamComment"."Name" ASC';


        $this->assertSQLEquals($expected, $list->sql($parameters));
        $this->assertEmpty($parameters);
    }

    public function testLeftJoinParameterised()
    {
        $db = DB::get_conn();

        $list = TeamComment::get();
        $list = $list->leftJoin(
            'DataObjectTest_Team',
            '"DataObjectTest_Team"."ID" = "DataObjectTest_TeamComment"."TeamID" '
            . 'AND "DataObjectTest_Team"."Title" LIKE ?',
            'Team',
            20,
            array('Team%')
        );

        $expected = 'SELECT DISTINCT "DataObjectTest_TeamComment"."ClassName", '
            . '"DataObjectTest_TeamComment"."LastEdited", "DataObjectTest_TeamComment"."Created", '
            . '"DataObjectTest_TeamComment"."Name", "DataObjectTest_TeamComment"."Comment", '
            . '"DataObjectTest_TeamComment"."TeamID", "DataObjectTest_TeamComment"."ID", '
            . 'CASE WHEN "DataObjectTest_TeamComment"."ClassName" IS NOT NULL'
            . ' THEN "DataObjectTest_TeamComment"."ClassName" ELSE '
            . $db->quoteString(DataObjectTest\TeamComment::class)
            . ' END AS "RecordClassName" FROM "DataObjectTest_TeamComment" LEFT JOIN '
            . '"DataObjectTest_Team" AS "Team" ON "DataObjectTest_Team"."ID" = '
            . '"DataObjectTest_TeamComment"."TeamID" '
            . 'AND "DataObjectTest_Team"."Title" LIKE ?'
            . ' ORDER BY "DataObjectTest_TeamComment"."Name" ASC';

        $this->assertSQLEquals($expected, $list->sql($parameters));
        $this->assertEquals(array('Team%'), $parameters);
    }

    public function testToNestedArray()
    {
        $list = TeamComment::get()->sort('ID');
        $nestedArray = $list->toNestedArray();
        $expected = array(
            0=>
            array(
                'ClassName'=>DataObjectTest\TeamComment::class,
                'Name'=>'Joe',
                'Comment'=>'This is a team comment by Joe',
                'TeamID'=> $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment1')->TeamID,
            ),
            1=>
            array(
                'ClassName'=>DataObjectTest\TeamComment::class,
                'Name'=>'Bob',
                'Comment'=>'This is a team comment by Bob',
                'TeamID'=> $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment2')->TeamID,
            ),
            2=>
            array(
                'ClassName'=>DataObjectTest\TeamComment::class,
                'Name'=>'Phil',
                'Comment'=>'Phil is a unique guy, and comments on team2',
                'TeamID'=> $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment3')->TeamID,
            ),
        );
        $this->assertEquals(3, count($nestedArray));
        $this->assertEquals($expected[0]['Name'], $nestedArray[0]['Name']);
        $this->assertEquals($expected[1]['Comment'], $nestedArray[1]['Comment']);
        $this->assertEquals($expected[2]['TeamID'], $nestedArray[2]['TeamID']);
    }

    public function testMap()
    {
        $map = TeamComment::get()->map()->toArray();
        $expected = array(
            $this->idFromFixture(DataObjectTest\TeamComment::class, 'comment1') => 'Joe',
            $this->idFromFixture(DataObjectTest\TeamComment::class, 'comment2') => 'Bob',
            $this->idFromFixture(DataObjectTest\TeamComment::class, 'comment3') => 'Phil'
        );

        $this->assertEquals($expected, $map);
        $otherMap = TeamComment::get()->map('Name', 'TeamID')->toArray();
        $otherExpected = array(
            'Joe' => $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment1')->TeamID,
            'Bob' => $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment2')->TeamID,
            'Phil' => $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment3')->TeamID
        );

        $this->assertEquals($otherExpected, $otherMap);
    }

    public function testAmbiguousAggregate()
    {
        // Test that we avoid ambiguity error when a field exists on two joined tables
        // Fetch the sponsors in a round-about way to simulate this
        $teamID = $this->idFromFixture(DataObjectTest\Team::class, 'team2');
        $sponsors = EquipmentCompany::get()->filter('SponsoredTeams.ID', $teamID);
        $this->assertNotNull($sponsors->Max('ID'));
        $this->assertNotNull($sponsors->Min('ID'));
        $this->assertNotNull($sponsors->Avg('ID'));
        $this->assertNotNull($sponsors->Sum('ID'));

        // Test non-orm many_many_extraFields
        $company = $this->objFromFixture(EquipmentCompany::class, 'equipmentcompany1');
        $this->assertNotNull($company->SponsoredTeams()->Max('SponsorFee'));
        $this->assertNotNull($company->SponsoredTeams()->Min('SponsorFee'));
        $this->assertNotNull($company->SponsoredTeams()->Avg('SponsorFee'));
        $this->assertNotNull($company->SponsoredTeams()->Sum('SponsorFee'));
    }

    public function testEach()
    {
        $list = TeamComment::get();

        $count = 0;
        $list->each(
            function ($item) use (&$count) {
                $count++;
                $this->assertInstanceOf(TeamComment::class, $item);
            }
        );

        $this->assertEquals($count, $list->count());
    }

    public function testWhere()
    {
        // We can use raw SQL queries with where.  This is only recommended for advanced uses;
        // if you can, you should use filter().
        $list = TeamComment::get();

        // where() returns a new DataList, like all the other modifiers, so it can be chained.
        $list2 = $list->where('"Name" = \'Joe\'');
        $this->assertEquals(array('This is a team comment by Joe'), $list2->column('Comment'));

        // The where() clauses are chained together with AND
        $list3 = $list2->where('"Name" = \'Bob\'');
        $this->assertEquals(array(), $list3->column('Comment'));
    }

    /**
     * Test DataList->byID()
     */
    public function testByID()
    {
        // We can get a single item by ID.
        $id = $this->idFromFixture(DataObjectTest\Team::class, 'team2');
        $team = Team::get()->byID($id);

        // byID() returns a DataObject, rather than a DataList
        $this->assertInstanceOf(DataObjectTest\Team::class, $team);
        $this->assertEquals('Team 2', $team->Title);

        // Assert that filtering on ID searches by the base table, not the child table field
        $query = SubTeam::get()->filter('ID', 4)->sql($parameters);
        $this->assertContains('WHERE ("DataObjectTest_Team"."ID" = ?)', $query);
        $this->assertNotContains('WHERE ("DataObjectTest_SubTeam"."ID" = ?)', $query);
    }

    public function testByIDs()
    {
        $knownIDs = $this->allFixtureIDs(DataObjectTest\Player::class);
        $removedID = array_pop($knownIDs);
        $filteredPlayers = Player::get()->byIDs($knownIDs);
        foreach ($filteredPlayers as $player) {
            $this->assertContains($player->ID, $knownIDs);
            $this->assertNotEquals($removedID, $player->ID);
        }
    }

    /**
     * Test DataList->removeByID()
     */
    public function testRemoveByID()
    {
        $list = Team::get();
        $id = $this->idFromFixture(DataObjectTest\Team::class, 'team2');

        $this->assertNotNull($list->byID($id));
        $list->removeByID($id);
        $this->assertNull($list->byID($id));
    }

    /**
     * Test DataList->canSortBy()
     */
    public function testCanSortBy()
    {
        // Basic check
        $team = Team::get();
        $this->assertTrue($team->canSortBy("Title"));
        $this->assertFalse($team->canSortBy("SomethingElse"));

        // Subclasses
        $subteam = SubTeam::get();
        $this->assertTrue($subteam->canSortBy("Title"));
        $this->assertTrue($subteam->canSortBy("SubclassDatabaseField"));
    }

    public function testDataListArrayAccess()
    {
        $list = Team::get()->sort('Title');

        // We can use array access to refer to single items in the DataList, as if it were an array
        $this->assertEquals("Subteam 1", $list[0]->Title);
        $this->assertEquals("Subteam 3", $list[2]->Title);
        $this->assertEquals("Team 2", $list[4]->Title);
    }

    public function testFind()
    {
        $list = Team::get();
        $record = $list->find('Title', 'Team 1');
        $this->assertEquals($this->idFromFixture(DataObjectTest\Team::class, 'team1'), $record->ID);
    }

    public function testFindById()
    {
        $list = Team::get();
        $record = $list->find('ID', $this->idFromFixture(DataObjectTest\Team::class, 'team1'));
        $this->assertEquals('Team 1', $record->Title);
        // Test that you can call it twice on the same list
        $record = $list->find('ID', $this->idFromFixture(DataObjectTest\Team::class, 'team2'));
        $this->assertEquals('Team 2', $record->Title);
    }

    public function testSimpleSort()
    {
        $list = TeamComment::get();
        $list = $list->sort('Name');
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testSimpleSortOneArgumentASC()
    {
        $list = TeamComment::get();
        $list = $list->sort('Name ASC');
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testSimpleSortOneArgumentDESC()
    {
        $list = TeamComment::get();
        $list = $list->sort('Name DESC');
        $this->assertEquals('Phil', $list->first()->Name, 'Last comment should be from Phil');
        $this->assertEquals('Bob', $list->last()->Name, 'First comment should be from Bob');
    }

    public function testSortOneArgumentMultipleColumns()
    {
        $list = TeamComment::get();
        $list = $list->sort('TeamID ASC, Name DESC');
        $this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testSimpleSortASC()
    {
        $list = TeamComment::get();
        $list = $list->sort('Name', 'asc');
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testSimpleSortDESC()
    {
        $list = TeamComment::get();
        $list = $list->sort('Name', 'desc');
        $this->assertEquals('Phil', $list->first()->Name, 'Last comment should be from Phil');
        $this->assertEquals('Bob', $list->last()->Name, 'First comment should be from Bob');
    }

    public function testSortWithArraySyntaxSortASC()
    {
        $list = TeamComment::get();
        $list = $list->sort(array('Name'=>'asc'));
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testSortWithArraySyntaxSortDESC()
    {
        $list = TeamComment::get();
        $list = $list->sort(array('Name'=>'desc'));
        $this->assertEquals('Phil', $list->first()->Name, 'Last comment should be from Phil');
        $this->assertEquals('Bob', $list->last()->Name, 'First comment should be from Bob');
    }

    public function testSortWithMultipleArraySyntaxSort()
    {
        $list = TeamComment::get();
        $list = $list->sort(array('TeamID'=>'asc','Name'=>'desc'));
        $this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testSortWithCompositeSyntax()
    {
        // Phil commented on team with founder surname "Aaron"
        $list = TeamComment::get();
        $list = $list->sort('Team.Founder.Surname', 'asc');
        $this->assertEquals('Phil', $list->first()->Name);
        $list = $list->sort('Team.Founder.Surname', 'desc');
        $this->assertEquals('Phil', $list->last()->Name);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Fans is not a linear relation on model SilverStripe\ORM\Tests\DataObjectTest\Player
     */
    public function testSortInvalidParameters()
    {
        $list = Team::get();
        $list->sort('Founder.Fans.Surname'); // Can't sort on has_many
    }

    public function testSortNumeric()
    {
        $list = Sortable::get();
        $list1 = $list->sort('Sort', 'ASC');
        $this->assertEquals(
            array(
            -10,
            -2,
            -1,
            0,
            1,
            2,
            10
            ),
            $list1->column('Sort')
        );
    }

    public function testSortMixedCase()
    {
        $list = Sortable::get();
        $list1 = $list->sort('Name', 'ASC');
        $this->assertEquals(
            array(
            'Bob',
            'bonny',
            'jane',
            'John',
            'sam',
            'Steve',
            'steven'
            ),
            $list1->column('Name')
        );
    }

    /**
     * Test DataList->canFilterBy()
     */
    public function testCanFilterBy()
    {
        // Basic check
        $team = Team::get();
        $this->assertTrue($team->canFilterBy("Title"));
        $this->assertFalse($team->canFilterBy("SomethingElse"));

        // Has one
        $this->assertTrue($team->canFilterBy("CaptainID"));
        $this->assertTrue($team->canFilterBy("Captain.ShirtNumber"));
        $this->assertFalse($team->canFilterBy("SomethingElse.ShirtNumber"));
        $this->assertFalse($team->canFilterBy("Captain.SomethingElse"));
        $this->assertTrue($team->canFilterBy("Captain.FavouriteTeam.Captain.ShirtNumber"));

        // Has many
        $this->assertTrue($team->canFilterBy("Fans.Name"));
        $this->assertFalse($team->canFilterBy("SomethingElse.Name"));
        $this->assertFalse($team->canFilterBy("Fans.SomethingElse"));

        // Many many
        $this->assertTrue($team->canFilterBy("Players.FirstName"));
        $this->assertFalse($team->canFilterBy("SomethingElse.FirstName"));
        $this->assertFalse($team->canFilterBy("Players.SomethingElse"));

        // Subclasses
        $subteam = SubTeam::get();
        $this->assertTrue($subteam->canFilterBy("Title"));
        $this->assertTrue($subteam->canFilterBy("SubclassDatabaseField"));
    }

    /**
     * $list->filter('Name', 'bob'); // only bob in the list
     */
    public function testSimpleFilter()
    {
        $list = Team::get();
        $list = $list->filter('Title', 'Team 2');
        $this->assertEquals(1, $list->count());
        $this->assertEquals('Team 2', $list->first()->Title, 'List should only contain Team 2');
        $this->assertEquals('Team 2', $list->last()->Title, 'Last should only contain Team 2');
    }

    public function testSimpleFilterEndsWith()
    {
        $list = TeamComment::get();
        $list = $list->filter('Name:EndsWith', 'b');
        $this->assertEquals(1, $list->count());
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
    }

    public function testSimpleFilterExactMatchFilter()
    {
        $list = TeamComment::get();
        $list = $list->filter('Name:ExactMatch', 'Bob');
        $this->assertEquals(1, $list->count());
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
    }

    public function testSimpleFilterGreaterThanFilter()
    {
        $list = TeamComment::get();
        $list = $list->filter('TeamID:GreaterThan', $this->idFromFixture(DataObjectTest\Team::class, 'team1'));
        $this->assertEquals(1, $list->count());
        $this->assertEquals('Phil', $list->first()->Name, 'First comment should be from Phil');
    }

    public function testSimpleFilterGreaterThanOrEqualFilter()
    {
        $list = TeamComment::get();
        $list = $list->filter(
            'TeamID:GreaterThanOrEqual',
            $this->idFromFixture(DataObjectTest\Team::class, 'team1')
        )->sort("ID");
        $this->assertEquals(3, $list->count());
        $this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Joe');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testSimpleFilterLessThanFilter()
    {
        $list = TeamComment::get();
        $list = $list->filter(
            'TeamID:LessThan',
            $this->idFromFixture(DataObjectTest\Team::class, 'team2')
        )->sort('Name');
        $this->assertEquals(2, $list->count());
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Joe', $list->Last()->Name, 'Last comment should be from Joe');
    }

    public function testSimpleFilterLessThanOrEqualFilter()
    {
        $list = TeamComment::get();
        $list = $list->filter(
            'TeamID:LessThanOrEqual',
            $this->idFromFixture(DataObjectTest\Team::class, 'team1')
        )->sort('ID');
        $this->assertEquals(2, $list->count());
        $this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Joe');
        $this->assertEquals('Bob', $list->Last()->Name, 'Last comment should be from Bob');
    }

    public function testSimplePartialMatchFilter()
    {
        $list = TeamComment::get();
        $list = $list->filter('Name:PartialMatch', 'o')->sort('Name');
        $this->assertEquals(2, $list->count());
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Joe', $list->last()->Name, 'First comment should be from Joe');
    }

    public function testSimpleFilterStartsWith()
    {
        $list = TeamComment::get();
        $list = $list->filter('Name:StartsWith', 'B');
        $this->assertEquals(1, $list->count());
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
    }

    /**
     * @expectedException \SilverStripe\Core\Injector\InjectorNotFoundException
     * @expectedExceptionMessage Class DataListFilter.Bogus does not exist
     */
    public function testSimpleFilterWithNonExistingComparisator()
    {
        $list = TeamComment::get();
        $list->filter('Comment:Bogus', 'team comment');
    }

    /**
     * Invalid modifiers are treated as failed filter construction
     *
     * @expectedException \SilverStripe\Core\Injector\InjectorNotFoundException
     * @expectedExceptionMessage Class DataListFilter.invalidmodifier does not exist
     */
    public function testInvalidModifier()
    {
        $list = TeamComment::get();
        $list->filter('Comment:invalidmodifier', 'team comment');
    }

    /**
     * $list->filter('Name', array('aziz', 'bob'); // aziz and bob in list
     */
    public function testSimpleFilterWithMultiple()
    {
        $list = TeamComment::get();
        $list = $list->filter('Name', array('Bob','Phil'));
        $list = $list->sort('Name', 'ASC');
        $this->assertEquals(2, $list->count());
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testMultipleFilterWithNoMatch()
    {
        $list = TeamComment::get();
        $list = $list->filter(array('Name'=>'Bob', 'Comment'=>'Phil is a unique guy, and comments on team2'));
        $this->assertEquals(0, $list->count());
    }

    /**
     *  $list->filter(array('Name'=>'bob, 'Age'=>21)); // bob with the age 21
     */
    public function testFilterMultipleArray()
    {
        $list = TeamComment::get();
        $list = $list->filter(array('Name'=>'Bob', 'Comment'=>'This is a team comment by Bob'));
        $list = $list->sort('Name', 'ASC');
        $this->assertEquals(1, $list->count());
        $this->assertEquals('Bob', $list->first()->Name, 'Only comment should be from Bob');
    }

    public function testFilterMultipleWithTwoMatches()
    {
        $list = TeamComment::get();
        $list = $list->filter(array('TeamID'=>$this->idFromFixture(DataObjectTest\Team::class, 'team1')));
        $this->assertEquals(2, $list->count());
    }

    public function testFilterMultipleWithArrayFilter()
    {
        $list = TeamComment::get();
        $list = $list->filter(array('Name'=>array('Bob','Phil')));
        $list = $list->sort('Name', 'ASC');
        $this->assertEquals(2, $list->count(), 'There should be two comments');
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testFilterMultipleWithArrayFilterAndModifiers()
    {
        $list = TeamComment::get();
        $list = $list->filter(array('Name:StartsWith'=>array('Bo', 'Jo')));
        $list = $list->sort('Name', 'ASC');
        $this->assertEquals(2, $list->count());
        $this->assertEquals('Bob', $list->first()->Name);
        $this->assertEquals('Joe', $list->last()->Name);
    }

    /**
     * $list->filter(array('Name'=>array('aziz','bob'), 'Age'=>array(21, 43)));
     */
    public function testFilterArrayInArray()
    {
        $list = TeamComment::get();
        $list = $list->filter(
            array(
            'Name'=>array('Bob','Phil'),
            'TeamID'=>array($this->idFromFixture(DataObjectTest\Team::class, 'team1')))
        );
        $this->assertEquals(1, $list->count(), 'There should be one comment');
        $this->assertEquals('Bob', $list->first()->Name, 'Only comment should be from Bob');
    }

    public function testFilterWithModifiers()
    {
        $list = TeamComment::get();
        $nocaseList = $list->filter('Name:nocase', 'bob');
        $this->assertEquals(1, $nocaseList->count(), 'There should be one comment');
        $caseList = $list->filter('Name:case', 'bob');
        $this->assertEquals(0, $caseList->count(), 'There should be no comments');
        $gtList = $list->filter(
            'TeamID:GreaterThan:not',
            $this->idFromFixture(DataObjectTest\Team::class, 'team1')
        );
        $this->assertEquals(2, $gtList->count());
    }

    /**
     * Test that a filter correctly aliases relationships that share common classes
     */
    public function testFilterSharedRelationalClasses()
    {
        /** @var Bracket $final1 */
        $final1 = $this->objFromFixture(Bracket::class, 'final');
        $prefinal1 = $this->objFromFixture(Bracket::class, 'prefinal1');
        $prefinal2 = $this->objFromFixture(Bracket::class, 'prefinal2');
        $semifinal1 = $this->objFromFixture(Bracket::class, 'semifinal1');
        $team2 = $this->objFromFixture(Team::class, 'team2');

        // grand child can be found from parent
        $found = Bracket::get()->filter('Next.Next.Title', $final1->Title);
        $this->assertListEquals(
            [['Title' => $semifinal1->Title]],
            $found
        );

        // grand child can be found from child
        $found = Bracket::get()->filter('Next.Title', $prefinal1->Title);
        $this->assertListEquals(
            [['Title' => $semifinal1->Title]],
            $found
        );

        // child can be found from parent
        $found = Bracket::get()->filter('Next.Title', $final1->Title);
        $this->assertListEquals(
            [
                ['Title' => $prefinal1->Title],
                ['Title' => $prefinal2->Title]
            ],
            $found
        );

        // Complex filter, get brackets where the following bracket was won by team 1
        // Note: Includes results from multiple levels
        $found = Bracket::get()->filter('Next.Winner.Title', $team2->Title);
        $this->assertListEquals(
            [
                ['Title' => $prefinal1->Title],
                ['Title' => $prefinal2->Title],
                ['Title' => $semifinal1->Title]
            ],
            $found
        );
    }

    public function testFilterOnImplicitJoinWithSharedInheritance()
    {
        $list = DataObjectTest\RelationChildFirst::get()->filter(array(
            'ManyNext.ID' => array(
                $this->idFromFixture(DataObjectTest\RelationChildSecond::class, 'test1'),
                $this->idFromFixture(DataObjectTest\RelationChildSecond::class, 'test2'),
            ),
        ));
        $this->assertEquals(2, $list->count());
        $ids = $list->column('ID');
        $this->assertContains($this->idFromFixture(DataObjectTest\RelationChildFirst::class, 'test1'), $ids);
        $this->assertContains($this->idFromFixture(DataObjectTest\RelationChildFirst::class, 'test2'), $ids);
    }

    public function testFilterAny()
    {
        $list = TeamComment::get();
        $list = $list->filterAny('Name', 'Bob');
        $this->assertEquals(1, $list->count());
    }

    public function testFilterAnyWithRelation()
    {
        $list = Player::get();
        $list = $list->filterAny(array(
            'Teams.Title:StartsWith' => 'Team',
            'ID:GreaterThan' => 0,
        ));
        $this->assertCount(4, $list);
    }

    public function testFilterAnyMultipleArray()
    {
        $list = TeamComment::get();
        $list = $list->filterAny(array('Name'=>'Bob', 'Comment'=>'This is a team comment by Bob'));
        $this->assertEquals(1, $list->count());
        $this->assertEquals('Bob', $list->first()->Name, 'Only comment should be from Bob');
    }

    public function testFilterAnyOnFilter()
    {
        $list = TeamComment::get();
        $list = $list->filter(
            array(
            'TeamID'=>$this->idFromFixture(DataObjectTest\Team::class, 'team1')
            )
        );
        $list = $list->filterAny(
            array(
            'Name'=>array('Phil', 'Joe'),
            'Comment'=>'This is a team comment by Bob'
            )
        );
        $list = $list->sort('Name');
        $this->assertEquals(2, $list->count());
        $this->assertEquals(
            'Bob',
            $list->offsetGet(0)->Name,
            'Results should include comments from Bob, matched by comment and team'
        );
        $this->assertEquals(
            'Joe',
            $list->offsetGet(1)->Name,
            'Results should include comments by Joe, matched by name and team (not by comment)'
        );

        $list = TeamComment::get();
        $list = $list->filter(
            array(
            'TeamID'=>$this->idFromFixture(DataObjectTest\Team::class, 'team1')
            )
        );
        $list = $list->filterAny(
            array(
            'Name'=>array('Phil', 'Joe'),
            'Comment'=>'This is a team comment by Bob'
            )
        );
        $list = $list->sort('Name');
        $list = $list->filter(array('Name' => 'Bob'));
        $this->assertEquals(1, $list->count());
        $this->assertEquals(
            'Bob',
            $list->offsetGet(0)->Name,
            'Results should include comments from Bob, matched by name and team'
        );
    }

    public function testFilterAnyMultipleWithArrayFilter()
    {
        $list = TeamComment::get();
        $list = $list->filterAny(array('Name'=>array('Bob','Phil')));
        $this->assertEquals(2, $list->count(), 'There should be two comments');
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testFilterAnyArrayInArray()
    {
        $list = TeamComment::get();
        $list = $list->filterAny(
            array(
            'Name'=>array('Bob','Phil'),
            'TeamID'=>array($this->idFromFixture(DataObjectTest\Team::class, 'team1')))
        )
            ->sort('Name');
        $this->assertEquals(3, $list->count());
        $this->assertEquals(
            'Bob',
            $list->offsetGet(0)->Name,
            'Results should include comments from Bob, matched by name and team'
        );
        $this->assertEquals(
            'Joe',
            $list->offsetGet(1)->Name,
            'Results should include comments by Joe, matched by team (not by name)'
        );
        $this->assertEquals(
            'Phil',
            $list->offsetGet(2)->Name,
            'Results should include comments from Phil, matched by name (even if he\'s not in Team1)'
        );
    }

    public function testFilterOnJoin()
    {
        $list = TeamComment::get()
            ->leftJoin(
                'DataObjectTest_Team',
                '"DataObjectTest_Team"."ID" = "DataObjectTest_TeamComment"."TeamID"'
            )->filter(
                array(
                'Title' => 'Team 1'
                )
            );

        $this->assertEquals(2, $list->count());
        $values = $list->column('Name');
        $this->assertEquals(array_intersect($values, array('Joe', 'Bob')), $values);
    }

    public function testFilterOnImplicitJoin()
    {
        // Many to many
        $list = Team::get()
            ->filter('Players.FirstName', array('Captain', 'Captain 2'));

        $this->assertEquals(2, $list->count());

        // Has many
        $list = Team::get()
            ->filter('Comments.Name', array('Joe', 'Phil'));

        $this->assertEquals(2, $list->count());

        // Has one
        $list = Player::get()
            ->filter('FavouriteTeam.Title', 'Team 1');

        $this->assertEquals(1, $list->count());
        $this->assertEquals('007', $list->first()->ShirtNumber);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage MascotAnimal is not a relation on model SilverStripe\ORM\Tests\DataObjectTest\Team
     */
    public function testFilterOnInvalidRelation()
    {
        // Filter on missing relation 'MascotAnimal'
        Team::get()
            ->filter('MascotAnimal.Name', 'Richard')
            ->toArray();
    }

    public function testFilterAndExcludeById()
    {
        $id = $this->idFromFixture(SubTeam::class, 'subteam1');
        $list = SubTeam::get()->filter('ID', $id);
        $this->assertEquals($id, $list->first()->ID);

        $list = SubTeam::get();
        $this->assertEquals(3, count($list));
        $this->assertEquals(2, count($list->exclude('ID', $id)));
    }

    /**
     * @skipUpgrade
     */
    public function testFilterByNull()
    {
        $list = Fan::get();
        // Force DataObjectTest_Fan/fan5::Email to empty string
        $fan5id = $this->idFromFixture(Fan::class, 'fan5');
        DB::prepared_query("UPDATE \"DataObjectTest_Fan\" SET \"Email\" = '' WHERE \"ID\" = ?", array($fan5id));

        // Filter by null email
        $nullEmails = $list->filter('Email', null);
        $this->assertListEquals(
            array(
            array(
                'Name' => 'Stephen',
            ),
            array(
                'Name' => 'Mitch',
            )
            ),
            $nullEmails
        );

        // Filter by non-null
        $nonNullEmails = $list->filter('Email:not', null);
        $this->assertListEquals(
            array(
            array(
                'Name' => 'Damian',
                'Email' => 'damian@thefans.com',
            ),
            array(
                'Name' => 'Richard',
                'Email' => 'richie@richers.com',
            ),
            array(
                'Name' => 'Hamish',
            )
            ),
            $nonNullEmails
        );

        // Filter by empty only
        $emptyOnly = $list->filter('Email', '');
        $this->assertListEquals(
            array(
            array(
                'Name' => 'Hamish',
            )
            ),
            $emptyOnly
        );

        // Non-empty only. This should include null values, since ExactMatchFilter works around
        // the caveat that != '' also excludes null values in ANSI SQL-92 behaviour.
        $nonEmptyOnly = $list->filter('Email:not', '');
        $this->assertListEquals(
            array(
            array(
                'Name' => 'Damian',
                'Email' => 'damian@thefans.com',
            ),
            array(
                'Name' => 'Richard',
                'Email' => 'richie@richers.com',
            ),
            array(
                'Name' => 'Stephen',
            ),
            array(
                'Name' => 'Mitch',
            )
            ),
            $nonEmptyOnly
        );

        // Filter by many including null, empty string, and non-empty
        $items1 = $list->filter('Email', array(null, '', 'damian@thefans.com'));
        $this->assertListEquals(
            array(
            array(
                'Name' => 'Damian',
                'Email' => 'damian@thefans.com',
            ),
            array(
                'Name' => 'Stephen',
            ),
            array(
                'Name' => 'Mitch',
            ),
            array(
                'Name' => 'Hamish',
            )
            ),
            $items1
        );

        // Filter exclusion of above list
        $items2 = $list->filter('Email:not', array(null, '', 'damian@thefans.com'));
        $this->assertListEquals(
            array(
            array(
                'Name' => 'Richard',
                'Email' => 'richie@richers.com',
            ),
            ),
            $items2
        );

        // Filter by many including empty string and non-empty
        $items3 = $list->filter('Email', array('', 'damian@thefans.com'));
        $this->assertListEquals(
            array(
            array(
                'Name' => 'Damian',
                'Email' => 'damian@thefans.com',
            ),
            array(
                'Name' => 'Hamish',
            )
            ),
            $items3
        );

        // Filter by many including empty string and non-empty
        // This also relies no the workaround for null comparison as in the $nonEmptyOnly test
        $items4 = $list->filter('Email:not', array('', 'damian@thefans.com'));
        $this->assertListEquals(
            array(
            array(
                'Name' => 'Richard',
                'Email' => 'richie@richers.com',
            ),
            array(
                'Name' => 'Stephen',
            ),
            array(
                'Name' => 'Mitch',
            )
            ),
            $items4
        );

        // Filter by many including empty string and non-empty
        // The extra null check isn't necessary, but check that this doesn't fail
        $items5 = $list->filterAny(
            array(
            'Email:not' => array('', 'damian@thefans.com'),
            'Email' => null
            )
        );
        $this->assertListEquals(
            array(
            array(
                'Name' => 'Richard',
                'Email' => 'richie@richers.com',
            ),
            array(
                'Name' => 'Stephen',
            ),
            array(
                'Name' => 'Mitch',
            )
            ),
            $items5
        );

        // Filter by null or empty values
        $items6 = $list->filter('Email', array(null, ''));
        $this->assertListEquals(
            array(
            array(
                'Name' => 'Stephen',
            ),
            array(
                'Name' => 'Mitch',
            ),
            array(
                'Name' => 'Hamish',
            )
            ),
            $items6
        );
    }

    /**
     * Test null checks with case modifiers
     */
    public function testFilterByNullCase()
    {
        // Test with case (case/nocase both use same code path)
        // Test with and without null, and with inclusion/exclusion permutations
        $list = Fan::get();

        // Only an explicit NOT NULL should include null values
        $items6 = $list->filter('Email:not:case', array(null, '', 'damian@thefans.com'));
        $this->assertSQLContains(' AND "DataObjectTest_Fan"."Email" IS NOT NULL', $items6->sql());

        // These should all include values where Email IS NULL
        $items7 = $list->filter('Email:nocase', array(null, '', 'damian@thefans.com'));
        $this->assertSQLContains(' OR "DataObjectTest_Fan"."Email" IS NULL', $items7->sql());
        $items8 = $list->filter('Email:not:case', array('', 'damian@thefans.com'));
        $this->assertSQLContains(' OR "DataObjectTest_Fan"."Email" IS NULL', $items8->sql());

        // These should not contain any null checks at all
        $items9 = $list->filter('Email:nocase', array('', 'damian@thefans.com'));
        $this->assertSQLNotContains('"DataObjectTest_Fan"."Email" IS NULL', $items9->sql());
        $this->assertSQLNotContains('"DataObjectTest_Fan"."Email" IS NOT NULL', $items9->sql());
    }

    public function testAggregateDBName()
    {
        $filter = new ExactMatchFilter(
            'Comments.Count()'
        );
        $filter->apply(new DataQuery(DataObjectTest\Team::class));
        $this->assertEquals('COUNT("comments_DataObjectTest_TeamComment"."ID")', $filter->getDBName());

        foreach (['Comments.Max(ID)', 'Comments.Max( ID )', 'Comments.Max(  ID)'] as $name) {
            $filter = new ExactMatchFilter($name);
            $filter->apply(new DataQuery(DataObjectTest\Team::class));
            $this->assertEquals('MAX("comments_DataObjectTest_TeamComment"."ID")', $filter->getDBName());
        }
    }

    public function testAggregateFilterExceptions()
    {
        $ex = null;
        try {
            $filter = new ExactMatchFilter('Comments.Max( This will not parse! )');
        } catch (\Exception $e) {
            $ex = $e;
        }
        $this->assertInstanceOf(\InvalidArgumentException::class, $ex);
        $this->assertRegExp('/Malformed/', $ex->getMessage());


        $filter = new ExactMatchFilter('Comments.Max(NonExistentColumn)');
        $filter->setModel(new DataObjectTest\Team());
        $ex = null;
        try {
            $name = $filter->getDBName();
        } catch (\Exception $e) {
            $ex = $e;
        }
        $this->assertInstanceOf(\InvalidArgumentException::class, $ex);
        $this->assertRegExp('/Invalid column/', $ex->getMessage());
    }

    public function testAggregateFilters()
    {
        $teams = Team::get()->filter('Comments.Count()', 2);

        $team1 = $this->objFromFixture(Team::class, 'team1');
        $team2 = $this->objFromFixture(Team::class, 'team2');
        $team3 = $this->objFromFixture(Team::class, 'team3');
        $team4 = $this->objFromFixture(SubTeam::class, 'subteam1');
        $team5 = $this->objFromFixture(SubTeam::class, 'subteam2_with_player_relation');
        $team6 = $this->objFromFixture(SubTeam::class, 'subteam3_with_empty_fields');

        $company1 = $this->objFromFixture(EquipmentCompany::class, 'equipmentcompany1');
        $company2 = $this->objFromFixture(EquipmentCompany::class, 'equipmentcompany2');

        $company1->CurrentStaff()->add(Staff::create(['Salary' => 3])->write());
        $company1->CurrentStaff()->add(Staff::create(['Salary' => 5])->write());
        $company2->CurrentStaff()->add(Staff::create(['Salary' => 4])->write());

        $this->assertCount(1, $teams);
        $this->assertEquals($team1->ID, $teams->first()->ID);

        $teams = Team::get()->filter('Comments.Count()', [1,2]);

        $this->assertCount(2, $teams);
        foreach ([$team1, $team2] as $expectedTeam) {
            $this->assertContains($expectedTeam->ID, $teams->column('ID'));
        }

        $teams = Team::get()->filter('Comments.Count():GreaterThan', 1);

        $this->assertCount(1, $teams);
        $this->assertContains(
            $this->objFromFixture(Team::class, 'team1')->ID,
            $teams->column('ID')
        );

        $teams = Team::get()->filter('Comments.Count():LessThan', 2);

        $this->assertCount(5, $teams);
        foreach ([$team2, $team3, $team4, $team5, $team6] as $expectedTeam) {
            $this->assertContains($expectedTeam->ID, $teams->column('ID'));
        }

        $teams = Team::get()->filter('Comments.Count():GreaterThanOrEqual', 1);

        $this->assertCount(2, $teams);
        foreach ([$team1, $team2] as $expectedTeam) {
            $this->assertContains($expectedTeam->ID, $teams->column('ID'));
        }

        $teams = Team::get()->filter('Comments.Count():LessThanOrEqual', 1);

        $this->assertCount(5, $teams);
        foreach ([$team2, $team3, $team4, $team5, $team6] as $expectedTeam) {
            $this->assertContains($expectedTeam->ID, $teams->column('ID'));
        }

        $companies = EquipmentCompany::get()->filter('CurrentStaff.Max(Salary)', 5);
        $this->assertCount(1, $companies);
        $this->assertEquals($company1->ID, $companies->first()->ID);

        $companies = EquipmentCompany::get()->filter('CurrentStaff.Min(Salary)', 3);
        $this->assertCount(1, $companies);
        $this->assertEquals($company1->ID, $companies->first()->ID);

        $companies = EquipmentCompany::get()->filter('CurrentStaff.Max(Salary):GreaterThan', 3);
        $this->assertCount(2, $companies);
        foreach ([$company1, $company2] as $expectedTeam) {
            $this->assertContains($expectedTeam->ID, $companies->column('ID'));
        }

        $companies = EquipmentCompany::get()->filter('CurrentStaff.Sum(Salary)', 8);
        $this->assertCount(1, $companies);
        $this->assertEquals($company1->ID, $companies->first()->ID);

        $companies = EquipmentCompany::get()->filter('CurrentStaff.Sum(Salary):LessThan', 7);
        $this->assertCount(1, $companies);
        $this->assertEquals($company2->ID, $companies->first()->ID);

        $companies = EquipmentCompany::get()->filter('CurrentStaff.Sum(Salary):GreaterThan', 100);
        $this->assertCount(0, $companies);

        $companies = EquipmentCompany::get()->filter('CurrentStaff.Sum(Salary):GreaterThan', 7);
        $this->assertCount(1, $companies);
        $this->assertEquals($company1->ID, $companies->first()->ID);

        $companies = EquipmentCompany::get()->filter('CurrentStaff.Avg(Salary)', 4);
        $this->assertCount(2, $companies);
        foreach ([$company1, $company2] as $expectedTeam) {
            $this->assertContains($expectedTeam->ID, $companies->column('ID'));
        }

        $companies = EquipmentCompany::get()->filter('CurrentStaff.Avg(Salary):LessThan', 10);
        $this->assertCount(2, $companies);
        foreach ([$company1, $company2] as $expectedTeam) {
            $this->assertContains($expectedTeam->ID, $companies->column('ID'));
        }
    }

    /**
     * $list = $list->filterByCallback(function($item, $list) { return $item->Age == 21; })
     */
    public function testFilterByCallback()
    {
        $team1ID = $this->idFromFixture(DataObjectTest\Team::class, 'team1');
        $list = TeamComment::get();
        $list = $list->filterByCallback(
            function ($item, $list) use ($team1ID) {
                return $item->TeamID == $team1ID;
            }
        );

        $result = $list->column('Name');
        $expected = array_intersect($result, array('Joe', 'Bob'));

        $this->assertEquals(2, $list->count());
        $this->assertEquals($expected, $result, 'List should only contain comments from Team 1 (Joe and Bob)');
        $this->assertTrue($list instanceof Filterable, 'The List should be of type SS_Filterable');
    }

    /**
     * $list->exclude('Name', 'bob'); // exclude bob from list
     */
    public function testSimpleExclude()
    {
        $list = TeamComment::get();
        $list = $list->exclude('Name', 'Bob');
        $list = $list->sort('Name');
        $this->assertEquals(2, $list->count());
        $this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Joe');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }
    //
    /**
     * $list->exclude('Name', array('aziz', 'bob'); // exclude aziz and bob from list
     */
    public function testSimpleExcludeWithMultiple()
    {
        $list = TeamComment::get();
        $list = $list->exclude('Name', array('Joe','Phil'));
        $this->assertEquals(1, $list->count());
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
    }

    /**
     * $list->exclude(array('Name'=>'bob, 'Age'=>21)); // negative version
     */
    public function testMultipleExcludeWithMiss()
    {
        $list = TeamComment::get();
        $list = $list->exclude(array('Name'=>'Bob', 'Comment'=>'Does not match any comments'));
        $this->assertEquals(3, $list->count());
    }

    /**
     * $list->exclude(array('Name'=>'bob, 'Age'=>21)); // exclude bob that has Age 21
     */
    public function testMultipleExclude()
    {
        $list = TeamComment::get();
        $list = $list->exclude(array('Name'=>'Bob', 'Comment'=>'This is a team comment by Bob'));
        $this->assertEquals(2, $list->count());
    }

    /**
     * Test that if an exclude() is applied to a filter(), the filter() is still preserved.
     */
    public function testExcludeOnFilter()
    {
        /**
 * @var DataList $list
*/
        $list = TeamComment::get();
        $list = $list->filter('Comment', 'Phil is a unique guy, and comments on team2');
        $list = $list->exclude('Name', 'Bob');

        $sql = $list->sql($parameters);
        $this->assertSQLContains(
            'WHERE ("DataObjectTest_TeamComment"."Comment" = ?) AND (("DataObjectTest_TeamComment"."Name" != ? '
            . 'OR "DataObjectTest_TeamComment"."Name" IS NULL))',
            $sql
        );
        $this->assertEquals(array('Phil is a unique guy, and comments on team2', 'Bob'), $parameters);
    }

    public function testExcludeWithSearchFilter()
    {
        $list = TeamComment::get();
        $list = $list->exclude('Name:LessThan', 'Bob');

        $sql = $list->sql($parameters);
        $this->assertSQLContains('WHERE (("DataObjectTest_TeamComment"."Name" >= ?))', $sql);
        $this->assertEquals(array('Bob'), $parameters);
    }

    /**
     * Test exact match filter with empty array items
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot filter "DataObjectTest_TeamComment"."Name" against an empty set
     */
    public function testEmptyFilter()
    {
        $list = TeamComment::get();
        $list->exclude('Name', array());
    }

    /**
     * $list->exclude(array('Name'=>'bob, 'Age'=>array(21, 43))); // exclude bob with Age 21 or 43
     */
    public function testMultipleExcludeWithMultipleThatCheersEitherTeam()
    {
        $list = TeamComment::get();
        $list = $list->exclude(
            array('Name'=>'Bob', 'TeamID'=>array(
            $this->idFromFixture(DataObjectTest\Team::class, 'team1'),
            $this->idFromFixture(DataObjectTest\Team::class, 'team2')
            ))
        );
        $list = $list->sort('Name');
        $this->assertEquals(2, $list->count());
        $this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Phil');
        $this->assertEquals('Phil', $list->last()->Name, 'First comment should be from Phil');
    }

    /**
     * $list->exclude(array('Name'=>'bob, 'Age'=>array(21, 43))); // negative version
     */
    public function testMultipleExcludeWithMultipleThatCheersOnNonExistingTeam()
    {
        $list = TeamComment::get();
        $list = $list->exclude(array('Name'=>'Bob', 'TeamID'=>array(3)));
        $this->assertEquals(3, $list->count());
    }

    /**
     * $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43))); //negative version
     */
    public function testMultipleExcludeWithNoExclusion()
    {
        $list = TeamComment::get();
        $list = $list->exclude(
            array(
            'Name'=>array('Bob','Joe'),
            'Comment' => 'Phil is a unique guy, and comments on team2')
        );
        $this->assertEquals(3, $list->count());
    }

    /**
     *  $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43)));
     */
    public function testMultipleExcludeWithTwoArray()
    {
        $list = TeamComment::get();
        $list = $list->exclude(
            array('Name' => array('Bob','Joe'), 'TeamID' => array(
            $this->idFromFixture(DataObjectTest\Team::class, 'team1'),
            $this->idFromFixture(DataObjectTest\Team::class, 'team2')
            ))
        );
        $this->assertEquals(1, $list->count());
        $this->assertEquals('Phil', $list->last()->Name, 'Only comment should be from Phil');
    }

    /**
     *  $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43)));
     */
    public function testMultipleExcludeWithTwoArrayOneTeam()
    {
        $list = TeamComment::get();
        $list = $list->exclude(
            array(
            'Name' => array('Bob', 'Phil'),
            'TeamID' => array($this->idFromFixture(DataObjectTest\Team::class, 'team1')))
        );
        $list = $list->sort('Name');
        $this->assertEquals(2, $list->count());
        $this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Joe');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    /**
     *
     */
    public function testSortByRelation()
    {
        $list = TeamComment::get();
        $list = $list->sort(array('Team.Title' => 'DESC'));
        $this->assertEquals(3, $list->count());
        $this->assertEquals(
            $this->idFromFixture(DataObjectTest\Team::class, 'team2'),
            $list->first()->TeamID,
            'First comment should be for Team 2'
        );
        $this->assertEquals(
            $this->idFromFixture(DataObjectTest\Team::class, 'team1'),
            $list->last()->TeamID,
            'Last comment should be for Team 1'
        );
    }

    public function testReverse()
    {
        $list = TeamComment::get();
        $list = $list->sort('Name');
        $list = $list->reverse();

        $this->assertEquals('Bob', $list->last()->Name, 'Last comment should be from Bob');
        $this->assertEquals('Phil', $list->first()->Name, 'First comment should be from Phil');
    }

    public function testSortByComplexExpression()
    {
        // Test an expression with both spaces and commas. This test also tests that column() can be called
        // with a complex sort expression, so keep using column() below
        $teamClass = Convert::raw2sql(SubTeam::class);
        $list = Team::get()->sort(
            'CASE WHEN "DataObjectTest_Team"."ClassName" = \'' . $teamClass . '\' THEN 0 ELSE 1 END, "Title" DESC'
        );
        $this->assertEquals(
            array(
            'Subteam 3',
            'Subteam 2',
            'Subteam 1',
            'Team 3',
            'Team 2',
            'Team 1',
            ),
            $list->column("Title")
        );
    }
}
