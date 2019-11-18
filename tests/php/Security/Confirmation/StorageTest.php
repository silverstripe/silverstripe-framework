<?php

namespace SilverStripe\Security\Tests\Confirmation;

use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Confirmation\Storage;
use SilverStripe\Security\Confirmation\Item;
use SilverStripe\Control\Tests\HttpRequestMockBuilder;

class StorageTest extends SapphireTest
{
    use HttpRequestMockBuilder;

    private function getNamespace($id)
    {
        return str_replace('\\', '.', Storage::class) . '.' . $id;
    }

    public function testNewStorage()
    {
        $session = $this->createMock(Session::class);
        $sessionCleaned = false;
        $session->method('clear')->will($this->returnCallback(static function ($namespace) use (&$sessionCleaned) {
            $sessionCleaned = $namespace;
        }));

        $storage = new Storage($session, 'test');

        $this->assertEquals(
            $this->getNamespace('test'),
            $sessionCleaned,
            'Session data should have been cleaned from the obsolete data'
        );

        $sessionCleaned = false;
        $storage = new Storage($session, 'test', false);
        $this->assertFalse($sessionCleaned, 'Session data should have been preserved');
    }

    public function testCleanup()
    {
        $session = $this->createMock(Session::class);
        $sessionCleaned = false;
        $session->method('clear')->will($this->returnCallback(static function ($namespace) use (&$sessionCleaned) {
            $sessionCleaned = $namespace;
        }));

        $storage = new Storage($session, 'test', false);

        $this->assertFalse($sessionCleaned, 'Session data should have been preserved');

        $storage->cleanup();
        $this->assertEquals(
            $this->getNamespace('test'),
            $sessionCleaned,
            'Session data should have been cleaned up'
        );
    }

    public function testSuccessRequest()
    {
        $session = new Session([]);
        $storage = new Storage($session, 'test');

        $request = $this->buildRequestMock('dev/build', ['flush' => 'all']);

        $storage->setSuccessRequest($request);

        // ensure the data is persisted within the session
        $storage = new Storage($session, 'test', false);
        $this->assertEquals('/dev/build?flush=all', $storage->getSuccessUrl());
        $this->assertEquals('GET', $storage->getHttpMethod());
    }

    public function testPutItem()
    {
        $session = new Session([]);
        $storage = new Storage($session, 'test');

        $item1 = new Item('item1_token', 'item1_name', 'item1_desc');
        $item2 = new Item('item2_token', 'item2_name', 'item2_desc');

        $storage->putItem($item1);
        $storage->putItem($item2);

        // ensure the data is persisted within the session
        $storage = new Storage($session, 'test', false);

        $items = $storage->getItems();
        $hashedItems = $storage->getHashedItems();

        $this->assertCount(2, $items);
        $this->assertCount(2, $hashedItems);

        $item1Hash = $storage->getTokenHash($item1);
        $this->assertArrayHasKey($item1Hash, $items);

        $item = $items[$item1Hash];

        $this->assertEquals('item1_token', $item->getToken());
        $this->assertEquals('item1_name', $item->getName());
        $this->assertEquals('item1_desc', $item->getDescription());
        $this->assertFalse($item->isConfirmed());

        $item2Hash = $storage->getTokenHash($item2);
        $this->assertArrayHasKey($item2Hash, $items);

        $item = $items[$item2Hash];

        $this->assertEquals('item2_token', $item->getToken());
        $this->assertEquals('item2_name', $item->getName());
        $this->assertEquals('item2_desc', $item->getDescription());
        $this->assertFalse($item->isConfirmed());
    }

    public function testConfirmation()
    {
        $session = new Session([]);
        $storage = new Storage($session, 'test');

        $item1 = new Item('item1_token', 'item1_name', 'item1_desc');
        $item2 = new Item('item2_token', 'item2_name', 'item2_desc');

        $storage->putItem($item1);
        $storage->putItem($item2);

        // ensure the data is persisted within the session
        $storage = new Storage($session, 'test', false);

        foreach ($storage->getItems() as $item) {
            $this->assertFalse($item->isConfirmed());
        }
        $this->assertFalse($storage->check([$item1, $item2]));

        // check we cannot confirm items with incorrect data
        $storage->confirm([]);
        foreach ($storage->getItems() as $item) {
            $this->assertFalse($item->isConfirmed());
        }
        $this->assertFalse($storage->check([$item1, $item2]));

        // check we cannot confirm items with unsalted tokens
        $storage->confirm(['item1_token' => '1', 'item2_token' => '1']);
        foreach ($storage->getItems() as $item) {
            $this->assertFalse($item->isConfirmed());
        }
        $this->assertFalse($storage->check([$item1, $item2]));

        // check we can confirm data with properly salted tokens
        $storage->confirm($storage->getHashedItems());
        foreach ($storage->getItems() as $item) {
            $this->assertTrue($item->isConfirmed());
        }
        $this->assertTrue($storage->check([$item1, $item2]));
    }
}
