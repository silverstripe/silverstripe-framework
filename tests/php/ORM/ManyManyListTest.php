<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\FieldType\DBMoney;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Tests\DataObjectTest\Player;
use SilverStripe\ORM\Tests\DataObjectTest\Team;
use SilverStripe\ORM\Tests\ManyManyListTest\ExtraFieldsObject;
use SilverStripe\ORM\Tests\ManyManyListTest\Product;

class ManyManyListTest extends SapphireTest
{

    protected static $fixture_file = 'DataObjectTest.yml';

    public static $extra_data_objects = [
        ManyManyListTest\Category::class,
        ManyManyListTest\ExtraFieldsObject::class,
        ManyManyListTest\Product::class,
    ];

    public static function getExtraDataObjects()
    {
        return array_merge(
            DataObjectTest::$extra_data_objects,
            ManyManyListTest::$extra_data_objects
        );
    }

    public function testAddCompositedExtraFields()
    {
        $obj = new ManyManyListTest\ExtraFieldsObject();
        $obj->write();

        $money = new DBMoney();
        $money->setAmount(100);
        $money->setCurrency('USD');

        // the actual test is that this does not generate an error in the sql.
        $obj->Clients()->add(
            $obj,
            array(
            'Worth' => $money,
            'Reference' => 'Foo'
            )
        );

        $check = $obj->Clients()->First();

        $this->assertEquals('Foo', $check->Reference, 'Basic scalar fields should exist');
        $this->assertInstanceOf(DBMoney::class, $check->Worth, 'Composite fields should exist on the record');
        $this->assertEquals(100, $check->Worth->getAmount());
    }

    /**
     * This test targets a bug where appending many_many_extraFields to a query would
     * result in erroneous queries for sort orders that rely on _SortColumn0
     */
    public function testAddCompositedExtraFieldsWithSortColumn0()
    {
        $obj = new ExtraFieldsObject();
        $obj->write();

        $product = new Product();
        $product->Title = 'Test Product';
        $product->write();

        // the actual test is that this does not generate an error in the sql.
        $obj->Products()->add($product, array(
            'Reference' => 'Foo'
        ));

        $result = $obj->Products()->First();
        $this->assertEquals('Foo', $result->Reference, 'Basic scalar fields should exist');
        $this->assertEquals('Test Product', $result->Title);
    }

    public function testCreateList()
    {
        $list = ManyManyList::create(
            Team::class,
            'DataObjectTest_Team_Players',
            'DataObjectTest_TeamID',
            'DataObjectTest_PlayerID'
        );
        $this->assertEquals(2, $list->count());
    }


    public function testRelationshipEmptyOnNewRecords()
    {
        // Relies on the fact that (unrelated) teams exist in the fixture file already
        $newPlayer = new Player(); // many_many Teams
        $this->assertEquals(array(), $newPlayer->Teams()->column('ID'));
    }

    public function testAddingSingleDataObjectByReference()
    {
        $player1 = $this->objFromFixture(Player::class, 'player1');
        $team1 = $this->objFromFixture(Team::class, 'team1');
        $player1->Teams()->add($team1);
        $player1->flushCache();

        $compareTeams = new ManyManyList(
            Team::class,
            'DataObjectTest_Team_Players',
            'DataObjectTest_TeamID',
            'DataObjectTest_PlayerID'
        );
        $compareTeams = $compareTeams->forForeignID($player1->ID);
        $this->assertEquals(
            $player1->Teams()->column('ID'),
            $compareTeams->column('ID'),
            "Adding single record as DataObject to many_many"
        );
    }

    public function testRemovingSingleDataObjectByReference()
    {
        $player1 = $this->objFromFixture(Player::class, 'player1');
        $team1 = $this->objFromFixture(Team::class, 'team1');
        $player1->Teams()->remove($team1);
        $player1->flushCache();
        $compareTeams = new ManyManyList(
            Team::class,
            'DataObjectTest_Team_Players',
            'DataObjectTest_TeamID',
            'DataObjectTest_PlayerID'
        );
        $compareTeams = $compareTeams->forForeignID($player1->ID);
        $this->assertEquals(
            $player1->Teams()->column('ID'),
            $compareTeams->column('ID'),
            "Removing single record as DataObject from many_many"
        );
    }

