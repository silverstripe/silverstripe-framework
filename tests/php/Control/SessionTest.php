<?php

namespace SilverStripe\Control\Tests;

use Exception;
use LogicException;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Director;
use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\NullHTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;

/**
 * Tests to cover the {@link Session} class
 */
class SessionTest extends SapphireTest
{
    /**
     * @var Session
     */
    protected $session = null;

    protected function setUp(): void
    {
        $this->session = new Session([]);
        parent::setUp();
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
     */
    public function testStartErrorsWhenStartingTwice()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Session has already started');
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
                'merge' => 2,
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

        $this->assertEquals($session, ['Test' => 'Test', 'Test-2' => 'Test-2']);
    }

    public function testSettingExistingDoesntClear()
    {
        $s = new Session(['something' => ['does' => 'exist']]);

        $s->set('something.does', 'exist');
        $result = $s->changedData();
        unset($result['HTTP_USER_AGENT']);
        $this->assertEmpty($result);
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
        $this->assertEmpty($s->changedData());
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
        $s = new Session([]);
        $s->init($req1);
        $s->set('val', 123);
        $s->finalize($req1);

        // Change our UA
        $req2 = new HTTPRequest('GET', '/');
        $req2->addHeader('User-Agent', 'Fake Agent');

        // Verify the new session reset our values
        $s2 = new Session($s);
        $s2->init($req2);
        $this->assertEmpty($s2->get('val'));
    }

