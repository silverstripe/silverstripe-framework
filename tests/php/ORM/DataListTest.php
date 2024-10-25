<?php

namespace SilverStripe\ORM\Tests;

use BadMethodCallException;
use Exception;
use InvalidArgumentException;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\InjectorNotFoundException;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Connect\MySQLiConnector;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DB;
use SilverStripe\Model\List\SS_List;
use SilverStripe\ORM\Filters\ExactMatchFilter;
use SilverStripe\ORM\Tests\DataObjectTest\DataListQueryCounter;
use SilverStripe\ORM\Tests\DataObjectTest\Fixture;
use SilverStripe\ORM\Tests\DataObjectTest\Bracket;
use SilverStripe\ORM\Tests\DataObjectTest\EquipmentCompany;
use SilverStripe\ORM\Tests\DataObjectTest\Fan;
use SilverStripe\ORM\Tests\DataObjectTest\Player;
use SilverStripe\ORM\Tests\DataObjectTest\Sortable;
use SilverStripe\ORM\Tests\DataObjectTest\Staff;
use SilverStripe\ORM\Tests\DataObjectTest\SubTeam;
use SilverStripe\ORM\Tests\DataObjectTest\Team;
use SilverStripe\ORM\Tests\DataObjectTest\TeamComment;
use SilverStripe\ORM\Tests\DataObjectTest\ValidatedObject;
use SilverStripe\ORM\Tests\ManyManyListTest\Category;
use SilverStripe\ORM\Connect\DatabaseException;
use SilverStripe\ORM\FieldType\DBPrimaryKey;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\ORM\Filters\SearchFilter;
use SilverStripe\ORM\Tests\DataObjectTest\RelationChildFirst;
use SilverStripe\ORM\Tests\DataObjectTest\RelationChildSecond;
use PHPUnit\Framework\Attributes\DataProvider;

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
        $obj1 = ValidatedObject::get()->filter(['Name' => 'test obj 1'])->first();
        $this->assertTrue(is_object($obj1));
        $this->assertEquals('test obj 1', $obj1->Name);
        $obj1->Created = '2013-01-01 00:00:00';
        $obj1->write();

        // reload the object again and make sure that our Created date was properly persisted
        $obj1 = ValidatedObject::get()->filter(['Name' => 'test obj 1'])->first();
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
            ->filter(['Created:GreaterThan' => '2013-02-01 00:00:00'])
            ->toArray();
        $this->assertEquals(2, count($list ?? []));
    }

    public static function provideDefaultCaseSensitivity()
    {
        return [
            [
                'caseSensitive' => true,
                'filter' => ['FirstName' => 'captain'],
                'expectedCount' => 0,
            ],
            [
                'caseSensitive' => false,
                'filter' => ['FirstName' => 'captain'],
                'expectedCount' => 1,
            ],
            [
                'caseSensitive' => true,
                'filter' => ['FirstName:PartialMatch' => 'captain'],
                'expectedCount' => 0,
            ],
            [
                'caseSensitive' => false,
                'filter' => ['FirstName:PartialMatch' => 'captain'],
                'expectedCount' => 2,
            ],
            [
                'caseSensitive' => true,
                'filter' => ['FirstName:StartsWith' => 'captain'],
                'expectedCount' => 0,
            ],
            [
                'caseSensitive' => false,
                'filter' => ['FirstName:StartsWith' => 'captain'],
                'expectedCount' => 2,
            ],
            [
                'caseSensitive' => true,
                'filter' => ['Surname:EndsWith' => 'Keeper'],
                'expectedCount' => 0,
            ],
            [
                'caseSensitive' => false,
                'filter' => ['Surname:EndsWith' => 'Keeper'],
                'expectedCount' => 1,
            ],
        ];
    }

    #[DataProvider('provideDefaultCaseSensitivity')]
    public function testDefaultCaseSensitivity(bool $caseSensitive, array $filter, int $expectedCount)
    {
        SearchFilter::config()->set('default_case_sensitive', $caseSensitive);
        $list = Player::get()->filter($filter);
        $this->assertCount($expectedCount, $list);
    }

    public function testCount()
    {
        $list = new DataList(Team::class);
        $this->assertSame(6, $list->count());

        $list->removeAll();
        $this->assertSame(0, $list->count());
    }

    public function testExists()
    {
        $list = new DataList(Team::class);
        $this->assertTrue($list->exists());

        $list->removeAll();
        $this->assertFalse($list->exists());
    }

    public function testSubtract()
    {
        $comment1 = $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment1');
        $subtractList = TeamComment::get()->filter('ID', $comment1->ID);
        $fullList = TeamComment::get();
        $newList = $fullList->subtract($subtractList);
        $this->assertEquals(2, $newList->Count(), 'List should only contain two objects after subtraction');
    }

    public function testSubtractBadDataclassThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $teamsComments = TeamComment::get();
        $teams = Team::get();
        $teamsComments->subtract($teams);
    }

    public function testListCreationSortAndLimit()
    {
        // By default, a DataList will contain all items of that class
        $list = TeamComment::get()->sort('ID');

        // We can iterate on the DataList
        $names = [];
        foreach ($list as $item) {
            $names[] = $item->Name;
        }
        $this->assertEquals(['Joe', 'Bob', 'Phil'], $names);

        // If we don't want to iterate, we can extract a single column from the list with column()
        $this->assertEquals(['Joe', 'Bob', 'Phil'], $list->column('Name'));

        // We can sort a list
        $list = $list->sort('Name');
        $this->assertEquals(['Bob', 'Joe', 'Phil'], $list->column('Name'));

        // We can also restrict the output to a range
        $this->assertEquals(['Joe', 'Phil'], $list->limit(2, 1)->column('Name'));
    }

    public static function limitDataProvider(): array
    {
        return [
            'no limit' => [null, 0, 3],
            'smaller limit' => [2, 0, 2],
            'greater limit' => [4, 0, 3],
            'one limit' => [1, 0, 1],
            'zero limit' => [0, 0, 0],
            'limit and offset' => [1, 1, 1],
            'false limit equivalent to 0' => [false, 0, 0],
            'offset only' => [null, 2, 1],
            'offset greater than list length' => [null, 3, 0],
            'negative length' => [-1, 0, 0, true],
            'negative offset' => [0, -1, 0, true],
        ];
    }

    #[DataProvider('limitDataProvider')]
    public function testLimitAndOffset($length, $offset, $expectedCount, $expectException = false)
    {
        $list = TeamComment::get();

        if ($expectException) {
            $this->expectException(\InvalidArgumentException::class);
        }

        $this->assertCount($expectedCount, $list->limit($length, $offset));
        $this->assertCount(
            $expectedCount,
            $list->limit(0, 9999)->limit($length, $offset),
            'Follow up limit calls unset previous ones'
        );

        // count()/first()/last() methods may alter limit/offset, so run the query and manually check the count
        $this->assertCount($expectedCount, $list->limit($length, $offset)->toArray());
    }

    public function testDistinct()
    {
        $list = TeamComment::get();
        $this->assertStringContainsString('SELECT DISTINCT', $list->dataQuery()->sql($params), 'Query is set as distinct by default');

        $list = $list->distinct(false);
        $this->assertStringNotContainsString('SELECT DISTINCT', $list->dataQuery()->sql($params), 'Query does not contain distinct');

        $list = $list->distinct(true);
        $this->assertStringContainsString('SELECT DISTINCT', $list->dataQuery()->sql($params), 'Query contains distinct');
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

    public function testDbObject()
    {
        $list = DataList::create(TeamComment::class);
        $this->assertInstanceOf(DBPrimaryKey::class, $list->dbObject('ID'));
        $this->assertInstanceOf(DBVarchar::class, $list->dbObject('Name'));
        $this->assertInstanceOf(DBText::class, $list->dbObject('Comment'));
    }

    public function testGetIDList()
    {
        $list = DataList::create(TeamComment::class);
        $idList = $list->getIDList();
        $this->assertSame($list->column('ID'), array_keys($idList));
        $this->assertSame($list->column('ID'), array_values($idList));
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

    public static function provideJoin()
    {
        return [
            [
                'joinMethod' => 'innerJoin',
                'joinType' => 'INNER JOIN',
            ],
            [
                'joinMethod' => 'leftJoin',
                'joinType' => 'LEFT JOIN',
            ],
            [
                'joinMethod' => 'rightJoin',
                'joinType' => 'RIGHT JOIN',
            ],
        ];
    }

    #[DataProvider('provideJoin')]
    public function testJoin(string $joinMethod, string $joinType)
    {
        $db = DB::get_conn();

        $list = TeamComment::get();
        $list = $list->$joinMethod(
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
            . ' END AS "RecordClassName" FROM "DataObjectTest_TeamComment" ' . $joinType
            . ' "DataObjectTest_Team" AS "Team" ON "DataObjectTest_Team"."ID" = '
            . '"DataObjectTest_TeamComment"."TeamID"'
            . ' ORDER BY "DataObjectTest_TeamComment"."Name" ASC';

        $this->assertSQLEquals($expected, $list->sql($parameters));
        $this->assertEmpty($parameters);
    }

    #[DataProvider('provideJoin')]
    public function testJoinParameterised(string $joinMethod, string $joinType)
    {
        $db = DB::get_conn();

        $list = TeamComment::get();
        $list = $list->$joinMethod(
            'DataObjectTest_Team',
            '"DataObjectTest_Team"."ID" = "DataObjectTest_TeamComment"."TeamID" '
            . 'AND "DataObjectTest_Team"."Title" LIKE ?',
            'Team',
            20,
            ['Team%']
        );

        $expected = 'SELECT DISTINCT "DataObjectTest_TeamComment"."ClassName", '
            . '"DataObjectTest_TeamComment"."LastEdited", "DataObjectTest_TeamComment"."Created", '
            . '"DataObjectTest_TeamComment"."Name", "DataObjectTest_TeamComment"."Comment", '
            . '"DataObjectTest_TeamComment"."TeamID", "DataObjectTest_TeamComment"."ID", '
            . 'CASE WHEN "DataObjectTest_TeamComment"."ClassName" IS NOT NULL'
            . ' THEN "DataObjectTest_TeamComment"."ClassName" ELSE '
            . $db->quoteString(DataObjectTest\TeamComment::class)
            . ' END AS "RecordClassName" FROM "DataObjectTest_TeamComment" ' . $joinType
            . ' "DataObjectTest_Team" AS "Team" ON "DataObjectTest_Team"."ID" = '
            . '"DataObjectTest_TeamComment"."TeamID" '
            . 'AND "DataObjectTest_Team"."Title" LIKE ?'
            . ' ORDER BY "DataObjectTest_TeamComment"."Name" ASC';

        $this->assertSQLEquals($expected, $list->sql($parameters));
        $this->assertEquals(['Team%'], $parameters);
    }

    public function testToNestedArray()
    {
        $list = TeamComment::get()->sort('ID');
        $nestedArray = $list->toNestedArray();
        $expected = [
            0=>
            [
                'ClassName'=>DataObjectTest\TeamComment::class,
                'Name'=>'Joe',
                'Comment'=>'This is a team comment by Joe',
                'TeamID'=> $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment1')->TeamID,
            ],
            1=>
            [
                'ClassName'=>DataObjectTest\TeamComment::class,
                'Name'=>'Bob',
                'Comment'=>'This is a team comment by Bob',
                'TeamID'=> $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment2')->TeamID,
            ],
            2=>
            [
                'ClassName'=>DataObjectTest\TeamComment::class,
                'Name'=>'Phil',
                'Comment'=>'Phil is a unique guy, and comments on team2',
                'TeamID'=> $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment3')->TeamID,
            ],
        ];
        $this->assertEquals(3, count($nestedArray ?? []));
        $this->assertEquals($expected[0]['Name'], $nestedArray[0]['Name']);
        $this->assertEquals($expected[1]['Comment'], $nestedArray[1]['Comment']);
        $this->assertEquals($expected[2]['TeamID'], $nestedArray[2]['TeamID']);
    }

    public function testMap()
    {
        $map = TeamComment::get()->map()->toArray();
        $expected = [
            $this->idFromFixture(DataObjectTest\TeamComment::class, 'comment1') => 'Joe',
            $this->idFromFixture(DataObjectTest\TeamComment::class, 'comment2') => 'Bob',
            $this->idFromFixture(DataObjectTest\TeamComment::class, 'comment3') => 'Phil'
        ];

        $this->assertEquals($expected, $map);
        $otherMap = TeamComment::get()->map('Name', 'TeamID')->toArray();
        $otherExpected = [
            'Joe' => $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment1')->TeamID,
            'Bob' => $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment2')->TeamID,
            'Phil' => $this->objFromFixture(DataObjectTest\TeamComment::class, 'comment3')->TeamID
        ];

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
        $this->assertEquals(['This is a team comment by Joe'], $list2->column('Comment'));

        // The where() clauses are chained together with AND
        $list3 = $list2->where('"Name" = \'Bob\'');
        $this->assertEquals([], $list3->column('Comment'));
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
        $this->assertStringContainsString('WHERE ("DataObjectTest_Team"."ID" = ?)', $query);
        $this->assertStringNotContainsString('WHERE ("DataObjectTest_SubTeam"."ID" = ?)', $query);
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

    public function testRemove()
    {
        $list = Team::get();
        $obj = $this->objFromFixture(DataObjectTest\Team::class, 'team2');

        $this->assertNotNull($list->byID($obj->ID));
        $list->remove($obj);
        $this->assertNull($list->byID($obj->ID));
    }

    public function testRemoveWrongDataClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $list = Team::get();
        $list->remove(Player::get()->first());
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
     * Test DataList->removeAll()
     */
    public function testRemoveAll()
    {
        $list = Team::get();
        $this->assertGreaterThan(0, $list->count());
        $list->removeAll();
        $this->assertCount(0, $list);
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
        $list = $list->sort(['Name'=>'asc']);
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testSortWithArraySyntaxSortDESC()
    {
        $list = TeamComment::get();
        $list = $list->sort(['Name'=>'desc']);
        $this->assertEquals('Phil', $list->first()->Name, 'Last comment should be from Phil');
        $this->assertEquals('Bob', $list->last()->Name, 'First comment should be from Bob');
    }

    public function testSortWithMultipleArraySyntaxSort()
    {
        $list = TeamComment::get();
        $list = $list->sort(['TeamID'=>'asc','Name'=>'desc']);
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

    public function testSortInvalidParameters()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fans is not a linear relation on model SilverStripe\ORM\Tests\DataObjectTest\Player');
        $list = Team::get();
        $list->sort('Founder.Fans.Surname'); // Can't sort on has_many
    }

    public function testSortNumeric()
    {
        $list = Sortable::get();
        $list1 = $list->sort('Sort', 'ASC');
        $this->assertEquals(
            [
            -10,
            -2,
            -1,
            0,
            1,
            2,
            10
            ],
            $list1->column('Sort')
        );
    }

    public function testSortMixedCase()
    {
        $list = Sortable::get();
        $list1 = $list->sort('Name', 'ASC');
        $this->assertEquals(
            [
            'Bob',
            'bonny',
            'jane',
            'John',
            'sam',
            'Steve',
            'steven'
            ],
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

    public function testSimpleFilterWithNonExistingComparisator()
    {
        $this->expectException(InjectorNotFoundException::class);
        $this->expectExceptionMessageMatches('/Class "?DataListFilter.Bogus"? does not exist/');

        $list = TeamComment::get();
        $list->filter('Comment:Bogus', 'team comment');
    }

    /**
     * Invalid modifiers are treated as failed filter construction
     */
    public function testInvalidModifier()
    {
        $this->expectException(InjectorNotFoundException::class);
        $this->expectExceptionMessageMatches('/Class "?DataListFilter.invalidmodifier"? does not exist/');

        $list = TeamComment::get();
        $list->filter('Comment:invalidmodifier', 'team comment');
    }

    /**
     * $list->filter('Name', ['aziz', 'bob']); // aziz and bob in list
     */
    public function testSimpleFilterWithMultiple()
    {
        $list = TeamComment::get();
        $list = $list->filter('Name', ['Bob','Phil']);
        $list = $list->sort('Name', 'ASC');
        $this->assertEquals(2, $list->count());
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testMultipleFilterWithNoMatch()
    {
        $list = TeamComment::get();
        $list = $list->filter(['Name'=>'Bob', 'Comment'=>'Phil is a unique guy, and comments on team2']);
        $this->assertEquals(0, $list->count());
    }

    /**
     *  $list->filter(['Name'=>'bob, 'Age'=>21]); // bob with the age 21
     */
    public function testFilterMultipleArray()
    {
        $list = TeamComment::get();
        $list = $list->filter(['Name'=>'Bob', 'Comment'=>'This is a team comment by Bob']);
        $list = $list->sort('Name', 'ASC');
        $this->assertEquals(1, $list->count());
        $this->assertEquals('Bob', $list->first()->Name, 'Only comment should be from Bob');
    }

    public function testFilterMultipleWithTwoMatches()
    {
        $list = TeamComment::get();
        $list = $list->filter(['TeamID'=>$this->idFromFixture(DataObjectTest\Team::class, 'team1')]);
        $this->assertEquals(2, $list->count());
    }

    public function testFilterMultipleWithArrayFilter()
    {
        $list = TeamComment::get();
        $list = $list->filter(['Name'=>['Bob','Phil']]);
        $list = $list->sort('Name', 'ASC');
        $this->assertEquals(2, $list->count(), 'There should be two comments');
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testFilterMultipleWithArrayFilterAndModifiers()
    {
        $list = TeamComment::get();
        $list = $list->filter(['Name:StartsWith'=>['Bo', 'Jo']]);
        $list = $list->sort('Name', 'ASC');
        $this->assertEquals(2, $list->count());
        $this->assertEquals('Bob', $list->first()->Name);
        $this->assertEquals('Joe', $list->last()->Name);
    }

    /**
     * $list->filter(['Name'=>['aziz','bob'], 'Age'=>[21, 43]]);
     */
    public function testFilterArrayInArray()
    {
        $list = TeamComment::get();
        $list = $list->filter(
            [
            'Name'=>['Bob','Phil'],
            'TeamID'=>[$this->idFromFixture(DataObjectTest\Team::class, 'team1')]]
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
        $list = DataObjectTest\RelationChildFirst::get()->filter([
            'ManyNext.ID' => [
                $this->idFromFixture(DataObjectTest\RelationChildSecond::class, 'test1'),
                $this->idFromFixture(DataObjectTest\RelationChildSecond::class, 'test2'),
            ],
        ]);
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
        $list = $list->filterAny([
            'Teams.Title:StartsWith' => 'Team',
            'ID:GreaterThan' => 0,
        ]);
        $this->assertCount(4, $list);
    }

    public function testFilterAnyWithTwoGreaterThanFilters()
    {

        for ($i=1; $i<=3; $i++) {
            $f = new Fixture();
            $f->MyDecimal = $i;
            $f->write();

            $f = new Fixture();
            $f->MyInt = $i;
            $f->write();
        }

        $list = Fixture::get()->filterAny([
            'MyDecimal:GreaterThan' => 1, // 2 records
            'MyInt:GreaterThan' => 2, // 1 record
        ]);

        $this->assertCount(3, $list);
    }

    public function testFilterAnyMultipleArray()
    {
        $list = TeamComment::get();
        $list = $list->filterAny(['Name'=>'Bob', 'Comment'=>'This is a team comment by Bob']);
        $this->assertEquals(1, $list->count());
        $this->assertEquals('Bob', $list->first()->Name, 'Only comment should be from Bob');
    }

    public function testFilterAnyOnFilter()
    {
        $list = TeamComment::get();
        $list = $list->filter(
            [
            'TeamID'=>$this->idFromFixture(DataObjectTest\Team::class, 'team1')
            ]
        );
        $list = $list->filterAny(
            [
            'Name'=>['Phil', 'Joe'],
            'Comment'=>'This is a team comment by Bob'
            ]
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
            [
            'TeamID'=>$this->idFromFixture(DataObjectTest\Team::class, 'team1')
            ]
        );
        $list = $list->filterAny(
            [
            'Name'=>['Phil', 'Joe'],
            'Comment'=>'This is a team comment by Bob'
            ]
        );
        $list = $list->sort('Name');
        $list = $list->filter(['Name' => 'Bob']);
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
        $list = $list->filterAny(['Name'=>['Bob','Phil']]);
        $this->assertEquals(2, $list->count(), 'There should be two comments');
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testFilterAnyArrayInArray()
    {
        $list = TeamComment::get();
        $list = $list->filterAny(
            [
            'Name'=>['Bob','Phil'],
            'TeamID'=>[$this->idFromFixture(DataObjectTest\Team::class, 'team1')]]
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

    private function createTeam(int $playerCount)
    {
        $team = Team::create();
        $team->write();
        for ($i = 0; $i < $playerCount; $i++) {
            $player = Player::create();
            $player->write();
            $team->Players()->add($player);
        }
        return $team;
    }

    public function testFilterAnyManyManyAggregate()
    {
        Team::get()->removeAll();
        $team1 = $this->createTeam(1);
        $team2 = $this->createTeam(2);
        $team3 = $this->createTeam(3);
        $list = Team::get()->filterAny([
            'Players.Count():LessThan' => 2,
            'Players.Count():GreaterThan' => 2,
        ]);
        $match = 'HAVING ((COUNT("players_Member"."ID") < ?) OR (COUNT("players_Member"."ID") > ?))';
        $sql = str_replace("\n", '', $list->sql());
        $this->assertTrue(str_contains($sql, $match));
        $ids = $list->column('ID');
        sort($ids);
        $this->assertSame([$team1->ID, $team3->ID], $ids);
    }

    public function testFilterOnJoin()
    {
        $list = TeamComment::get()
            ->leftJoin(
                'DataObjectTest_Team',
                '"DataObjectTest_Team"."ID" = "DataObjectTest_TeamComment"."TeamID"'
            )->filter([
                'Title' => 'Team 1'
            ]);

        $this->assertEquals(2, $list->count());
        $values = $list->column('Name');
        $this->assertEquals(array_intersect($values ?? [], ['Joe', 'Bob']), $values);
    }

    public function testFilterOnImplicitJoin()
    {
        // Many to many
        $list = Team::get()
            ->filter('Players.FirstName', ['Captain', 'Captain 2']);

        $this->assertEquals(2, $list->count());

        // Has many
        $list = Team::get()
            ->filter('Comments.Name', ['Joe', 'Phil']);

        $this->assertEquals(2, $list->count());

        // Has one
        $list = Player::get()
            ->filter('FavouriteTeam.Title', 'Team 1');

        $this->assertEquals(1, $list->count());
        $this->assertEquals('007', $list->first()->ShirtNumber);
    }

    public function testFilterOnInvalidRelation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('MascotAnimal is not a relation on model SilverStripe\ORM\Tests\DataObjectTest\Team');
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
        $this->assertEquals(3, count($list ?? []));
        $this->assertEquals(2, count($list->exclude('ID', $id) ?? []));
    }

    public function testFilterByNull()
    {
        $list = Fan::get();
        // Force DataObjectTest_Fan/fan5::Email to empty string
        $fan5id = $this->idFromFixture(Fan::class, 'fan5');
        DB::prepared_query("UPDATE \"DataObjectTest_Fan\" SET \"Email\" = '' WHERE \"ID\" = ?", [$fan5id]);

        // Filter by null email
        $nullEmails = $list->filter('Email', null);
        $this->assertListEquals(
            [
            [
                'Name' => 'Stephen',
            ],
            [
                'Name' => 'Mitch',
            ]
            ],
            $nullEmails
        );

        // Filter by non-null
        $nonNullEmails = $list->filter('Email:not', null);
        $this->assertListEquals(
            [
            [
                'Name' => 'Damian',
                'Email' => 'damian@thefans.com',
            ],
            [
                'Name' => 'Richard',
                'Email' => 'richie@richers.com',
            ],
            [
                'Name' => 'Hamish',
            ]
            ],
            $nonNullEmails
        );

        // Filter by empty only
        $emptyOnly = $list->filter('Email', '');
        $this->assertListEquals(
            [
            [
                'Name' => 'Hamish',
            ]
            ],
            $emptyOnly
        );

        // Non-empty only. This should include null values, since ExactMatchFilter works around
        // the caveat that != '' also excludes null values in ANSI SQL-92 behaviour.
        $nonEmptyOnly = $list->filter('Email:not', '');
        $this->assertListEquals(
            [
            [
                'Name' => 'Damian',
                'Email' => 'damian@thefans.com',
            ],
            [
                'Name' => 'Richard',
                'Email' => 'richie@richers.com',
            ],
            [
                'Name' => 'Stephen',
            ],
            [
                'Name' => 'Mitch',
            ]
            ],
            $nonEmptyOnly
        );

        // Filter by many including null, empty string, and non-empty
        $items1 = $list->filter('Email', [null, '', 'damian@thefans.com']);
        $this->assertListEquals(
            [
            [
                'Name' => 'Damian',
                'Email' => 'damian@thefans.com',
            ],
            [
                'Name' => 'Stephen',
            ],
            [
                'Name' => 'Mitch',
            ],
            [
                'Name' => 'Hamish',
            ]
            ],
            $items1
        );

        // Filter exclusion of above list
        $items2 = $list->filter('Email:not', [null, '', 'damian@thefans.com']);
        $this->assertListEquals(
            [
            [
                'Name' => 'Richard',
                'Email' => 'richie@richers.com',
            ],
            ],
            $items2
        );

        // Filter by many including empty string and non-empty
        $items3 = $list->filter('Email', ['', 'damian@thefans.com']);
        $this->assertListEquals(
            [
            [
                'Name' => 'Damian',
                'Email' => 'damian@thefans.com',
            ],
            [
                'Name' => 'Hamish',
            ]
            ],
            $items3
        );

        // Filter by many including empty string and non-empty
        // This also relies no the workaround for null comparison as in the $nonEmptyOnly test
        $items4 = $list->filter('Email:not', ['', 'damian@thefans.com']);
        $this->assertListEquals(
            [
            [
                'Name' => 'Richard',
                'Email' => 'richie@richers.com',
            ],
            [
                'Name' => 'Stephen',
            ],
            [
                'Name' => 'Mitch',
            ]
            ],
            $items4
        );

        // Filter by many including empty string and non-empty
        // The extra null check isn't necessary, but check that this doesn't fail
        $items5 = $list->filterAny(
            [
            'Email:not' => ['', 'damian@thefans.com'],
            'Email' => null
            ]
        );
        $this->assertListEquals(
            [
            [
                'Name' => 'Richard',
                'Email' => 'richie@richers.com',
            ],
            [
                'Name' => 'Stephen',
            ],
            [
                'Name' => 'Mitch',
            ]
            ],
            $items5
        );

        // Filter by null or empty values
        $items6 = $list->filter('Email', [null, '']);
        $this->assertListEquals(
            [
            [
                'Name' => 'Stephen',
            ],
            [
                'Name' => 'Mitch',
            ],
            [
                'Name' => 'Hamish',
            ]
            ],
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
        $items6 = $list->filter('Email:not:case', [null, '', 'damian@thefans.com']);
        $this->assertSQLContains(' AND "DataObjectTest_Fan"."Email" IS NOT NULL', $items6->sql());

        // These should all include values where Email IS NULL
        $items7 = $list->filter('Email:nocase', [null, '', 'damian@thefans.com']);
        $this->assertSQLContains(' OR "DataObjectTest_Fan"."Email" IS NULL', $items7->sql());
        $items8 = $list->filter('Email:not:case', ['', 'damian@thefans.com']);
        $this->assertSQLContains(' OR "DataObjectTest_Fan"."Email" IS NULL', $items8->sql());

        // These should not contain any null checks at all
        $items9 = $list->filter('Email:nocase', ['', 'damian@thefans.com']);
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
        $this->assertMatchesRegularExpression('/Malformed/', $ex->getMessage());


        $filter = new ExactMatchFilter('Comments.Max(NonExistentColumn)');
        $filter->setModel(new DataObjectTest\Team());
        $ex = null;
        try {
            $name = $filter->getDBName();
        } catch (\Exception $e) {
            $ex = $e;
        }
        $this->assertInstanceOf(\InvalidArgumentException::class, $ex);
        $this->assertMatchesRegularExpression('/Invalid column/', $ex->getMessage());
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
        $expected = array_intersect($result ?? [], ['Joe', 'Bob']);

        $this->assertEquals(2, $list->count());
        $this->assertEquals($expected, $result, 'List should only contain comments from Team 1 (Joe and Bob)');
        $this->assertTrue($list instanceof SS_List, 'The List should be of type SS_List');
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
     * $list->exclude('Name', ['aziz', 'bob']); // exclude aziz and bob from list
     */
    public function testSimpleExcludeWithMultiple()
    {
        $list = TeamComment::get();
        $list = $list->exclude('Name', ['Joe','Phil']);
        $this->assertEquals(1, $list->count());
        $this->assertEquals('Bob', $list->first()->Name, 'First comment should be from Bob');
    }

    /**
     * $list->exclude(['Name'=>'bob, 'Age'=>21]); // negative version
     */
    public function testMultipleExcludeWithMiss()
    {
        $list = TeamComment::get();
        $list = $list->exclude(['Name'=>'Bob', 'Comment'=>'Does not match any comments']);
        $this->assertEquals(3, $list->count());
    }

    /**
     * $list->exclude(['Name'=>'bob, 'Age'=>21]); // exclude bob that has Age 21
     */
    public function testMultipleExclude()
    {
        $list = TeamComment::get();
        $list = $list->exclude(['Name'=>'Bob', 'Comment'=>'This is a team comment by Bob']);
        $this->assertEquals(2, $list->count());
    }

    /**
     * Test doesn't exclude if only matches one
     * $list->exclude(['Name'=>'bob, 'Age'=>21]); // exclude bob that has Age 21
     */
    public function testMultipleExcludeMultipleMatches()
    {
        $list = TeamComment::get();
        $list = $list->exclude(['Name'=>'Bob', 'Comment'=>'Phil is a unique guy, and comments on team2']);
        $this->assertCount(3, $list);
    }

    /**
     * // exclude only those that match both
     */
    public function testMultipleExcludeArraysMultipleMatches()
    {
        $list = TeamComment::get();
        $list = $list->exclude([
            'Name'=> ['Bob', 'Phil'],
            'Comment'=> [
                'This is a team comment by Bob',
                'Phil is a unique guy, and comments on team2'
            ]
        ]);
        $this->assertListEquals([['Name' => 'Joe']], $list);
    }

    /**
     * Exclude only which matches both params
     */
    public function testMultipleExcludeArraysMultipleMatchesOneMiss()
    {
        $list = TeamComment::get();
        $list = $list->exclude([
            'Name' => ['Bob', 'Phil'],
            'Comment' => [
                'Does not match any comments',
                'Phil is a unique guy, and comments on team2'
            ]
        ]);
        $list = $list->sort('Name');
        $this->assertListEquals(
            [
                ['Name' => 'Bob'],
                ['Name' => 'Joe'],
            ],
            $list
        );
    }

    /**
     * Test that if an exclude() is applied to a filter(), the filter() is still preserved.
     */
    public function testExcludeOnFilter()
    {
        $list = TeamComment::get();
        $list = $list->filter('Comment', 'Phil is a unique guy, and comments on team2');
        $list = $list->exclude('Name', 'Bob');

        $sql = $list->sql($parameters);
        $this->assertSQLContains(
            'WHERE ("DataObjectTest_TeamComment"."Comment" = ?) AND (("DataObjectTest_TeamComment"."Name" != ? '
            . 'OR "DataObjectTest_TeamComment"."Name" IS NULL))',
            $sql
        );
        $this->assertEquals(['Phil is a unique guy, and comments on team2', 'Bob'], $parameters);
        $this->assertListEquals([['Name' => 'Phil']], $list);
    }

    /**
     * Test that if a complicated exclude() is applied to a filter(), the filter() is still preserved.
     */
    public function testComplicatedExcludeOnFilter()
    {
        $list = TeamComment::get();
        $list = $list->filter('Name', ['Phil', 'Bob']);
        $list = $list->exclude('Name', ['Bob', 'Joe']);

        $sql = $list->sql($parameters);
        $this->assertSQLContains(
            'WHERE ("DataObjectTest_TeamComment"."Name" IN (?, ?)) AND (("DataObjectTest_TeamComment"."Name" NOT IN (?, ?) '
            . 'OR "DataObjectTest_TeamComment"."Name" IS NULL))',
            $sql
        );
        $this->assertEquals(['Phil', 'Bob', 'Bob', 'Joe'], $parameters);
        $this->assertListEquals([['Name' => 'Phil']], $list);
    }

    /**
     * Test that if a very complicated exclude() is applied to a filter(), the filter() is still preserved.
     */
    public function testVeryComplicatedExcludeOnFilter()
    {
        $list = TeamComment::get();
        $list = $list->filter('Name', ['Phil', 'Bob']);
        $list = $list->exclude([
            'Name' => ['Joe', 'Phil'],
            'Comment' => ['Matches no comments', 'Not a matching comment']
        ]);

        $sql = $list->sql($parameters);
        $this->assertSQLContains(
            'WHERE ("DataObjectTest_TeamComment"."Name" IN (?, ?)) '
            . 'AND (("DataObjectTest_TeamComment"."Name" NOT IN (?, ?) '
            . 'OR "DataObjectTest_TeamComment"."Name" IS NULL) '
            . 'OR ("DataObjectTest_TeamComment"."Comment" NOT IN (?, ?) '
            . 'OR "DataObjectTest_TeamComment"."Comment" IS NULL))',
            $sql
        );
        $this->assertEquals(['Phil', 'Bob', 'Joe', 'Phil', 'Matches no comments', 'Not a matching comment'], $parameters);
        $list = $list->sort('Name');
        $this->assertListEquals(
            [
                ['Name' => 'Bob'],
                ['Name' => 'Phil'],
            ],
            $list
        );
    }

    public function testExcludeWithSearchFilter()
    {
        $list = TeamComment::get();
        $list = $list->exclude('Name:LessThan', 'Bob');

        $sql = $list->sql($parameters);
        $this->assertSQLContains('WHERE (("DataObjectTest_TeamComment"."Name" >= ?))', $sql);
        $this->assertEquals(['Bob'], $parameters);
    }

    /**
     * Test that Bob and Phil are excluded (one match each)
     */
    public function testExcludeAny()
    {
        $list = TeamComment::get();
        $list = $list->excludeAny([
            'Name' => 'Bob',
            'Comment' => 'Phil is a unique guy, and comments on team2'
        ]);
        $this->assertListEquals([['Name' => 'Joe']], $list);
    }

    /**
     * Test that Bob and Phil are excluded by Name
     */
    public function testExcludeAnyArrays()
    {
        $list = TeamComment::get();
        $list = $list->excludeAny([
            'Name' => ['Bob', 'Phil'],
            'Comment' => 'No matching comments'
        ]);
        $this->assertListEquals([['Name' => 'Joe']], $list);
    }

    /**
     * Test that Bob is excluded by Name, Phil by comment
     */
    public function testExcludeAnyMultiArrays()
    {
        $list = TeamComment::get();
        $list = $list->excludeAny([
            'Name' => ['Bob', 'Fred'],
            'Comment' => ['No matching comments', 'Phil is a unique guy, and comments on team2']
        ]);
        $this->assertListEquals([['Name' => 'Joe']], $list);
    }

    public function testEmptyFilter()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot filter "DataObjectTest_TeamComment"."Name" against an empty set');
        $list = TeamComment::get();
        $list->exclude('Name', []);
    }

    /**
     * $list->exclude(['Name'=>'bob, 'Age'=>[21, 43]]); // exclude bob with Age 21 or 43
     */
    public function testMultipleExcludeWithMultipleThatCheersEitherTeam()
    {
        $list = TeamComment::get();
        $list = $list->exclude(
            ['Name'=>'Bob', 'TeamID'=>[
            $this->idFromFixture(DataObjectTest\Team::class, 'team1'),
            $this->idFromFixture(DataObjectTest\Team::class, 'team2')
            ]]
        );
        $list = $list->sort('Name');
        $this->assertEquals(2, $list->count());
        $this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Phil');
        $this->assertEquals('Phil', $list->last()->Name, 'First comment should be from Phil');
    }

    /**
     * $list->exclude(['Name'=>'bob, 'Age'=>[21, 43]]); // negative version
     */
    public function testMultipleExcludeWithMultipleThatCheersOnNonExistingTeam()
    {
        $list = TeamComment::get();
        $list = $list->exclude(['Name'=>'Bob', 'TeamID'=>[3]]);
        $this->assertEquals(3, $list->count());
    }

    /**
     * $list->exclude(['Name'=>['bob','phil'], 'Age'=>[21, 43]]); //negative version
     */
    public function testMultipleExcludeWithNoExclusion()
    {
        $list = TeamComment::get();
        $list = $list->exclude(
            [
            'Name'=>['Bob','Joe'],
            'Comment' => 'Phil is a unique guy, and comments on team2']
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
            ['Name' => ['Bob','Joe'], 'TeamID' => [
            $this->idFromFixture(DataObjectTest\Team::class, 'team1'),
            $this->idFromFixture(DataObjectTest\Team::class, 'team2')
            ]]
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
            [
            'Name' => ['Bob', 'Phil'],
            'TeamID' => [$this->idFromFixture(DataObjectTest\Team::class, 'team1')]]
        );
        $list = $list->sort('Name');
        $this->assertEquals(2, $list->count());
        $this->assertEquals('Joe', $list->first()->Name, 'First comment should be from Joe');
        $this->assertEquals('Phil', $list->last()->Name, 'Last comment should be from Phil');
    }

    public function testSortByRelation()
    {
        $list = TeamComment::get();
        $list = $list->sort(['Team.Title' => 'DESC']);
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

    public function testOrderByComplexExpression()
    {
        // Test an expression with both spaces and commas. This test also tests that column() can be called
        // with a complex sort expression, so keep using column() below
        $teamClass = Convert::raw2sql(SubTeam::class);
        $list = Team::get()->orderBy(
            'CASE WHEN "DataObjectTest_Team"."ClassName" = \'' . $teamClass . '\' THEN 0 ELSE 1 END, "Title" DESC'
        );
        $this->assertEquals(
            [
            'Subteam 3',
            'Subteam 2',
            'Subteam 1',
            'Team 3',
            'Team 2',
            'Team 1',
            ],
            $list->column("Title")
        );
    }

    #[DataProvider('provideRawSqlSortException')]
    public function testRawSqlSort(string $sort, string $type): void
    {
        $type = explode('|', $type)[0];
        if ($type === 'valid') {
            $this->expectNotToPerformAssertions();
        } elseif ($type === 'invalid-direction') {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessageMatches('/Invalid sort direction/');
        } elseif ($type === 'unknown-column') {
            if (!(DB::get_conn()->getConnector() instanceof MySQLiConnector)) {
                $this->markTestSkipped('Database connector is not MySQLiConnector');
            }
            $this->expectException(DatabaseException::class);
            $this->expectExceptionMessageMatches('/Unknown column/');
        } elseif ($type === 'invalid-column') {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessageMatches('/Invalid sort column/');
        } elseif ($type === 'unknown-relation') {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessageMatches('/is not a relation on model/');
        } else {
            throw new \Exception("Invalid type $type");
        }
        // column('ID') is required to get the database query be actually fired off
        Team::get()->sort($sort)->column('ID');
    }

    #[DataProvider('provideRawSqlSortException')]
    public function testRawSqlOrderBy(string $sort, string $type): void
    {
        $type = explode('|', $type)[1];
        if ($type === 'valid') {
            if (!str_contains($sort, '"') && !(DB::get_conn()->getConnector() instanceof MySQLiConnector)) {
                // don't test unquoted things in non-mysql
                $this->markTestSkipped('Database connector is not MySQLiConnector');
            }
            $this->expectNotToPerformAssertions();
        } else {
            if (!(DB::get_conn()->getConnector() instanceof MySQLiConnector)) {
                $this->markTestSkipped('Database connector is not MySQLiConnector');
            }
            $this->expectException(DatabaseException::class);
            if ($type === 'error-in-sql-syntax') {
                $this->expectExceptionMessageMatches('/You have an error in your SQL syntax/');
            } else {
                $this->expectExceptionMessageMatches('/Unknown column/');
            }
        }
        // column('ID') is required to get the database query be actually fired off
        Team::get()->orderBy($sort)->column('ID');
    }

    public static function provideRawSqlSortException(): array
    {
        return [
            ['Title', 'valid|valid'],
            ['Title asc', 'valid|valid'],
            ['"Title" ASC', 'valid|valid'],
            ['Title ASC, "DatabaseField"', 'valid|valid'],
            ['"Title", "DatabaseField" DESC', 'valid|valid'],
            ['Title ASC, DatabaseField DESC', 'valid|valid'],
            ['Title ASC, , DatabaseField DESC', 'invalid-column|unknown-column'],
            ['Captain.ShirtNumber', 'valid|unknown-column'],
            ['Captain.ShirtNumber ASC', 'valid|unknown-column'],
            ['"Captain"."ShirtNumber"', 'invalid-column|unknown-column'],
            ['"Captain"."ShirtNumber" DESC', 'invalid-column|unknown-column'],
            ['Title BACKWARDS', 'invalid-direction|error-in-sql-syntax'],
            ['"Strange non-existent column name"', 'invalid-column|unknown-column'],
            ['NonExistentColumn', 'unknown-column|unknown-column'],
            ['Team.NonExistentColumn', 'unknown-relation|unknown-column'],
            ['"DataObjectTest_Team"."NonExistentColumn" ASC', 'invalid-column|unknown-column'],
            ['"DataObjectTest_Team"."Title" ASC', 'invalid-column|valid'],
            ['DataObjectTest_Team.Title', 'unknown-relation|valid'],
            ['Title, 1 = 1', 'invalid-column|valid'],
            ["Title,'abc' = 'abc'", 'invalid-column|valid'],
            ['Title,Mod(ID,3)=1', 'invalid-column|valid'],
            ['(CASE WHEN ID < 3 THEN 1 ELSE 0 END)', 'invalid-column|valid'],
        ];
    }

    #[DataProvider('provideSortDirectionValidationTwoArgs')]
    public function testSortDirectionValidationTwoArgs(string $direction, string $type): void
    {
        if ($type === 'valid') {
            $this->expectNotToPerformAssertions();
        } else {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessageMatches('/Invalid sort direction/');
        }
        Team::get()->sort('Title', $direction)->column('ID');
    }

    public static function provideSortDirectionValidationTwoArgs(): array
    {
        return [
            ['ASC', 'valid'],
            ['asc', 'valid'],
            ['DESC', 'valid'],
            ['desc', 'valid'],
            ['BACKWARDS', 'invalid'],
        ];
    }

    /**
     * Test passing scalar values to sort()
     */
    #[DataProvider('provideSortScalarValues')]
    public function testSortScalarValues(mixed $emtpyValue, string $type): void
    {
        $this->assertSame(['Subteam 1'], Team::get()->limit(1)->column('Title'));
        $list = Team::get()->sort('Title DESC');
        $this->assertSame(['Team 3'], $list->limit(1)->column('Title'));
        $this->expectException(InvalidArgumentException::class);
        if ($type === 'invalid-scalar') {
            $this->expectExceptionMessage('sort() arguments must either be a string, an array, or null');
        }
        if ($type === 'empty-scalar') {
            $this->expectExceptionMessage('Invalid sort parameter');
        }

        $list = $list->sort($emtpyValue);
        $this->assertSame(['Subteam 1'], $list->limit(1)->column('Title'));
    }

    public static function provideSortScalarValues(): array
    {
        return [
            ['', 'empty-scalar'],
            [[], 'empty-scalar'],
            [false, 'invalid-scalar'],
            [true, 'invalid-scalar'],
            [0, 'invalid-scalar'],
            [1, 'invalid-scalar'],
        ];
    }

    /**
     * Explicity tests that sort(null) will wipe any existing sort on a DataList
     */
    public function testSortNull(): void
    {
        $list = Team::get()->sort('Title DESC');
        $query = $list->dataQuery()->getFinalisedQuery();
        $this->assertSame(
            ['"DataObjectTest_Team"."Title"' => 'DESC'],
            $query->getOrderBy(),
            'Calling sort on a DataList sets an Orderby on the underlying query.'
        );

        $list = $list->sort(null);
        $query = $list->dataQuery()->getFinalisedQuery();
        $this->assertEmpty(
            $query->getOrderBy(),
            'Calling sort with null on a DataList unsets the orderby on the underlying query.'
        );
    }

    public function testShuffle()
    {
        $list = Team::get()->shuffle();

        $this->assertSQLContains(DB::get_conn()->random() . ' AS "_SortColumn', $list->dataQuery()->sql());
    }

    public function testColumn()
    {
        // sorted so postgres won't complain about the order being different
        $list = RelationChildSecond::get()->sort('Title');
        $ids = [
            $this->idFromFixture(RelationChildSecond::class, 'test1'),
            $this->idFromFixture(RelationChildSecond::class, 'test2'),
            $this->idFromFixture(RelationChildSecond::class, 'test3'),
            $this->idFromFixture(RelationChildSecond::class, 'test3-duplicate'),
        ];

        // Test default
        $this->assertSame($ids, $list->column());

        // Test specific field
        $this->assertSame(['Test 1', 'Test 2', 'Test 3', 'Test 3'], $list->column('Title'));
    }

    public function testColumnUnique()
    {
        // sorted so postgres won't complain about the order being different
        $list = RelationChildSecond::get()->sort('Title');
        $ids = [
            $this->idFromFixture(RelationChildSecond::class, 'test1'),
            $this->idFromFixture(RelationChildSecond::class, 'test2'),
            $this->idFromFixture(RelationChildSecond::class, 'test3'),
            $this->idFromFixture(RelationChildSecond::class, 'test3-duplicate'),
        ];

        // Test default
        $this->assertSame($ids, $list->columnUnique());

        // Test specific field
        $this->assertSame(['Test 1', 'Test 2', 'Test 3'], $list->columnUnique('Title'));
    }

    public function testColumnFailureInvalidColumn()
    {
        $this->expectException(InvalidArgumentException::class);

        Category::get()->column('ObviouslyInvalidColumn');
    }

    public function testColumnFailureInvalidTable()
    {
        $this->expectException(InvalidArgumentException::class);

        $columnName = null;
        Category::get()
            ->applyRelation('Products.ID', $columnName)
            ->column('"ObviouslyInvalidTable"."ID"');
    }

    public function testColumnFromRelatedTable()
    {
        $columnName = null;
        $productTitles = Category::get()
            ->applyRelation('Products.Title', $columnName)
            ->column($columnName);

        $productTitles = array_diff($productTitles ?? [], [null]);
        sort($productTitles);

        $this->assertEquals([
            'Product A',
            'Product B',
        ], $productTitles);
    }

    public function testFirst()
    {
        $list = Sortable::get()->sort('Sort');
        $this->assertGreaterThanOrEqual(
            3,
            $list->count(),
            'We must have at least 3 items for this test to be valid'
        );
        $this->assertSame('Steve', $list->first()->Name);
    }

    public function testLast()
    {
        $list = Sortable::get()->sort('Sort');
        $this->assertGreaterThanOrEqual(
            3,
            $list->count(),
            'We must have at least 3 items for this test to be valid'
        );
        $this->assertSame('John', $list->last()->Name);
    }

    public function testOffsetGet()
    {
        $list = TeamComment::get()->sort('Name');
        $this->assertEquals('Bob', $list->offsetGet(0)->Name);
        $this->assertEquals('Joe', $list->offsetGet(1)->Name);
        $this->assertEquals('Phil', $list->offsetGet(2)->Name);
        $this->assertNull($list->offsetGet(999));
    }

    public function testOffsetExists()
    {
        $list = TeamComment::get()->sort('Name');
        $this->assertTrue($list->offsetExists(0));
        $this->assertTrue($list->offsetExists(1));
        $this->assertTrue($list->offsetExists(2));
        $this->assertFalse($list->offsetExists(999));
    }

    public function testOffsetGetNegative()
    {
        $list = TeamComment::get()->sort('Name');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$offset can not be negative. -1 was provided.');
        $list->offsetGet(-1);
    }

    public function testOffsetExistsNegative()
    {
        $list = TeamComment::get()->sort('Name');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$offset can not be negative. -1 was provided.');
        $list->offsetExists(-1);
    }

    public function testOffsetSet()
    {
        $list = TeamComment::get()->sort('Name');
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Can't alter items in a DataList using array-access");
        $list->offsetSet(0, null);
    }

    public function testOffsetUnset()
    {
        $list = TeamComment::get()->sort('Name');
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Can't alter items in a DataList using array-access");
        $list->offsetUnset(0);
    }

    #[DataProvider('provideRelation')]
    public function testRelation(string $parentClass, string $relation, ?array $expected)
    {
        $list = $parentClass::get()->relation($relation);
        if ($expected === null) {
            $this->assertNull($list);
        } else {
            $this->assertListEquals($expected, $list);
        }
    }

    public static function provideRelation()
    {
        return [
            'many_many' => [
                'parentClass' => RelationChildFirst::class,
                'relation' => 'ManyNext',
                'expected' => [
                    ['Title' => 'Test 1'],
                    ['Title' => 'Test 2'],
                    ['Title' => 'Test 3'],
                ],
            ],
            'has_many' => [
                'parentClass' => Team::class,
                'relation' => 'SubTeams',
                'expected' => [
                    ['Title' => 'Subteam 1'],
                ],
            ],
            // calling relation() for a has_one just gives you null
            'has_one' => [
                'parentClass' => DataObjectTest\Company::class,
                'relation' => 'Owner',
                'expected' => null,
            ],
        ];
    }

    #[DataProvider('provideCreateDataObject')]
    public function testCreateDataObject(string $dataClass, string $realClass, array $row)
    {
        $list = new DataList($dataClass);
        $obj = $list->createDataObject($row);

        // Validate the class is correct
        $this->assertSame($realClass, get_class($obj));

        // Validates all fields are available
        foreach ($row as $field => $value) {
            $this->assertSame($value, $obj->$field);
        }

        // Validates hydration only used if the row has an ID
        if (array_key_exists('ID', $row)) {
            $this->assertFalse($obj->isChanged());
        } else {
            $this->assertTrue($obj->isChanged());
        }
    }

    public static function provideCreateDataObject()
    {
        return [
            'no ClassName' => [
                'dataClass' => Team::class,
                'realClass' => Team::class,
                'row' => [
                    'ID' => 1,
                    'Title' => 'Team 1',
                    'NumericField' => '1',
                    // Extra field that doesn't exist on that class
                    'SubclassDatabaseField' => 'this shouldnt be there',
                ],
            ],
            'subclassed ClassName' => [
                'dataClass' => Team::class,
                'realClass' => SubTeam::class,
                'row' => [
                    'ClassName' => SubTeam::class,
                    'ID' => 1,
                    'Title' => 'Team 1',
                    'SubclassDatabaseField' => 'this time it should be there',
                ],
            ],
            'RecordClassName takes precedence' => [
                'dataClass' => Team::class,
                'realClass' => SubTeam::class,
                'row' => [
                    'ClassName' => Player::class,
                    'RecordClassName' => SubTeam::class,
                    'ID' => 1,
                    'Title' => 'Team 1',
                    'SubclassDatabaseField' => 'this time it should be there',
                ],
            ],
            'No ID' => [
                'dataClass' => Team::class,
                'realClass' => Team::class,
                'row' => [
                    'Title' => 'Team 1',
                    'NumericField' => '1',
                    'SubclassDatabaseField' => 'this shouldnt be there',
                ],
            ],
        ];
    }

    public function testDebug()
    {
        $list = Sortable::get();

        $result = $list->debug();
        $this->assertStringStartsWith('<h2>' . DataList::class . '</h2>', $result);
        $this->assertMatchesRegularExpression(
            '/<ul>\s*(<li style="list-style-type: disc; margin-left: 20px">.*?<\/li>)+\s*<\/ul>/s',
            $result
        );
        $this->assertStringEndsWith('</ul>', $result);
    }

    public function testChunkedFetch()
    {
        $expectedIDs = Team::get()->map('ID', 'ID')->toArray();
        $expectedSize = sizeof($expectedIDs ?? []);

        $dataQuery = new DataListQueryCounter(Team::class);
        $this->chunkTester(
            $expectedIDs,
            Team::get()->setDataQuery($dataQuery)->chunkedFetch(),
            $dataQuery,
            1
        );

        $dataQuery = new DataListQueryCounter(Team::class);
        $this->chunkTester(
            $expectedIDs,
            Team::get()->setDataQuery($dataQuery)->chunkedFetch(1),
            $dataQuery,
            $expectedSize+1
        );

        $dataQuery = new DataListQueryCounter(Team::class);
        $this->chunkTester(
            $expectedIDs,
            Team::get()->setDataQuery($dataQuery)->chunkedFetch($expectedSize),
            $dataQuery,
            2
        );

        $dataQuery = new DataListQueryCounter(Team::class);
        $this->chunkTester(
            $expectedIDs,
            Team::get()->setDataQuery($dataQuery)->chunkedFetch($expectedSize-1),
            $dataQuery,
            2
        );

        $dataQuery = new DataListQueryCounter(Team::class);
        $this->chunkTester(
            $expectedIDs,
            Team::get()->setDataQuery($dataQuery)->chunkedFetch($expectedSize+1),
            $dataQuery,
            1
        );
    }

    public function testFilteredChunk()
    {
        $dataQuery = new DataListQueryCounter(Team::class);
        $this->chunkTester(
            Team::get()->filter('ClassName', Team::class)->map('ID', 'ID')->toArray(),
            Team::get()->setDataQuery($dataQuery)->filter('ClassName', Team::class)->chunkedFetch(),
            $dataQuery,
            1
        );
    }

    public function testSortedChunk()
    {
        $dataQuery = new DataListQueryCounter(Team::class);
        $this->chunkTester(
            Team::get()->sort('ID', 'Desc')->map('ID', 'ID')->toArray(),
            Team::get()->setDataQuery($dataQuery)->sort('ID', 'Desc')->chunkedFetch(),
            $dataQuery,
            1
        );
    }

    public function testEmptyChunk()
    {
        $dataQuery = new DataListQueryCounter(Team::class);
        $this->chunkTester(
            [],
            Team::get()->setDataQuery($dataQuery)->filter('ClassName', 'non-sense')->chunkedFetch(),
            $dataQuery,
            1
        );
    }

    public function testInvalidChunkSize()
    {
        $this->expectException(InvalidArgumentException::class);
        foreach (Team::get()->chunkedFetch(0) as $item) {
            // You don't get the error until you iterate over the list
        };
    }

    /**
     * Loop over a chunk list and make sure it matches our expected results
     * @param int[] $expectedIDs
     * @param iterable $chunkList
     */
    private function chunkTester(
        array $expectedIDs,
        iterable $chunkList,
        DataListQueryCounter $dataQuery,
        int $expectedQueryCount
    ) {
        foreach ($chunkList as $chunkedTeam) {
            $this->assertInstanceOf(
                Team::class,
                $chunkedTeam,
                'Chunk return the correct type of data object'
            );

            $expectedID = array_shift($expectedIDs);

            $this->assertEquals(
                $expectedID,
                $chunkedTeam->ID,
                'chunk returns the same results in the same order as the regular iterator'
            );
        }

        $this->assertEmpty($expectedIDs, 'chunk returns all the results that the regular iterator does');
        $this->assertEquals($expectedQueryCount, $dataQuery->getCount());
    }
}
