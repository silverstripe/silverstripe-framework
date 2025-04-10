<?php

namespace SilverStripe\Security\Tests\MemberAuthenticator;

use ReflectionClass;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberAuthenticator\ChangePasswordForm;
use SilverStripe\Security\MemberAuthenticator\ChangePasswordHandler;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use SilverStripe\Security\RandomGenerator;
use SilverStripe\Security\Security;

class ChangePasswordHandlerTest extends SapphireTest
{
    protected static $fixture_file = 'ChangePasswordHandlerTest.yml';

    private ChangePasswordHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logOut();

        $this->handler = new ChangePasswordHandler('/Security/changepassword', new MemberAuthenticator());
        $generator = new class() extends RandomGenerator {
            public function randomToken($algorithm = 'whirlpool')
            {
                return 'temporary-hash';
            }
        };
        Injector::inst()->registerService($generator, RandomGenerator::class);
        // These values need to be set dynamically rather than as static fixture values.
        $member = $this->objFromFixture(Member::class, 'sarah');
        $member->AutoLoginHash = $member->encryptWithUserSettings($member->AutoLoginHash);
        $member->AutoLoginExpired = date('Y-m-d H:i:s', strtotime('+7 days'));
        $member->write();
    }

    protected function tearDown(): void
    {
        $handlerReflection = new ReflectionClass(ChangePasswordHandler::class);
        $handlerReflection->setStaticPropertyValue('tempHashAlreadyGenerated', false);
        $handlerReflection->setStaticPropertyValue('tempHashAlreadyProcessed', false);
    }

    public function testValidUrlParamsRedirectsWithTemporaryHash()
    {
        $request = $this->createRequest([
            'm' => $this->idFromFixture(Member::class, 'sarah'),
            't' => 'foobar',
        ]);
        $result = $this->handler->setRequest($request)->changepassword();
        $this->assertInstanceOf(HTTPResponse::class, $result);
        $this->assertSame(302, $result->getStatusCode());
        $location = Director::absoluteURL('/Security/changepassword?th=temporary-hash');
        $this->assertSame($location, $result->getHeader('Location'));
    }

    public function testExpiredOrInvalidToken()
    {
        $request = $this->createRequest([
            'm' => $this->idFromFixture(Member::class, 'sarah'),
            't' => 'an-old-or-expired-hash',
        ]);
        $result = $this->handler->setRequest($request)->changepassword();
        $this->assertIsArray($result);
        $this->assertSame(['Content'], array_keys($result));
        $this->assertSame($this->getInvalidTokenString(), $result['Content']->getValue());
    }

    public function testTemporaryHashInUrlRendersForm()
    {
        $this->setMemberAutoLoginTempHash();
        $request = $this->createRequest([
            'th' => 'temporary-hash'
        ]);
        $result = $this->handler->setRequest($request)->changepassword();
        $this->assertIsArray($result);
        $this->assertSame(['Content', 'Form'], array_keys($result));
        $this->assertInstanceOf(ChangePasswordForm::class, $result['Form']);
        $this->assertNotSame($this->getInvalidTokenString(), $result['Content']->getValue());
    }

    public function testSubsequentTemporaryHash()
    {
        $this->setMemberAutoLoginTempHash();
        $request = $this->createRequest([
            'th' => 'temporary-hash'
        ]);
        $this->handler->setRequest($request)->changepassword();
        // Simulate starting a fresh request cycle
        $handlerReflection = new ReflectionClass(ChangePasswordHandler::class);
        $handlerReflection->setStaticPropertyValue('tempHashAlreadyGenerated', false);
        $handlerReflection->setStaticPropertyValue('tempHashAlreadyProcessed', false);
        $request = $this->createRequest([
            'th' => 'temporary-hash'
        ]);
        $this->handler->setRequest($request);
        $result = $this->handler->changepassword();
        $this->assertIsArray($result);
        $this->assertInstanceOf(DBHTMLText::class, $result['Content'] ?? null);
        $this->assertSame($this->getInvalidTokenString(), $result['Content']->getValue());
    }

    public function testIncorrectTemporaryHashInUrl()
    {
        $this->setMemberAutoLoginTempHash();
        $request = $this->createRequest([
            'th' => 'wrong-hash'
        ]);
        $result = $this->handler->setRequest($request)->changepassword();
        $this->assertIsArray($result);
        $this->assertInstanceOf(DBHTMLText::class, $result['Content'] ?? null);
        $this->assertSame($this->getInvalidTokenString(), $result['Content']->getValue());
    }

    /**
     * Create a HTTPRequest for testing with.
     */
    private function createRequest(array $params = []): HTTPRequest
    {
        $request = new HTTPRequest('POST', '/Security/changepassword', $params);
        $request->setSession(new Session([]));
        return $request;
    }

    /**
     * Set the AutoLoginTempHash to 'temporary-hash' for the test member.
     * This is used to simulate the second stage of the change password process.
     */
    private function setMemberAutoLoginTempHash(): void
    {
        $member = $this->objFromFixture(Member::class, 'sarah');
        $member->AutoLoginTempHash = 'temporary-hash';
        $member->write();
    }

    private function getInvalidTokenString(): string
    {
        $link1 = Security::lost_password_url();
        $link2 = Security::login_url();
        return '<p>The password reset link is invalid or expired.</p>'
        . "<p>You can request a new one <a href=\"{$link1}\">here</a> or change your password after"
        . " you <a href=\"{$link2}\">log in</a>.</p>";
    }
}
