<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\Control\Director;
use SilverStripe\Control\Tests\HttpRequestMockBuilder;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\PasswordExpirationMiddleware;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

class PasswordExpirationMiddlewareTest extends SapphireTest
{
    use HttpRequestMockBuilder;

    private $rollbackMember;

    private $rollbackEnable;

    private $rollbackDefaultRedirect;

    private $rollbackWhitelistedUrls;

    private $rollbackWhitelistedUrlStartswith;

    protected function setUp()
    {
        Director::config()->set('alternate_base_url', 'http://localhost/custom-base/');
        $this->rollbackMember = Security::getCurrentUser();
        $this->rollbackEnable =
            PasswordExpirationMiddleware::config()->get('enable');
        $this->rollbackDefaultRedirect =
            PasswordExpirationMiddleware::config()->get('default_redirect');
        $this->rollbackWhitelistedUrls =
            PasswordExpirationMiddleware::config()->get('whitelisted_urls');
        $this->rollbackWhitelistedUrlStartswith =
            PasswordExpirationMiddleware::config()->get('whitelisted_url_startswith');

        PasswordExpirationMiddleware::config()->set('enable', true);
        PasswordExpirationMiddleware::config()->set('default_redirect', null);
        PasswordExpirationMiddleware::config()->set('whitelisted_urls', []);
        PasswordExpirationMiddleware::config()->set('whitelisted_url_startswith', []);
    }

    protected function tearDown()
    {
        Director::config()->remove('alternate_base_url');
        Security::setCurrentUser($this->rollbackMember);
        PasswordExpirationMiddleware::config()->set(
            'enable',
            $this->rollbackEnable
        );
        PasswordExpirationMiddleware::config()->set(
            'default_redirect',
            $this->rollbackDefaultRedirect
        );
        PasswordExpirationMiddleware::config()->set(
            'whitelisted_urls',
            $this->rollbackWhitelistedUrls
        );
        PasswordExpirationMiddleware::config()->set(
            'whitelisted_url_startswith',
            $this->rollbackWhitelistedUrlStartswith
        );
    }

    /**
     * Returns Member mock object
     *
     * @param bool $isPasswordExpired result of the function {@see SilverStripe\Security\Member::isPasswordExpired}
     *
     * @return Member
     */
    private function getMemberMock($isPasswordExpired) : Member
    {
        $mock = $this->createMock(Member::class);
        $mock->method('isPasswordExpired')->will($this->returnValue($isPasswordExpired));

        return $mock;
    }

    public function test200()
    {
        $member = $this->getMemberMock(false);

        $a = new PasswordExpirationMiddleware();

        $request = $this->buildRequestMock('/');
        Security::setCurrentUser($member);
        $executed = false;
        $response = $a->process($request, static function () use (&$executed) {
            $executed = true;
            return "delegated";
        });

        $this->assertEquals($member, Security::getCurrentUser());
        $this->assertTrue($executed);
        $this->assertEquals("delegated", $response);
    }

    /**
     * Check a member with an expired password is allowed to process the request in
     * deauthorised mode (Security::getCurrentUser() === null) if there are no
     * change password redirects registered
     *
     * @depends test200
     */
    public function testDeauthorised()
    {
        $member = $this->getMemberMock(true);
        $this->assertTrue($member->isPasswordExpired());

        $a = new PasswordExpirationMiddleware();

        $request = $this->buildRequestMock('/');
        $executed = false;
        $activeMember = null;
        Security::setCurrentUser($member);
        $response = $a->process($request, static function () use (&$executed, &$activeMember) {
            $executed = true;
            $activeMember = Security::getCurrentUser();
        });

        $this->assertTrue($executed);
        $this->assertNull($activeMember);
    }

    /**
     * Check a member with an expired password is redirected to a change password form
     * instead of processing its original request
     *
     * @depends test200
     */
    public function testRedirected()
    {
        $member = $this->getMemberMock(true);
        $this->assertTrue($member->isPasswordExpired());

        $a = new PasswordExpirationMiddleware();

        $request = $this->buildRequestMock('/');
        $request->method('getAcceptMimetypes')->will($this->returnValue(['*/*']));
        $session = $request->getSession();

        $a->setRedirect($session, '/redirect-address-custom');

        $executed = false;
        $activeMember = null;
        Security::setCurrentUser($member);
        $response = $a->process($request, static function () use (&$executed, &$activeMember) {
            $executed = true;
            $activeMember = Security::getCurrentUser();
        });

        $this->assertFalse($executed);
        $this->assertNull($activeMember);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(Director::absoluteURL('redirect-address-custom'), $response->getHeader('Location'));
    }

    /**
     * Check we can deactivate expired passwords check by some configuration
     *
     * @depends test200
     * @depends testDeauthorised
     */
    public function testDisable()
    {
        $member = $this->getMemberMock(true);

        $this->assertTrue($member->isPasswordExpired());

        PasswordExpirationMiddleware::config()->set('enable', false);
        $a = new PasswordExpirationMiddleware();

        $request = $this->buildRequestMock('/');
        $executed = false;
        $activeMember = null;
        Security::setCurrentUser($member);
        $response = $a->process($request, static function () use (&$executed, &$activeMember) {
            $executed = true;
            $activeMember = Security::getCurrentUser();
        });

        $this->assertTrue($executed);
        $this->assertEquals($member, $activeMember);
    }

