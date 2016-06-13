<?php
/**
 * @package framework
 * @subpackage tests
 */
class BulkLoaderResultTest extends SapphireTest
{
    protected $extraDataObjects = array('BulkLoaderTestPlayer');

    public function setUp()
    {
        parent::setUp();
        BulkLoaderTestPlayer::create(array('Name' => 'Vincent', 'Status' => 'Available'))->write();
    }

    public function testBulkLoaderResultCreated()
    {
        $results = BulkLoader_Result::create();
        $player = BulkLoaderTestPlayer::create(array('Name' => 'Rangi', 'Status' => 'Possible'));
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
        $player = BulkLoaderTestPlayer::get()->find('Name', 'Vincent');
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
        $player = BulkLoaderTestPlayer::get()->find('Name', 'Vincent');
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

class BulkLoaderTestPlayer extends DataObject implements TestOnly
{
    private static $db = array(
        'Name' => 'Varchar',
        'Status' => 'Varchar',
    );
}
