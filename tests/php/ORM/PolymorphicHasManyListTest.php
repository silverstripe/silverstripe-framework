<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Tests\DataObjectTest\Fan;
use SilverStripe\ORM\Tests\DataObjectTest\Team;

/**
 * Tests the PolymorphicHasManyList class
 *
 * @see PolymorphicHasManyList
 */
class PolymorphicHasManyListTest extends SapphireTest
{

    // Borrow the model from DataObjectTest
    protected static $fixture_file = [
        'DataObjectTest.yml',
        'PolymorphicHasManyListTest.yml',
    ];

    public static function getExtraDataObjects()
    {
        return array_merge(
            DataObjectTest::$extra_data_objects,
            ManyManyListTest::$extra_data_objects
        );
    }

    public function testRelationshipEmptyOnNewRecords()
    {
        // Relies on the fact that (unrelated) comments exist in the fixture file already
        $newTeam = new Team(); // has_many Comments
        $this->assertEquals([], $newTeam->Fans()->column('ID'));
    }

    /**
     * Validates that multiple has_many relations can point to a single multi-relational
     * has_one relation and still be separate
     */
    public function testMultiRelationalRelations(): void
    {
        $team = $this->objFromFixture(Team::class, 'multiRelationalTeam');
        $playersList1 = $team->ManyPlayers1();
        $playersList2 = $team->ManyPlayers2();

        // Lists are separate
        $this->assertSame(
            ['MultiRelational Player 1', 'MultiRelational Player 2', 'MultiRelational Player 6'],
            $playersList1->sort('FirstName')->column('FirstName')
        );
        $this->assertSame(
            ['MultiRelational Player 3', 'MultiRelational Player 4', 'MultiRelational Player 5'],
            $playersList2->sort('FirstName')->column('FirstName')
        );
        // The relation is saved on the has_one side of the relationship
        $this->assertSame('ManyPlayers1', $playersList1->first()->MultiRelationalRelation);
        $this->assertSame('ManyPlayers2', $playersList2->first()->MultiRelationalRelation);
    }

    /**
     * Test that DataList::relation works with PolymorphicHasManyList
     */
    public function testFilterRelation()
    {
        // Check that expected teams exist
        $list = Team::get();
        $this->assertEquals(
            ['MultiRelational team', 'Subteam 1', 'Subteam 2', 'Subteam 3', 'Team 1', 'Team 2', 'Team 3'],
            $list->sort('Title')->column('Title')
        );

        // Check that fan list exists
        $fans = $list->relation('Fans');
        $this->assertEquals(['Damian', 'Mitch', 'Richard'], $fans->sort('Name')->column('Name'));

        // Modify list of fans and retest
        $team1 = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $subteam1 = $this->objFromFixture(DataObjectTest\SubTeam::class, 'subteam1');
        $newFan1 = Fan::create();
        $newFan1->Name = 'Bobby';
        $newFan1->write();
        $newFan2 = Fan::create();
        $newFan2->Name = 'Mindy';
        $newFan2->write();
        $team1->Fans()->add($newFan1);
        $subteam1->Fans()->add($newFan2);
        $fans = Team::get()->relation('Fans');
        $this->assertEquals(['Bobby', 'Damian', 'Richard'], $team1->Fans()->sort('Name')->column('Name'));
        $this->assertEquals(['Mindy', 'Mitch'], $subteam1->Fans()->sort('Name')->column('Name'));
        $this->assertEquals(['Bobby', 'Damian', 'Mindy', 'Mitch', 'Richard'], $fans->sort('Name')->column('Name'));
    }

    /**
     * The same as testFilterRelation but for multi-relational relationships
     */
    public function testFilterMultiRelationalRelation(): void
    {
        $list = Team::get();

        $players1 = $list->relation('ManyPlayers1')->sort('FirstName')->column('FirstName');
        $players2 = $list->relation('ManyPlayers2')->sort('FirstName')->column('FirstName');
        // Test that each relation has the expected players
        $this->assertSame(
            ['MultiRelational Player 1', 'MultiRelational Player 2', 'MultiRelational Player 6'],
            $players1
        );
        $this->assertSame(
            ['MultiRelational Player 3', 'MultiRelational Player 4', 'MultiRelational Player 5'],
            $players2
        );

        // Modify list of fans
        $team = $this->objFromFixture(DataObjectTest\Team::class, 'multiRelationalTeam');
        $newPlayer1 = new DataObjectTest\Player(['FirstName' => 'New player 1']);
        $team->ManyPlayers1()->add($newPlayer1);
        $this->assertSame('ManyPlayers1', $newPlayer1->MultiRelationalRelation);
        $this->assertSame(Team::class, $newPlayer1->MultiRelationalClass);
        $this->assertSame($team->ID, $newPlayer1->MultiRelationalID);
        $newPlayer2 = new DataObjectTest\Player(['FirstName' => 'New player 2']);
        $team->ManyPlayers2()->add($newPlayer2);
        $this->assertSame('ManyPlayers2', $newPlayer2->MultiRelationalRelation);
        $this->assertSame(Team::class, $newPlayer2->MultiRelationalClass);
        $this->assertSame($team->ID, $newPlayer2->MultiRelationalID);

        // and retest
        $players1 = $list->relation('ManyPlayers1')->sort('FirstName')->column('FirstName');
        $players2 = $list->relation('ManyPlayers2')->sort('FirstName')->column('FirstName');
        $this->assertSame(
            ['MultiRelational Player 1', 'MultiRelational Player 2', 'MultiRelational Player 6', 'New player 1'],
            $players1
        );
        $this->assertSame(
            ['MultiRelational Player 3', 'MultiRelational Player 4', 'MultiRelational Player 5', 'New player 2'],
            $players2
        );
    }

