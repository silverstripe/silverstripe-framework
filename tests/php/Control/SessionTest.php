<?php

namespace SilverStripe\Control\Tests;

use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

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
        $s = new Session(array('something' => array('does' => 'exist')));

        $s->clear('something.doesnt.exist');
        $result = $s->changedData();
        unset($result['HTTP_USER_AGENT']);
        $this->assertEquals(array(), $result);

        $s->set('something-else', 'val');
        $s->clear('something-new');
        $result = $s->changedData();
        unset($result['HTTP_USER_AGENT']);
        $this->assertEquals(array('something-else' => 'val'), $result);
    }

    /**
     * Check that changedData is populated with clearing data.
     */
    public function testClearElementThatDoesExist()
    {
        $s = new Session(array('something' => array('does' => 'exist')));

        $s->clear('something.does');
        $result = $s->changedData();
        unset($result['HTTP_USER_AGENT']);
        $this->assertEquals(array('something' => array('does' => null)), $result);
    }

    public function testNonStandardPath()
    {
        Session::config()->set('store_path', (realpath(dirname($_SERVER['DOCUMENT_ROOT']) . '/../session')));
        $this->session->start();

        $this->assertEquals(Config::inst()->get('SilverStripe\\Control\\Session', 'store_path'), '');
    }

    public function testUserAgentLockout()
    {
        // Set a user agent
        $_SERVER['HTTP_USER_AGENT'] = 'Test Agent';

        // Generate our session
        $s = new Session(array());
        $s->set('val', 123);
        $s->finalize();

        // Change our UA
        $_SERVER['HTTP_USER_AGENT'] = 'Fake Agent';

        // Verify the new session reset our values
        $s2 = new Session($s);
        $this->assertNotEquals($s2->get('val'), 123);
    }
}
