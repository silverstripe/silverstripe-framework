<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Tests\DataObjectTest\Fan;
use SilverStripe\ORM\Tests\DataObjectTest\Team;

/**
 * Tests the PolymorphicHasManyList class
 *
 * @see PolymorphicHasManyList
 *
 * @todo Complete
 */
class PolymorphicHasManyListTest extends SapphireTest
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

    public function testRelationshipEmptyOnNewRecords()
    {
        // Relies on the fact that (unrelated) comments exist in the fixture file already
        $newTeam = new Team(); // has_many Comments
        $this->assertEquals(array(), $newTeam->Fans()->column('ID'));
    }

    /**
     * Test that DataList::relation works with PolymorphicHasManyList
     */
    public function testFilterRelation()
    {
        // Check that expected teams exist
        $list = Team::get();
        $this->assertEquals(
            array('Subteam 1', 'Subteam 2', 'Subteam 3', 'Team 1', 'Team 2', 'Team 3'),
            $list->sort('Title')->column('Title')
        );

        // Check that fan list exists
        $fans = $list->relation('Fans');
        $this->assertEquals(array('Damian', 'Mitch', 'Richard'), $fans->sort('Name')->column('Name'));

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
        $this->assertEquals(array('Bobby', 'Damian', 'Richard'), $team1->Fans()->sort('Name')->column('Name'));
        $this->assertEquals(array('Mindy', 'Mitch'), $subteam1->Fans()->sort('Name')->column('Name'));
        $this->assertEquals(array('Bobby', 'Damian', 'Mindy', 'Mitch', 'Richard'), $fans->sort('Name')->column('Name'));
    }

    /**
     * Test that related objects can be removed from a relation
     */
    public function testRemoveRelation()
    {

        // Check that expected teams exist
        $list = Team::get();
        $this->assertEquals(
            array('Subteam 1', 'Subteam 2', 'Subteam 3', 'Team 1', 'Team 2', 'Team 3'),
            $list->sort('Title')->column('Title')
        );

        // Test that each team has the correct fans
        $team1 = $this->objFromFixture(DataObjectTest\Team::class, 'team1');
        $subteam1 = $this->objFromFixture(DataObjectTest\SubTeam::class, 'subteam1');
        $this->assertEquals(array('Damian', 'Richard'), $team1->Fans()->sort('Name')->column('Name'));
        $this->assertEquals(array('Mitch'), $subteam1->Fans()->sort('Name')->column('Name'));

        // Test that removing items from unrelated team has no effect
        $team1fan = $this->objFromFixture(DataObjectTest\Fan::class, 'fan1');
        $subteam1fan = $this->objFromFixture(DataObjectTest\Fan::class, 'fan4');
        $team1->Fans()->remove($subteam1fan);
        $subteam1->Fans()->remove($team1fan);
        $this->assertEquals(array('Damian', 'Richard'), $team1->Fans()->sort('Name')->column('Name'));
        $this->assertEquals(array('Mitch'), $subteam1->Fans()->sort('Name')->column('Name'));
        $this->assertEquals($team1->ID, $team1fan->FavouriteID);
        $this->assertEquals(DataObjectTest\Team::class, $team1fan->FavouriteClass);
        $this->assertEquals($subteam1->ID, $subteam1fan->FavouriteID);
        $this->assertEquals(DataObjectTest\SubTeam::class, $subteam1fan->FavouriteClass);

        // Test that removing items from the related team resets the has_one relations on the fan
        $team1fan = $this->objFromFixture(DataObjectTest\Fan::class, 'fan1');
        $subteam1fan = $this->objFromFixture(DataObjectTest\Fan::class, 'fan4');
        $team1->Fans()->remove($team1fan);
        $subteam1->Fans()->remove($subteam1fan);
        $this->assertEquals(array('Richard'), $team1->Fans()->sort('Name')->column('Name'));
        $this->assertEquals(array(), $subteam1->Fans()->sort('Name')->column('Name'));
        $this->assertEmpty($team1fan->FavouriteID);
        $this->assertEmpty($team1fan->FavouriteClass);
        $this->assertEmpty($subteam1fan->FavouriteID);
        $this->assertEmpty($subteam1fan->FavouriteClass);
    }
}
