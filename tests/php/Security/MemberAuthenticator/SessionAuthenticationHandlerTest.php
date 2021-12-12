<?php
namespace SilverStripe\Security\Tests\MemberAuthenticator;

use SilverStripe\Control\Cookie;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberAuthenticator\SessionAuthenticationHandler;

class SessionAuthenticationHandlerTest extends SapphireTest
{
    protected static $fixture_file = 'SessionAuthenticationHandlerTest.yml';

    protected $usesDatabase = true;

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAuthenticateRequestDefersSessionStartWithoutSessionIdentifier()
    {
        $member = new Member(['Email' => 'test@example.com']);
        $member->write();

        $handler = new SessionAuthenticationHandler();

        $session = new Session(null); // unstarted, simulates lack of session cookie
        $session->set($handler->getSessionVariable(), $member->ID);

        $req = new HTTPRequest('GET', '/');
        $req->setSession($session);

        $matchedMember = $handler->authenticateRequest($req);
        $this->assertNull($matchedMember);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAuthenticateRequestStartsSessionWithSessionIdentifier()
    {
        $member = new Member(['Email' => 'test@example.com']);
        $member->write();

        $handler = new SessionAuthenticationHandler();

        $session = new Session(null); // unstarted
        $session->set($handler->getSessionVariable(), $member->ID);

        $req = new HTTPRequest('GET', '/');
        $req->setSession($session);

        Cookie::set(session_name(), '1234');
        $session->start($req); // simulate detection of session cookie

        $matchedMember = $handler->authenticateRequest($req);
        $this->assertNotNull($matchedMember);
        $this->assertEquals($matchedMember->Email, $member->Email);
    }

    public function testLoginMarkerCookie()
    {
        Config::modify()->set(Member::class, 'login_marker_cookie', 'sslogin');

        /** @var Member $member */
        $member = $this->objFromFixture(Member::class, 'test');

        $this->logInAs($member);

        $this->assertNotNull(Cookie::get('sslogin'), 'Login marker cookie is set after logging in');

        $this->logOut();

        $this->assertNull(Cookie::get('sslogin'), 'Login marker cookie is deleted after logging out');
    }
}