    public function testDisabledUserAgentLockout()
    {
        Session::config()->set('strict_user_agent_check', false);

        // Set a user agent
        $req1 = new HTTPRequest('GET', '/');
        $req1->addHeader('User-Agent', 'Test Agent');

        // Generate our session
        $s = new Session([]);
        $s->init($req1);
        $s->set('val', 123);
        $s->finalize($req1);

        // Change our UA
        $req2 = new HTTPRequest('GET', '/');
        $req2->addHeader('User-Agent', 'Fake Agent');

        // Verify the new session reset our values
        $s2 = new Session($s);
        $s2->init($req2);
        $this->assertEquals($s2->get('val'), 123);
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
                ],
            ],
            $_SESSION
        );

        // Test cleared keys are restorable
        $s = new Session($_SESSION = ['bookmarks' => [1 => 1, 2 => 2]]);
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
                ],
            ],
            $_SESSION
        );
    }

    public function testIsCookieSecure(): void
    {
        $session = new Session(null);
        $methodIsCookieSecure = new ReflectionMethod($session, 'isCookieSecure');
        $methodIsCookieSecure->setAccessible(true);

        $this->assertFalse($methodIsCookieSecure->invoke($session, 'Lax', true));
        $this->assertFalse($methodIsCookieSecure->invoke($session, 'Lax', false));
        $this->assertTrue($methodIsCookieSecure->invoke($session, 'None', false));
        $this->assertTrue($methodIsCookieSecure->invoke($session, 'None', true));

        Config::modify()->set(Session::class, 'cookie_secure', true);
        $this->assertTrue($methodIsCookieSecure->invoke($session, 'Lax', true));
        $this->assertFalse($methodIsCookieSecure->invoke($session, 'Lax', false));
        $this->assertTrue($methodIsCookieSecure->invoke($session, 'None', false));
        $this->assertTrue($methodIsCookieSecure->invoke($session, 'None', true));
    }

    public function testBuildCookieParams(): void
    {
        $session = new Session(null);
        $methodBuildCookieParams = new ReflectionMethod($session, 'buildCookieParams');
        $methodBuildCookieParams->setAccessible(true);

        $params = $methodBuildCookieParams->invoke($session, new NullHTTPRequest());
        $this->assertSame(
            [
                'lifetime' => Session::config()->get('timeout'), // 0 by default but kitchen sink sets this to 1440
                'path' => '/',
                'domain' => null,
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax',
            ],
            $params
        );

        Config::modify()->set(Session::class, 'timeout', 123);
        Config::modify()->set(Session::class, 'cookie_path', 'test-path');
        Config::modify()->set(Session::class, 'cookie_domain', 'test-domain');
        $params = $methodBuildCookieParams->invoke($session, new NullHTTPRequest());
        $this->assertSame(
            [
                'lifetime' => 123,
                'path' => 'test-path',
                'domain' => 'test-domain',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax',
            ],
            $params
        );

        Config::modify()->set(Session::class, 'cookie_path', '');
        Config::modify()->set(Director::class, 'alternate_base_url', 'https://secure.example.com/some-path/');
        $params = $methodBuildCookieParams->invoke($session, new NullHTTPRequest());
        $this->assertSame(
            [
                'lifetime' => 123,
                'path' => '/some-path/',
                'domain' => 'test-domain',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax',
            ],
            $params
        );
    }

    public function provideSecureSamesiteData(): array
    {
        $data = [];
        foreach ([true, false] as $secure) {
            foreach (['Strict', 'Lax', 'None'] as $sameSite) {
                foreach (['https://secure.example.com/', 'http://insecure.example.com/'] as $alternateBase) {
                    if ($sameSite === 'None') {
                        // secure is always true if samesite is "None"
                        $secure = true;
                    } else {
                        // secure cannot be true for insecure requests
                        $secure = (strpos($alternateBase, 'https:') === 0) && $secure;
                    }
                    $data[] = [
                        $secure,
                        $sameSite,
                        $alternateBase,
                        [
                            'secure' => $secure,
                            'samesite' => $sameSite,
                        ]
                    ];
                }
            }
        }
        return $data;
    }

    /**
     * @dataProvider provideSecureSamesiteData
     */
    public function testBuildCookieParamsSecureAndSamesite(
        bool $secure,
        string $sameSite,
        string $alternateBase,
        array $expected
    ): void {
        $session = new Session(null);
        $methodBuildCookieParams = new ReflectionMethod($session, 'buildCookieParams');
        $methodBuildCookieParams->setAccessible(true);

        Config::modify()->set(Session::class, 'cookie_secure', $secure);
        Config::modify()->set(Session::class, 'cookie_samesite', $sameSite);
        Config::modify()->set(Director::class, 'alternate_base_url', $alternateBase);
        $params = $methodBuildCookieParams->invoke($session, new NullHTTPRequest());
        foreach ($expected as $key => $value) {
            $secure = $secure ? 'true' : 'false';
            $this->assertSame($value, $params[$key], "Inputs were 'secure': $secure, 'samesite': $sameSite, 'anternateBase': $alternateBase");
        }
    }

    /**
     * Check that the samesite value is being validated
     */
    public function testBuildCookieParamsSamesiteIsValidated(): void
    {
        $session = new Session(null);
        $methodBuildCookieParams = new ReflectionMethod($session, 'buildCookieParams');
        $methodBuildCookieParams->setAccessible(true);

        // Throw an exception when a warning is logged so we can catch it
        $mockLogger = $this->getMockBuilder(Logger::class)->setConstructorArgs(['testLogger'])->getMock();
        $catchMessage = 'A warning was logged';
        $mockLogger->expects($this->once())
            ->method('warning')
            ->willThrowException(new Exception($catchMessage));
        Injector::inst()->registerService($mockLogger, LoggerInterface::class);

        // samesite "None" should log a warning for non-https requests
        Config::modify()->set(Director::class, 'alternate_base_url', 'http://insecure.example.com/some-path');
        Config::modify()->set(Session::class, 'cookie_samesite', 'None');
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($catchMessage);
        $methodBuildCookieParams->invoke($session, new NullHTTPRequest());
    }

    public function testInvalidSamesite(): void
    {
        $session = new Session(null);
        $methodBuildCookieParams = new ReflectionMethod($session, 'buildCookieParams');
        $methodBuildCookieParams->setAccessible(true);

        $this->expectException(LogicException::class);
        Config::modify()->set(Session::class, 'cookie_samesite', 'invalid');
        $methodBuildCookieParams->invoke($session, new NullHTTPRequest());
    }
}