    public function testAddingSingleDataObjectByID()
    {
        $player1 = $this->objFromFixture(Player::class, 'player1');
        $team1 = $this->objFromFixture(Team::class, 'team1');
        $player1->Teams()->add($team1->ID);
        $player1->flushCache();
        $compareTeams = new ManyManyList(
            Team::class,
            'DataObjectTest_Team_Players',
            'DataObjectTest_TeamID',
            'DataObjectTest_PlayerID'
        );
        $compareTeams = $compareTeams->forForeignID($player1->ID);
        $this->assertEquals(
            $player1->Teams()->column('ID'),
            $compareTeams->column('ID'),
            "Adding single record as ID to many_many"
        );
    }

    public function testRemoveByID()
    {
        $player1 = $this->objFromFixture(Player::class, 'player1');
        $team1 = $this->objFromFixture(Team::class, 'team1');
        $player1->Teams()->removeByID($team1->ID);
        $player1->flushCache();
        $compareTeams = new ManyManyList(
            Team::class,
            'DataObjectTest_Team_Players',
            'DataObjectTest_TeamID',
            'DataObjectTest_PlayerID'
        );
        $compareTeams = $compareTeams->forForeignID($player1->ID);
        $this->assertEquals(
            $player1->Teams()->column('ID'),
            $compareTeams->column('ID'),
            "Removing single record as ID from many_many"
        );
    }

    public function testSetByIdList()
    {
        $player1 = $this->objFromFixture(Player::class, 'player1');
        $team1 = $this->objFromFixture(Team::class, 'team1');
        $team2 = $this->objFromFixture(Team::class, 'team2');
        $player1->Teams()->setByIdList(array($team1->ID, $team2->ID));
        $this->assertEquals(array($team1->ID, $team2->ID), $player1->Teams()->sort('Title')->column());
        $player1->Teams()->setByIdList(array($team1->ID));
        $this->assertEquals(array($team1->ID), $player1->Teams()->sort('Title')->column());
        $player1->Teams()->setByIdList(array($team2->ID));
        $this->assertEquals(array($team2->ID), $player1->Teams()->sort('Title')->column());
    }

    public function testAddingWithMultipleForeignKeys()
    {
        $newPlayer = new Player();
        $newPlayer->write();
        $team1 = $this->objFromFixture(Team::class, 'team1');
        $team2 = $this->objFromFixture(Team::class, 'team2');

        $playersTeam1Team2 = Team::get()->relation('Players')
            ->forForeignID(array($team1->ID, $team2->ID));
        $playersTeam1Team2->add($newPlayer);
        $this->assertEquals(
            array($team1->ID, $team2->ID),
            $newPlayer->Teams()->sort('Title')->column('ID')
        );
    }

    public function testAddingExistingDoesntRemoveExtraFields()
    {
        $player = new Player();
        $player->write();
        $team1 = $this->objFromFixture(Team::class, 'team1');

        $team1->Players()->add($player, array('Position' => 'Captain'));
        $this->assertEquals(
            array('Position' => 'Captain'),
            $team1->Players()->getExtraData('Teams', $player->ID),
            'Writes extrafields'
        );

        $team1->Players()->add($player);
        $this->assertEquals(
            array('Position' => 'Captain'),
            $team1->Players()->getExtraData('Teams', $player->ID),
            'Retains extrafields on subsequent adds with NULL fields'
        );

        $team1->Players()->add($player, array('Position' => 'Defense'));
        $this->assertEquals(
            array('Position' => 'Defense'),
            $team1->Players()->getExtraData('Teams', $player->ID),
            'Updates extrafields on subsequent adds with fields'
        );

        $team1->Players()->add($player, array('Position' => null));
        $this->assertEquals(
            array('Position' => null),
            $team1->Players()->getExtraData('Teams', $player->ID),
            'Allows clearing of extrafields on subsequent adds'
        );
    }

    public function testSubtractOnAManyManyList()
    {
        $allList = ManyManyList::create(
            Player::class,
            'DataObjectTest_Team_Players',
            'DataObjectTest_PlayerID',
            'DataObjectTest_TeamID'
        );
        $this->assertEquals(
            3,
            $allList->count(),
            'Precondition; we have all 3 players connected to a team in the list'
        );

        $teamOneID = $this->idFromFixture(Team::class, 'team1');
        $teamTwoID = $this->idFromFixture(Team::class, 'team2');

        // Captain 1 belongs to one team; team1
        $captain1 = $this->objFromFixture(Player::class, 'captain1');
        $this->assertEquals(
            array($teamOneID),
            $captain1->Teams()->column("ID"),
            'Precondition; player2 belongs to team1'
        );

        // Player 2 belongs to both teams: team1, team2
        $player2 = $this->objFromFixture(Player::class, 'player2');
        $this->assertEquals(
            array($teamOneID,$teamTwoID),
            $player2->Teams()->sort('Title')->column('ID'),
            'Precondition; player2 belongs to team1 and team2'
        );

        // We want to find the teams for player2 where the captain does not belong to
        $teamsWithoutTheCaptain = $player2->Teams()->subtract($captain1->Teams());

        // Assertions
        $this->assertEquals(
            1,
            $teamsWithoutTheCaptain->count(),
            'The ManyManyList should onlu contain one team'
        );
        $this->assertEquals(
            $teamTwoID,
            $teamsWithoutTheCaptain->first()->ID,
            'The ManyManyList contains the wrong team'
        );
    }

