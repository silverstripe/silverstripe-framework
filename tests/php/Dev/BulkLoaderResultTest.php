<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\BulkLoader_Result;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\Tests\BulkLoaderResultTest\Player;

class BulkLoaderResultTest extends SapphireTest
{

    protected static $extra_dataobjects = [
        Player::class,
    ];

    protected function setUp()
    {
        parent::setUp();
        Player::create(array('Name' => 'Vincent', 'Status' => 'Available'))->write();
    }

    public function testBulkLoaderResultCreated()
    {
        $results = BulkLoader_Result::create();
        $player = Player::create(array('Name' => 'Rangi', 'Status' => 'Possible'));
        $player->write();
        $results->addCreated($player, 'Speedster');

        $this->assertEquals($results->CreatedCount(), 1);
        $this->assertSame(
            'Rangi',
            $results->Created()->find('Name', 'Rangi')->Name,
            'The player Rangi should be recorded as created in $results'
        );
        $this->assertSame(
            'Possible',
            $results->Created()->find('Name', 'Rangi')->Status,
            'The player Rangi should have Status of "Possible" in $results'
        );
        $this->assertSame(
            'Speedster',
            $results->Created()->find('Name', 'Rangi')->_BulkLoaderMessage,
            'Rangi should have _BulkLoaderMessage of Speedster'
        );
    }

    public function testBulkLoaderResultDeleted()
    {
        $results = BulkLoader_Result::create();
        $player = Player::get()->find('Name', 'Vincent');
        $results->addDeleted($player, 'Retired');
        $player->delete();

        $this->assertEquals($results->DeletedCount(), 1);
        $this->assertSame(
            'Vincent',
            $results->Deleted()->find('Name', 'Vincent')->Name,
            'The player Vincent should be recorded as deleted'
        );
        $this->assertSame(
            'Retired',
            $results->Deleted()->find('Name', 'Vincent')->_BulkLoaderMessage,
            'Vincent should have a _BulkLoaderMessage of Retired'
        );
    }

    public function testBulkLoaderResultUpdated()
    {
        $results = BulkLoader_Result::create();
        $player = Player::get()->find('Name', 'Vincent');
        $player->Status = 'Unavailable';
        $player->write();
        $results->addUpdated($player, 'Injured');

        $this->assertEquals($results->UpdatedCount(), 1);
        $this->assertSame(
            'Vincent',
            $results->Updated()->find('Name', 'Vincent')->Name,
            'The player Vincent should be recorded as updated'
        );
        $this->assertSame(
            'Unavailable',
            $results->Updated()->find('Name', 'Vincent')->Status,
            'The player Vincent should have a Status of Unavailable'
        );
        $this->assertSame(
            'Injured',
            $results->Updated()->find('Name', 'Vincent')->_BulkLoaderMessage,
            'Vincent is injured'
        );
    }
}
