<?php

namespace SilverStripe\Control\Tests;

use http\Exception\BadMessageException;
use SilverStripe\Control\Cookie;
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

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInitDoesNotStartSessionWithoutIdentifier()
    {
        $req = new HTTPRequest('GET', '/');
        $session = new Session(null); // unstarted session
        $session->init($req);
        $this->assertFalse($session->isStarted());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInitStartsSessionWithIdentifier()
    {
        $req = new HTTPRequest('GET', '/');
        Cookie::set(session_name(), '1234');
        $session = new Session(null); // unstarted session
        $session->init($req);
        $this->assertTrue($session->isStarted());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInitStartsSessionWithData()
    {
        $req = new HTTPRequest('GET', '/');
        $session = new Session([]);
        $session->init($req);
        $this->assertTrue($session->isStarted());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testStartUsesDefaultCookieNameWithHttp()
    {
        $req = (new HTTPRequest('GET', '/'))
            ->setScheme('http');
        Cookie::set(session_name(), '1234');
        $session = new Session(null); // unstarted session
        $session->start($req);
        $this->assertNotEquals(session_name(), $session->config()->get('cookie_name_secure'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testStartUsesDefaultCookieNameWithHttpsAndCookieSecureOff()
    {
        $req = (new HTTPRequest('GET', '/'))
            ->setScheme('https');
        Cookie::set(session_name(), '1234');
        $session = new Session(null); // unstarted session
        $session->start($req);
        $this->assertNotEquals(session_name(), $session->config()->get('cookie_name_secure'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testStartUsesSecureCookieNameWithHttpsAndCookieSecureOn()
    {
        $req = (new HTTPRequest('GET', '/'))
            ->setScheme('https');
        Cookie::set(session_name(), '1234');
        $session = new Session(null); // unstarted session
        $session->config()->update('cookie_secure', true);
        $session->start($req);
        $this->assertEquals(session_name(), $session->config()->get('cookie_name_secure'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage Session has already started
     */
    public function testStartErrorsWhenStartingTwice()
    {
        $req = new HTTPRequest('GET', '/');
        $session = new Session(null); // unstarted session
        $session->start($req);
        $session->start($req);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testStartRetainsInMemoryData()
    {
        $this->markTestIncomplete('Test');
        // TODO Figure out how to simulate session vars without a session_start() resetting them
        // $_SESSION['existing'] = true;
        // $_SESSION['merge'] = 1;
        $req = new HTTPRequest('GET', '/');
        $session = new Session(null); // unstarted session
        $session->set('new', true);
        $session->set('merge', 2);
        $session->start($req); // simulate lazy start
        $this->assertEquals(
            [
                // 'existing' => true,
                'new' => true,
                'merge' => 2
            ],
            $session->getAll()
        );

        unset($_SESSION);
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

    public function testRequestContainsSessionId()
    {
        $req = new HTTPRequest('GET', '/');
        $session = new Session(null); // unstarted session
        $this->assertFalse($session->requestContainsSessionId($req));
        Cookie::set(session_name(), '1234');
        $this->assertTrue($session->requestContainsSessionId($req));
    }

    public function testRequestContainsSessionIdRespectsCookieNameSecure()
    {
        $req = (new HTTPRequest('GET', '/'))
            ->setScheme('https');
        $session = new Session(null); // unstarted session
        Cookie::set($session->config()->get('cookie_name_secure'), '1234');
        $session->config()->update('cookie_secure', true);
        $this->assertTrue($session->requestContainsSessionId($req));
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

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSessionOutput()
    {
        if (!function_exists('xdebug_get_headers')) {
            $this->markTestSkipped("This test requires the xdebug_get_headers function");
        }

        $req = new HTTPRequest('GET', '/');

        // fresh session
        $session = new Session(null);
        $session->init($req);

        // save a value to session
        $session->set('val', 'my value');

        // Session save should emit a Set-Cookie header as part of session_start.
        $session->save($req);

        $headers = xdebug_get_headers();
        $sessionHeaderCount = array_reduce($headers, function ($carry, $header) {
            $carry += (int)preg_match("/Set-Cookie: " . session_name() . "/", $header);
            return $carry;
        });

        $this->assertEquals(1, $sessionHeaderCount);
    }
}