    /**
     * Test that related objects can be removed from a relation
     */
    public function testRemoveRelation()
    {
        // Check that expected teams exist
        $list = Team::get();
        $this->assertEquals(
            ['MultiRelational team', 'Subteam 1', 'Subteam 2', 'Subteam 3', 'Team 1', 'Team 2', 'Team 3'],
            $list->sort('Title')->column('Title')
        );

        // Test that each team has the correct fans
        $team1 = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $subteam1 = $this->objFromFixture(DataObjectTest\SubTeam::class, 'subteam1');
        $this->assertEquals(['Damian', 'Richard'], $team1->Fans()->sort('Name')->column('Name'));
        $this->assertEquals(['Mitch'], $subteam1->Fans()->sort('Name')->column('Name'));

        // Test that removing items from unrelated team has no effect
        $team1fan = $this->objFromFixture(DataObjectTest\Fan::class, 'fan1');
        $subteam1fan = $this->objFromFixture(DataObjectTest\Fan::class, 'fan4');
        $team1->Fans()->remove($subteam1fan);
        $subteam1->Fans()->remove($team1fan);
        $this->assertEquals(['Damian', 'Richard'], $team1->Fans()->sort('Name')->column('Name'));
        $this->assertEquals(['Mitch'], $subteam1->Fans()->sort('Name')->column('Name'));
        $this->assertEquals($team1->ID, $team1fan->FavouriteID);
        $this->assertEquals(DataObjectTest\Team::class, $team1fan->FavouriteClass);
        $this->assertEquals($subteam1->ID, $subteam1fan->FavouriteID);
        $this->assertEquals(DataObjectTest\SubTeam::class, $subteam1fan->FavouriteClass);

        // Test that removing items from the related team resets the has_one relations on the fan
        $team1fan = $this->objFromFixture(DataObjectTest\Fan::class, 'fan1');
        $subteam1fan = $this->objFromFixture(DataObjectTest\Fan::class, 'fan4');
        $team1->Fans()->remove($team1fan);
        $subteam1->Fans()->remove($subteam1fan);
        $this->assertEquals(['Richard'], $team1->Fans()->sort('Name')->column('Name'));
        $this->assertEquals([], $subteam1->Fans()->sort('Name')->column('Name'));
        $this->assertEmpty($team1fan->FavouriteID);
        $this->assertEmpty($team1fan->FavouriteClass);
        $this->assertEmpty($subteam1fan->FavouriteID);
        $this->assertEmpty($subteam1fan->FavouriteClass);
    }

    /**
     * The same as testRemoveRelation but for multi-relational relationships
     */
    public function testRemoveMultiRelationalRelation(): void
    {
        $team = $this->objFromFixture(DataObjectTest\Team::class, 'multiRelationalTeam');
        $originalPlayers1 = $team->ManyPlayers1()->sort('FirstName')->column('FirstName');
        $originalPlayers2 = $team->ManyPlayers2()->sort('FirstName')->column('FirstName');

        // Test that each relation has the expected players as a baseline
        $this->assertSame(
            ['MultiRelational Player 1', 'MultiRelational Player 2', 'MultiRelational Player 6'],
            $originalPlayers1
        );
        $this->assertSame(
            ['MultiRelational Player 3', 'MultiRelational Player 4', 'MultiRelational Player 5'],
            $originalPlayers2
        );

        // Test that you can't remove items from relations they're not in
        $playerFromGroup1 = $this->objFromFixture(DataObjectTest\Player::class, 'multiRelationalPlayer2');
        $team->ManyPlayers2()->remove($playerFromGroup1);
        $this->assertSame($originalPlayers1, $team->ManyPlayers1()->sort('FirstName')->column('FirstName'));
        $this->assertSame($originalPlayers2, $team->ManyPlayers2()->sort('FirstName')->column('FirstName'));
        $this->assertSame('ManyPlayers1', $playerFromGroup1->MultiRelationalRelation);
        $this->assertSame(Team::class, $playerFromGroup1->MultiRelationalClass);
        $this->assertSame($team->ID, $playerFromGroup1->MultiRelationalID);

        // Test that you *can* remove items from relations they *are* in
        $team->ManyPlayers1()->remove($playerFromGroup1);
        $this->assertSame(
            ['MultiRelational Player 1', 'MultiRelational Player 6'],
            $team->ManyPlayers1()->sort('FirstName')->column('FirstName')
        );
        $this->assertSame($originalPlayers2, $team->ManyPlayers2()->sort('FirstName')->column('FirstName'));
        $this->assertEmpty($playerFromGroup1->MultiRelationalRelation);
        $this->assertEmpty($playerFromGroup1->MultiRelationalClass);
        $this->assertEmpty($playerFromGroup1->MultiRelationalID);
    }

    public function testGetForeignClassKey(): void
    {
        $team = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $list = $team->Fans();
        $this->assertSame('FavouriteClass', $list->getForeignClassKey());
    }

    public function getGetForeignRelationKey(): void
    {
        $team = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $list = $team->Fans();
        $this->assertSame('FavouriteRelation', $list->getForeignRelationKey());
    }
}
