<?php

namespace SilverStripe\Security\Tests\MemberAuthenticator;

use PHPUnit\Framework\Attributes\DataProvider;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Cache\RateLimiter;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberAuthenticator\LostPasswordHandler;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use SilverStripe\Security\Security;

class LostPasswordHandlerTest extends FunctionalTest
{
    protected static $fixture_file = 'LostPasswordHandlerTest.yml';

    protected $autoFollowRedirection = false;

    private ?string $originalRemoteAddress = null;

    protected function setUp(): void
    {
        Config::modify()->set(MemberAuthenticator::class, 'authenticators', []);
        Config::modify()->set(MemberAuthenticator::class, 'default_authenticator', MemberAuthenticator::class);
        Injector::inst()->load([
            Security::class => [
                'properties' => [
                    'Authenticators' => [
                        'default' => '%$' . MemberAuthenticator::class,
                    ],
                ],
            ],
        ]);
        Member::config()->set('unique_identifier_field', 'Email');
        Member::set_password_validator(null);
        parent::setUp();
        Director::config()->set('alternate_base_url', '/');
        // The rate limiter cache is process-wide, so clear accumulated hits from earlier tests.
        Injector::inst()->get(CacheInterface::class . '.RateLimiter')->clear();
        $this->originalRemoteAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->originalRemoteAddress === null) {
            unset($_SERVER['REMOTE_ADDR']);
        } else {
            $_SERVER['REMOTE_ADDR'] = $this->originalRemoteAddress;
        }
        parent::tearDown();
    }

    public static function provideForgotPasswordResponseHidesThrottle(): array
    {
        return [
            'existing member' => [
                'email' => 'member@example.com',
            ],
            'address with no member' => [
                'email' => 'nobody@example.com',
            ],
        ];
    }

    #[DataProvider('provideForgotPasswordResponseHidesThrottle')]
    public function testForgotPasswordResponseHidesThrottle(string $email): void
    {
        Config::modify()->set(LostPasswordHandler::class, 'lock_out_after_reset_requests', 3);
        $this->get('Security/lostpassword');
        $reachedSuccessPage = [];
        for ($i = 0; $i < 4; $i++) {
            $response = $this->post('Security/lostpassword/LostPasswordForm', ['Email' => $email]);
            $reachedSuccessPage[] = str_contains((string) $response->getHeader('Location'), 'passwordsent');
        }
        $this->assertSame([true, true, true, true], $reachedSuccessPage);
    }

    public static function provideForgotPasswordThrottleIsPerIPAddress(): array
    {
        return [
            'different IPv4 address' => [
                'firstIP' => '192.0.2.1',
                'secondIP' => '198.51.100.1',
                'expected' => 4,
            ],
            'same IPv6 /64 network' => [
                'firstIP' => '2001:db8::1',
                'secondIP' => '2001:db8::ffff:1',
                'expected' => 3,
            ],
            'different IPv6 /64 network' => [
                'firstIP' => '2001:db8::1',
                'secondIP' => '2001:db8:0:1::1',
                'expected' => 4,
            ],
            'different IPv4-mapped IPv6 address' => [
                'firstIP' => '::ffff:192.0.2.1',
                'secondIP' => '::ffff:198.51.100.1',
                'expected' => 4,
            ],
        ];
    }

    #[DataProvider('provideForgotPasswordThrottleIsPerIPAddress')]
    public function testForgotPasswordThrottleIsPerIPAddress(string $firstIP, string $secondIP, int $expected): void
    {
        Config::modify()->set(LostPasswordHandler::class, 'lock_out_after_reset_requests', 3);
        $memberID = $this->idFromFixture(Member::class, 'member');
        $this->get('Security/lostpassword');
        $tokenHashes = [];
        foreach ([$firstIP, $firstIP, $firstIP, $firstIP, $secondIP] as $ip) {
            $_SERVER['REMOTE_ADDR'] = $ip;
            $this->post('Security/lostpassword/LostPasswordForm', ['Email' => 'member@example.com']);
            $tokenHashes[] = Member::get()->byID($memberID)->AutoLoginHash;
        }
        $this->assertCount($expected, array_unique($tokenHashes));
    }

    public static function provideForgotPasswordLimitsTokensPerMember(): array
    {
        return [
            'same spelling' => [
                'maxAttempts' => 3,
                'emails' => ['member@example.com', 'member@example.com', 'member@example.com', 'member@example.com'],
                'expected' => 3,
            ],
            'different case and accents' => [
                'maxAttempts' => 3,
                'emails' => ['member@example.com', 'Member@Example.com', 'MEMBER@EXAMPLE.COM', 'mémber@example.com', 'mèmber@example.com'],
                'expected' => 3,
            ],
            'throttle disabled' => [
                'maxAttempts' => 0,
                'emails' => ['member@example.com', 'member@example.com', 'member@example.com', 'member@example.com'],
                'expected' => 4,
            ],
        ];
    }

    #[DataProvider('provideForgotPasswordLimitsTokensPerMember')]
    public function testForgotPasswordLimitsTokensPerMember(int $maxAttempts, array $emails, int $expected): void
    {
        Config::modify()->set(LostPasswordHandler::class, 'lock_out_after_reset_requests', $maxAttempts);
        $memberID = $this->idFromFixture(Member::class, 'member');
        $this->get('Security/lostpassword');
        $tokenHashes = [];
        foreach ($emails as $email) {
            $this->post('Security/lostpassword/LostPasswordForm', ['Email' => $email]);
            $tokenHashes[] = Member::get()->byID($memberID)->AutoLoginHash;
        }
        $this->assertCount($expected, array_unique($tokenHashes));
    }

    public function testForgotPasswordLockOutLastsFromLastRequest(): void
    {
        Config::modify()->set(LostPasswordHandler::class, 'lock_out_after_reset_requests', 3);
        Config::modify()->set(LostPasswordHandler::class, 'lock_out_delay_mins', 15);
        $_SERVER['REMOTE_ADDR'] = '192.0.2.1';
        $memberID = $this->idFromFixture(Member::class, 'member');
        $this->get('Security/lostpassword');
        DBDatetime::set_mock_now('2030-01-01 12:00:00');
        $this->post('Security/lostpassword/LostPasswordForm', ['Email' => 'member@example.com']);
        DBDatetime::set_mock_now('2030-01-01 12:10:00');
        $this->post('Security/lostpassword/LostPasswordForm', ['Email' => 'member@example.com']);
        $this->post('Security/lostpassword/LostPasswordForm', ['Email' => 'member@example.com']);
        $lockOut = RateLimiter::create('lostpassword-lock-' . md5('member-' . $memberID . '|192.0.2.1'), 1, 15);
        $this->assertSame(15 * 60, $lockOut->getTimeToReset());
    }

    public function testForgotPasswordSharesLimitBetweenAddressesWithNoMember(): void
    {
        Config::modify()->set(Security::class, 'secure_min_execution_time', 0);
        Config::modify()->set(LostPasswordHandler::class, 'lock_out_after_reset_requests', 3);
        $_SERVER['REMOTE_ADDR'] = '192.0.2.1';
        $this->get('Security/lostpassword');
        foreach (['one@example.com', 'two@example.com', 'three@example.com'] as $email) {
            $this->post('Security/lostpassword/LostPasswordForm', ['Email' => $email]);
        }
        $lockOut = RateLimiter::create('lostpassword-lock-' . md5('email-___NOMEMBER___|192.0.2.1'), 1, 15);
        $this->assertFalse($lockOut->canAccess());
    }
}
