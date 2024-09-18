<?php

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

    protected function setUp(): void
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

        // not using a phpunit mock otherwise get the error
        // Error: Typed property MockObject_ChangePasswordHandler_12f49d86::$__phpunit_state
        // must not be accessed before initialization
        $handler = new class() extends ChangePasswordHandler {
            public function __construct()
            {
            }
        };

        $result = $handler->setRequest($request)->changepassword();
        $this->assertIsArray($result, 'An array is returned');
        $this->assertStringContainsString('Security/lostpassword', $result['Content'], 'Lost password URL is included');
        $this->assertStringContainsString('Security/login', $result['Content'], 'Login URL is included');
    }
}