    /**
     * Check we can allow the current request handling even with an expired password
     *
     * @depends test200
     * @depends testDeauthorised
     */
    public function testAllowRequest()
    {
        $member = $this->getMemberMock(true);
        $this->assertTrue($member->isPasswordExpired());

        $a = new PasswordExpirationMiddleware();

        $request = $this->buildRequestMock('/');
        $session = $request->getSession();
        PasswordExpirationMiddleware::allowCurrentRequest($session);

        $executed = false;
        $activeMember = null;
        Security::setCurrentUser($member);
        $response = $a->process($request, static function () use (&$executed, &$activeMember) {
            $executed = true;
            $activeMember = Security::getCurrentUser();
        });

        $this->assertTrue($executed);
        $this->assertEquals($member, $activeMember);
    }

    /**
     * Check a member with an expired password is redirected to a default change password form
     * if a custom not set
     *
     * @depends testRedirected
     */
    public function testDefaultRedirect()
    {
        $member = $this->getMemberMock(true);
        $this->assertTrue($member->isPasswordExpired());

        PasswordExpirationMiddleware::config()->set('default_redirect', '/redirect-address-default');

        $a = new PasswordExpirationMiddleware();

        $request = $this->buildRequestMock('/');
        $request->method('getAcceptMimetypes')->will($this->returnValue(['*/*']));
        $session = $request->getSession();

        $executed = false;
        $activeMember = null;
        Security::setCurrentUser($member);
        $response = $a->process($request, static function () use (&$executed, &$activeMember) {
            $executed = true;
            $activeMember = Security::getCurrentUser();
        });

        $this->assertFalse($executed);
        $this->assertNull($activeMember);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(Director::absoluteURL('redirect-address-default'), $response->getHeader('Location'));
    }

    /**
     * Check a member with an expired password is redirected to a default change password form
     * if a custom not set
     *
     * @depends testDefaultRedirect
     */
    public function testCustomRedirect()
    {
        $member = $this->getMemberMock(true);
        $this->assertTrue($member->isPasswordExpired());

        PasswordExpirationMiddleware::config()->set('default_redirect', '/redirect-address-default');

        $a = new PasswordExpirationMiddleware();

        $request = $this->buildRequestMock('/');
        $request->method('getAcceptMimetypes')->will($this->returnValue(['*/*']));

        $executed = false;
        $activeMember = null;
        Security::setCurrentUser($member);
        $response = $a->process($request, static function () use (&$executed, &$activeMember) {
            $executed = true;
            $activeMember = Security::getCurrentUser();
        });

        $this->assertFalse($executed);
        $this->assertNull($activeMember);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(Director::absoluteURL('redirect-address-default'), $response->getHeader('Location'));
    }

    /**
     * Check a custom redirect URL overrides the default one
     *
     * @depends testCustomRedirect
     */
    public function testCustomOverDefaultRedirect()
    {
        $member = $this->getMemberMock(true);
        $this->assertTrue($member->isPasswordExpired());

        PasswordExpirationMiddleware::config()->set('default_redirect', '/redirect-address-default');

        $a = new PasswordExpirationMiddleware();

        $request = $this->buildRequestMock('/');
        $request->method('getAcceptMimetypes')->will($this->returnValue(['*/*']));
        $session = $request->getSession();
        $a->setRedirect($session, '/redirect-address-custom');

        $executed = false;
        $activeMember = null;
        Security::setCurrentUser($member);
        $response = $a->process($request, static function () use (&$executed, &$activeMember) {
            $executed = true;
            $activeMember = Security::getCurrentUser();
        });

        $this->assertFalse($executed);
        $this->assertNull($activeMember);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(Director::absoluteURL('redirect-address-custom'), $response->getHeader('Location'));
    }

    /**
     * Test we can allow URLs to be visited without redirections
     *
     * @depends testRedirected
     */
    public function testSessionAllowedUrls()
    {
        $member = $this->getMemberMock(true);
        $this->assertTrue($member->isPasswordExpired());

        $a = new PasswordExpirationMiddleware();

        $request = $this->buildRequestMock('/allowed-address-custom');
        $request->method('getAcceptMimetypes')->will($this->returnValue(['*/*']));
        $session = $request->getSession();

        $a->setRedirect($session, '/redirect-address-custom');
        $a->whitelistUrl($session, '/allowed-address-custom');

        $executed = false;
        $activeMember = null;
        Security::setCurrentUser($member);
        $response = $a->process($request, static function () use (&$executed, &$activeMember) {
            $executed = true;
            $activeMember = Security::getCurrentUser();
        });

        $this->assertTrue($executed);
        $this->assertEquals($member, $activeMember);
    }