    public function testRemoveAll()
    {
        $first = new Team();
        $first->write();

        $second = new Team();
        $second->write();

        $firstPlayers = $first->Players();
        $secondPlayers = $second->Players();

        $a = new Player();
        $a->ShirtNumber = 'a';
        $a->write();

        $b = new Player();
        $b->ShirtNumber = 'b';
        $b->write();

        $firstPlayers->add($a);
        $firstPlayers->add($b);

        $secondPlayers->add($a);
        $secondPlayers->add($b);

        $this->assertEquals(array('a', 'b'), $firstPlayers->sort('ShirtNumber')->column('ShirtNumber'));
        $this->assertEquals(array('a', 'b'), $secondPlayers->sort('ShirtNumber')->column('ShirtNumber'));

        $firstPlayers->removeAll();

        $this->assertEquals(0, count($firstPlayers));
        $this->assertEquals(2, count($secondPlayers));

        $firstPlayers->removeAll();

        $firstPlayers->add($a);
        $firstPlayers->add($b);

        $this->assertEquals(array('a', 'b'), $firstPlayers->sort('ShirtNumber')->column('ShirtNumber'));

        $firstPlayers->filter('ShirtNumber', 'b')->removeAll();

        $this->assertEquals(array('a'), $firstPlayers->column('ShirtNumber'));
        $this->assertEquals(array('a', 'b'), $secondPlayers->sort('ShirtNumber')->column('ShirtNumber'));

        $this->assertNotNull(Player::get()->byID($a->ID));
        $this->assertNotNull(Player::get()->byID($b->ID));
    }

    public function testAppendExtraFieldsToQuery()
    {
        $list = new ManyManyList(
            ManyManyListTest\ExtraFieldsObject::class,
            'ManyManyListTest_ExtraFields_Clients',
            'ManyManyListTest_ExtraFieldsID',
            'ChildID',
            array(
                'Worth' => 'Money',
                'Reference' => 'Varchar'
            )
        );

        // ensure that ManyManyListTest_ExtraFields_Clients.ValueCurrency is
        // selected.
        $expected = 'SELECT DISTINCT "ManyManyListTest_ExtraFields_Clients"."WorthCurrency",'
            . ' "ManyManyListTest_ExtraFields_Clients"."WorthAmount", "ManyManyListTest_ExtraFields_Clients"."Reference",'
            . ' "ManyManyListTest_ExtraFields"."ClassName", "ManyManyListTest_ExtraFields"."LastEdited",'
            . ' "ManyManyListTest_ExtraFields"."Created", "ManyManyListTest_ExtraFields"."ID",'
            . ' CASE WHEN "ManyManyListTest_ExtraFields"."ClassName" IS NOT NULL THEN'
            . ' "ManyManyListTest_ExtraFields"."ClassName" ELSE ' . Convert::raw2sql(ManyManyListTest\ExtraFieldsObject::class, true)
            . ' END AS "RecordClassName" FROM "ManyManyListTest_ExtraFields" INNER JOIN'
            . ' "ManyManyListTest_ExtraFields_Clients" ON'
            . ' "ManyManyListTest_ExtraFields_Clients"."ManyManyListTest_ExtraFieldsID" ='
            . ' "ManyManyListTest_ExtraFields"."ID"';

        $this->assertSQLEquals($expected, $list->sql($parameters));
    }

    public function testFilteringOnPreviouslyJoinedTable()
    {
        /** @var ManyManyListTest\Category $category */
        $category = $this->objFromFixture(ManyManyListTest\Category::class, 'categorya');

        /** @var ManyManyList $productsRelatedToProductB */
        $productsRelatedToProductB = $category->Products()->filter('RelatedProducts.Title', 'Product A');
        $this->assertEquals(1, $productsRelatedToProductB->count());
    }
}
