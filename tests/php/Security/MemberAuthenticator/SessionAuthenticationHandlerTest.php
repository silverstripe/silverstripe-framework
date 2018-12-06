<?php
namespace SilverStripe\Security\Tests\MemberAuthenticator;

use SilverStripe\Control\Cookie;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;

use SilverStripe\Security\Member;
use SilverStripe\Security\MemberAuthenticator\SessionAuthenticationHandler;

class SessionAuthenticationHandlerTest extends SapphireTest
{
    protected $usesDatabase = true;

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

    public function testAuthenticateRequestStartsSessionWithSessionIdentifier()
    {
        $member = new Member(['Email' => 'test@example.com']);
        $member->write();

        $handler = new SessionAuthenticationHandler();

        $session = new Session(null); // unstarted
        $session->set($handler->getSessionVariable(), $member->ID);

        $req = new HTTPRequest('GET', '/');
        $req->setSession($session);

        // Set up global state
        Cookie::set(session_name(), '1234');
        $_COOKIE[session_name()] = '1234';

        $session->start($req); // simulate detection of session cookie

        $matchedMember = $handler->authenticateRequest($req);
        $this->assertNotNull($matchedMember);
        $this->assertEquals($matchedMember->Email, $member->Email);
    }
}
