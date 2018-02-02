<?php

namespace SilverStripe\Control\Tests;

use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\HTTPRequest;

/**
 * Tests to cover the {@link Session} class
 */
class SessionTest extends SapphireTest
{
    /**
     * @var Session
     */
    protected $session = null;

    protected function setUp()
    {
        $this->session = new Session([]);
        return parent::setUp();
    }

    public function testGetSetBasics()
    {
        $this->session->set('Test', 'Test');

        $this->assertEquals($this->session->get('Test'), 'Test');
    }

    public function testClearElement()
    {
        $this->session->set('Test', 'Test');
        $this->session->clear('Test');

        $this->assertEquals($this->session->get('Test'), '');
    }

    public function testClearAllElements()
    {
        $this->session->set('Test', 'Test');
        $this->session->set('Test-1', 'Test-1');

        $this->session->clearAll();

        // should session get return null? The array key should probably be
        // unset from the data array
        $this->assertEquals($this->session->get('Test'), '');
        $this->assertEquals($this->session->get('Test-1'), '');
    }

    public function testGetAllElements()
    {
        $this->session->clearAll(); // Remove all session that might've been set by the test harness

        $this->session->set('Test', 'Test');
        $this->session->set('Test-2', 'Test-2');

        $session = $this->session->getAll();
        unset($session['HTTP_USER_AGENT']);

        $this->assertEquals($session, array('Test' => 'Test', 'Test-2' => 'Test-2'));
    }

    public function testSettingExistingDoesntClear()
    {
        $s = new Session(array('something' => array('does' => 'exist')));

        $s->set('something.does', 'exist');
        $result = $s->changedData();
        unset($result['HTTP_USER_AGENT']);
        $this->assertEquals(array(), $result);
    }

    /**
     * Check that changedData isn't populated with junk when clearing non-existent entries.
     */
    public function testClearElementThatDoesntExist()
    {
        $s = new Session(['something' => ['does' => 'exist']]);
        $s->clear('something.doesnt.exist');

        // Clear without existing data
        $data = $s->get('something.doesnt.exist');
        $this->assertEquals(array(), $s->changedData());
        $this->assertNull($data);

        // Clear with existing change
        $s->set('something-else', 'val');
        $s->clear('something-new');
        $data = $s->get('something-else');
        $this->assertEquals(['something-else' => true], $s->changedData());
        $this->assertEquals('val', $data);
    }

    /**
     * Check that changedData is populated with clearing data.
     */
    public function testClearElementThatDoesExist()
    {
        $s = new Session(['something' => ['does' => 'exist']]);

        // Ensure keys are properly removed and not simply nullified
        $s->clear('something.does');
        $this->assertEquals(
            ['something' => ['does' => true]],
            $s->changedData()
        );
        $this->assertEquals(
            [], // 'does' removed
            $s->get('something')
        );

        // Clear at more specific level should also clear other changes
        $s->clear('something');
        $this->assertEquals(
            ['something' => true],
            $s->changedData()
        );
        $this->assertEquals(
            null, // Should be removed not just empty array
            $s->get('something')
        );
    }

    public function testUserAgentLockout()
    {
        // Set a user agent
        $req1 = new HTTPRequest('GET', '/');
        $req1->addHeader('User-Agent', 'Test Agent');

        // Generate our session
        $s = new Session(array());
        $s->init($req1);
        $s->set('val', 123);
        $s->finalize($req1);

        // Change our UA
        $req2 = new HTTPRequest('GET', '/');
        $req2->addHeader('User-Agent', 'Fake Agent');

        // Verify the new session reset our values
        $s2 = new Session($s);
        $s2->init($req2);
        $this->assertNotEquals($s2->get('val'), 123);
    }

    public function testSave()
    {
        $request = new HTTPRequest('GET', '/');

        // Test change of nested array type
        $s = new Session($_SESSION = ['something' => ['some' => 'value', 'another' => 'item']]);
        $s->set('something', 'string');
        $s->save($request);
        $this->assertEquals(
            ['something' => 'string'],
            $_SESSION
        );

        // Test multiple changes combine safely
        $s = new Session($_SESSION = ['something' => ['some' => 'value', 'another' => 'item']]);
        $s->set('something.another', 'newanother');
        $s->clear('something.some');
        $s->set('something.newkey', 'new value');
        $s->save($request);
        $this->assertEquals(
            [
                'something' => [
                    'another' => 'newanother',
                    'newkey' => 'new value',
                ]
            ],
            $_SESSION
        );

        // Test cleared keys are restorable
        $s = new Session($_SESSION = ['bookmarks' => [ 1 => 1, 2 => 2]]);
        $s->clear('bookmarks');
        $s->set('bookmarks', [
            1 => 1,
            3 => 3,
        ]);
        $s->save($request);
        $this->assertEquals(
            [
                'bookmarks' => [
                    1 => 1,
                    3 => 3,
                ]
            ],
            $_SESSION
        );
    }
}
