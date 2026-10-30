<?php

namespace SilverStripe\Security\Tests\MemberAuthenticator;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberAuthenticator\CookieAuthenticationHandler;
use SilverStripe\Security\RememberLoginHash;
use SilverStripe\Security\Tests\MemberAuthenticator\CookieAuthenticationHandlerTest\VetoLoginExtension;

class CookieAuthenticationHandlerTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $required_extensions = [
        Member::class => [
            VetoLoginExtension::class,
        ],
    ];

    private function buildHandler(): CookieAuthenticationHandler
    {
        $handler = new CookieAuthenticationHandler();
        $handler->setDeviceCookieName('alc_device');
        $handler->setTokenCookieName('alc_enc');
        $handler->setTokenCookieSecure(false);
        return $handler;
    }

    private function buildRequest(): HTTPRequest
    {
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        // The session renewal extension hook resolves the request from the injector
        Injector::inst()->registerService($request, HTTPRequest::class);
        return $request;
    }

    public static function provideAuthenticateRequestRestoresMember(): array
    {
        return [
            'unlocked member' => [
                'isThrottleLocked' => false,
            ],
            'throttle-locked member' => [
                'isThrottleLocked' => true,
            ],
        ];
    }

    #[DataProvider('provideAuthenticateRequestRestoresMember')]
    public function testAuthenticateRequestRestoresMember(bool $isThrottleLocked): void
    {
        $member = new Member(['Email' => 'test@example.com']);
        $member->write();
        $handler = $this->buildHandler();
        $request = $this->buildRequest();
        $handler->logIn($member, true, $request);
        if ($isThrottleLocked) {
            $member->LockedOutUntil = date('Y-m-d H:i:s', DBDatetime::now()->getTimestamp() + 3600);
            $member->write();
        }
        $matched = $handler->authenticateRequest($request);
        $this->assertNotNull($matched);
        $this->assertEquals($member->ID, $matched->ID);
    }

    public function testAuthenticateRequestRefusesVetoedMember(): void
    {
        $member = new Member(['Email' => VetoLoginExtension::VETOED_EMAIL]);
        $member->write();
        $handler = $this->buildHandler();
        $request = $this->buildRequest();
        $handler->logIn($member, true, $request);
        $matched = $handler->authenticateRequest($request);
        $this->assertNull($matched);
        $this->assertEmpty(Cookie::get('alc_enc'));
        $this->assertEmpty(Cookie::get('alc_device'));
        $this->assertSame(0, RememberLoginHash::get()->filter('MemberID', $member->ID)->count());
    }
}
