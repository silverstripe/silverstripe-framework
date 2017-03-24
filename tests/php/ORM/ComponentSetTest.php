<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;

class ComponentSetTest extends SapphireTest
{

    protected static $fixture_file = 'ComponentSetTest.yml';

    protected static $extra_dataobjects = array(
        ComponentSetTest\Player::class,
        ComponentSetTest\Team::class,
    );

    public function testSetByIDListManyMany()
    {
        $team1 = $this->objFromFixture(ComponentSetTest\Team::class, 'team1');
        $player1_team1 = $this->objFromFixture(ComponentSetTest\Player::class, 'player1_team1');
        $player2 = $this->objFromFixture(ComponentSetTest\Player::class, 'player2');

        $team1->Players()->setByIdList(
            array(
            $player1_team1->ID,
            $player2->ID
            )
        );
        $team1->flushCache();
        $this->assertContains(
            $player2->ID,
            $team1->Players()->column('ID'),
            'Can add new entry'
        );
        $this->assertContains(
            $player1_team1->ID,
            $team1->Players()->column('ID'),
            'Can retain existing entry'
        );

        $team1->Players()->setByIdList(
            array(
            $player1_team1->ID
            )
        );
        $team1->flushCache();
        $this->assertNotContains(
            $player2->ID,
            $team1->Players()->column('ID'),
            'Can remove existing entry'
        );
        $this->assertContains(
            $player1_team1->ID,
            $team1->Players()->column('ID'),
            'Can retain existing entry'
        );

        $team1->Players()->setByIdList(array());
        $team1->flushCache();
        $this->assertEquals(
            0,
            $team1->Players()->Count(),
            'Can remove all entries by passing an empty array'
        );
    }
}
