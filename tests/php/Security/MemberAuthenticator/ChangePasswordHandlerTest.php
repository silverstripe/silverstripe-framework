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
use SilverStripe\Dev\FunctionalTest;

class ChangePasswordHandlerTest extends FunctionalTest
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

        /** @var ChangePasswordHandler $handler */
        $handler = $this->getMockBuilder(ChangePasswordHandler::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $handler->setRequest($request)->changepassword();

        $this->assertIsArray($result, 'An array is returned');
        $this->assertStringContainsString('Security/lostpassword', $result['Content'], 'Lost password URL is included');
        $this->assertStringContainsString('Security/login', $result['Content'], 'Login URL is included');
    }

    public function testLegitimateTokenLoadsChangePasswordForm()
    {
        $member = $this->objFromFixture(Member::class, 'sarah');
        $token = $member->generateAutologinTokenAndStoreHash();
        $hash = $member->AutoLoginHash;

        $request = new HTTPRequest('GET', '/Security/changepassword', [
            'm' => $member->ID,
            't' => $token,
        ]);
        $request->setSession(new Session([]));

        /** @var ChangePasswordHandler $handler */
        $handler = $this->getMockBuilder(ChangePasswordHandler::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $handler->setRequest($request)->changepassword();

        $this->assertIsArray($result, 'An array is returned');
        $this->assertArrayHasKey('Form', $result, 'Form is included');

        $hashField = $result['Form']->HiddenFields()->dataFieldByName('AutoLoginHash') ?? null;
        $this->assertIsObject($hashField, 'AutoLoginHash field is included');
        $this->assertEquals($hashField->value, $hash, 'AutoLoginHash field value is correct');
    }

    public function testSubmittingChangePasswordFormSucceedsWithValidToken()
    {
        $member = $this->objFromFixture(Member::class, 'sarah');
        $hash = $member->generateAutologinTokenAndStoreHash();

        $this->get("/Security/changepassword?m={$member->ID}&t={$hash}");
        $result = $this->submitForm('ChangePasswordForm_ChangePasswordForm', null, [
            'NewPassword1' => 'newpassword',
            'NewPassword2' => 'newpassword',
        ]);

        $this->assertStringContainsString(
            'You&#039;re logged in',
            $result->getBody(),
            'User is logged in'
        );
    }
}
