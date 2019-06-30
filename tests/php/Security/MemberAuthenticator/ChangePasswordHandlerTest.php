<?php declare(strict_types = 1);

namespace SilverStripe\Security\Tests\MemberAuthenticator;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberAuthenticator\ChangePasswordHandler;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use SilverStripe\Security\Security;

class ChangePasswordHandlerTest extends SapphireTest
{
    protected static $fixture_file = 'ChangePasswordHandlerTest.yml';

    protected function setUp()
    {
        parent::setUp();

        Config::modify()
            ->set(Security::class, 'login_url', 'Security/login')
            ->set(Security::class, 'lost_password_url', 'Security/lostpassword');

        $this->logOut();
    }

    public function testExpiredOrInvalidTokenProvidesLostPasswordAndLoginLink()
    {
        $request = new HTTPRequest('GET', '/Security/changepassword', [
            'm' => $this->idFromFixture(Member::class, 'sarah'),
            't' => 'an-old-or-expired-hash',
        ]);
        $request->setSession(new Session([]));

        /** @var ChangePasswordHandler $handler */
        $handler = $this->getMockBuilder(ChangePasswordHandler::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $handler->setRequest($request)->changepassword();

        $this->assertInternalType('array', $result, 'An array is returned');
        $this->assertContains('Security/lostpassword', $result['Content'], 'Lost password URL is included');
        $this->assertContains('Security/login', $result['Content'], 'Login URL is included');
    }
}