    /**
     * Test we can allow URLs to be visited without redirections through config
     *
     * @depends testRedirected
     */
    public function testConfigAllowedUrlStartswith()
    {
        $member = $this->getMemberMock(true);
        $this->assertTrue($member->isPasswordExpired());

        PasswordExpirationMiddleware::config()->set('whitelisted_url_startswith', [
            '/allowed-address-configured/'
        ]);

        $a = new PasswordExpirationMiddleware();

        // Check 1: allowed exact url
        $request = $this->buildRequestMock('/allowed-address-configured/');
        $request->method('getAcceptMimetypes')->will($this->returnValue(['*/*']));
        $session = $request->getSession();

        $a->setRedirect($session, '/redirect-address-custom');

        $executed = false;
        $activeMember = null;
        Security::setCurrentUser($member);
        $response = $a->process($request, static function () use (&$executed, &$activeMember) {
            $executed = true;
            $activeMember = Security::getCurrentUser();
        });

        $this->assertTrue($executed);
        $this->assertEquals($member, $activeMember);

        // Check 2: not allowed
        $request = $this->buildRequestMock('/not-allowed');
        $request->method('getAcceptMimetypes')->will($this->returnValue(['*/*']));
        $session = $request->getSession();

        $a->setRedirect($session, '/redirect-address-custom');
        $a->whitelistUrl($session, '/allowed-address-custom');

        $executed = false;
        $activeMember = null;
        $response = $a->process($request, static function () use (&$executed, &$activeMember) {
            $executed = true;
            $activeMember = Security::getCurrentUser();
        });

        $this->assertFalse($executed);
        $this->assertNull($activeMember);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(Director::absoluteURL('redirect-address-custom'), $response->getHeader('Location'));

        // Check 3: allowed url startswith
        $request = $this->buildRequestMock('/allowed-address-configured/subsection1/subsection2/');
        $request->method('getAcceptMimetypes')->will($this->returnValue(['*/*']));
        $session = $request->getSession();

        $a->setRedirect($session, '/redirect-address-custom');

        $executed = false;
        $activeMember = null;
        Security::setCurrentUser($member);
        $response = $a->process($request, static function () use (&$executed, &$activeMember) {
            $executed = true;
            $activeMember = Security::getCurrentUser();
        });

        $this->assertTrue($executed);
        $this->assertEquals($member, $activeMember);
    }

    /**
     * Test we can allow URLs to be visited without redirections through config
     *
     * @depends testRedirected
     */
    public function testConfigAllowedUrls()
    {
        $member = $this->getMemberMock(true);
        $this->assertTrue($member->isPasswordExpired());

        PasswordExpirationMiddleware::config()->set('whitelisted_urls', [
            '/allowed-address-configured'
        ]);

        $a = new PasswordExpirationMiddleware();

        // Check 1: configured allowed url
        $request = $this->buildRequestMock('/allowed-address-configured');
        $request->method('getAcceptMimetypes')->will($this->returnValue(['*/*']));
        $session = $request->getSession();

        $a->setRedirect($session, '/redirect-address-custom');

        $executed = false;
        $activeMember = null;
        Security::setCurrentUser($member);
        $response = $a->process($request, static function () use (&$executed, &$activeMember) {
            $executed = true;
            $activeMember = Security::getCurrentUser();
        });

        $this->assertTrue($executed);
        $this->assertEquals($member, $activeMember);

        // Check 2: custom allowed url
        $request = $this->buildRequestMock('/allowed-address-custom');
        $request->method('getAcceptMimetypes')->will($this->returnValue(['*/*']));
        $session = $request->getSession();

        $a->setRedirect($session, '/redirect-address-custom');
        $a->whitelistUrl($session, '/allowed-address-custom');

        $executed = false;
        $activeMember = null;
        $response = $a->process($request, static function () use (&$executed, &$activeMember) {
            $executed = true;
            $activeMember = Security::getCurrentUser();
        });

        $this->assertTrue($executed);
        $this->assertEquals($member, $activeMember);

        // Check 3: configured merged with custom
        $request = $this->buildRequestMock('/allowed-address-configured');
        $request->method('getAcceptMimetypes')->will($this->returnValue(['*/*']));
        $session = $request->getSession();

        $a->setRedirect($session, '/redirect-address-custom');
        $a->whitelistUrl($session, '/allowed-address-custom');

        $executed = false;
        $activeMember = null;
        $response = $a->process($request, static function () use (&$executed, &$activeMember) {
            $executed = true;
            $activeMember = Security::getCurrentUser();
        });

        $this->assertTrue($executed);
        $this->assertEquals($member, $activeMember);

        // Check 4: not allowed
        $request = $this->buildRequestMock('/not-allowed');
        $request->method('getAcceptMimetypes')->will($this->returnValue(['*/*']));
        $session = $request->getSession();

        $a->setRedirect($session, '/redirect-address-custom');
        $a->whitelistUrl($session, '/allowed-address-custom');

        $executed = false;
        $activeMember = null;
        $response = $a->process($request, static function () use (&$executed, &$activeMember) {
            $executed = true;
            $activeMember = Security::getCurrentUser();
        });

        $this->assertFalse($executed);
        $this->assertNull($activeMember);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(Director::absoluteURL('redirect-address-custom'), $response->getHeader('Location'));
    }
}
