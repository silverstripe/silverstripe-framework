<?php
namespace SilverStripe\Security\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\CryptographicHashService;
use SilverStripe\Security\HashMethod\LegacyMemberHash;
use SilverStripe\Security\Member;
use SilverStripe\Security\PasswordEncryptor_Blowfish;
use SilverStripe\Security\PasswordHashService;

/**
 * Test the PasswordHashService
 *
 * This service is a thin layer around CryptographicHashService to facilitate a simpler API based around Member and most
 * importantly automatically handle rehashing passwords. Tests in this suite might be better suited as a test in
 * CryptographicHashServiceTest.
 */
class PasswordHashServiceTest extends SapphireTest
{
    public function testVerifyLegacyHash()
    {
        // Although most verification done by PasswordHashService is a fairly simple passthrough to
        // CryptographicHashService there is some custom logic to handle verification of "legacy" hashes

        // Create a legacy hash to verify
        $legacyHasher = new PasswordEncryptor_Blowfish();
        $salt = $legacyHasher->salt('does not matter');
        $hash = $legacyHasher->encrypt('phrase', $salt);

        $member = $this->mockMember([
            'Password' => $hash,
            'Salt' => $salt,
            'PasswordEncryption' => 'blowfish',
        ]);

        $service = PasswordHashService::create();

        $this->assertTrue($service->verifyForMember('phrase', $member), 'The correct passphrase return true');
        $this->assertFalse($service->verifyForMember('phrase1', $member), 'The incorrect passphrase return false');
    }

    public function testPasswordIsRehashedWhenRequired()
    {
        // Mock the hash service to always respond with true on needs rehash the first time
        $hashService = $this->getMockBuilder(CryptographicHashService::class)
            ->setMethodsExcept(['verify', 'hash']) // Allow passthrough for verify & hash
            ->getMock();
        $hashService->expects($this->exactly(2))
            ->method('needsRehash')
            ->willReturn(true, false);

        // Create the password service and set our own hash service
        $passwordService = PasswordHashService::create();
        $passwordService->setCryptoHashService($hashService);

        // Mock the member with an existing hash
        $member = $this->mockMember([
            'Password' => $existingHash = CryptographicHashService::create()->hash('phrase'),
        ]);

        // Expect a write
        $member->expects($this->once())->method('write');

        // Verify and then the hash should have changed
        $this->assertTrue(
            $passwordService->verifyForMember('phrase', $member),
            'Verification of correct passphase is true even when rehashing'
        );
        $this->assertNotSame($existingHash, $member->Password, 'The password is now different from the existing hash');

        $this->assertTrue(
            $passwordService->verifyForMember('phrase', $member),
            'Correct phrase is still correctly verified after rehash'
        );
    }

    public function testPasswordIsNotRehashedWhenNotRequired()
    {
        // Mock the hash service to always respond with true on needs rehash the first time
        $hashService = $this->getMockBuilder(CryptographicHashService::class)
            ->setMethodsExcept(['verify', 'hash']) // Allow passthrough for verify & hash
            ->getMock();
        $hashService->expects($this->once())
            ->method('needsRehash')
            ->willReturn(false);

        // Create the password service and set our own hash service
        $passwordService = PasswordHashService::create();
        $passwordService->setCryptoHashService($hashService);

        // Mock the member with an existing hash
        $member = $this->mockMember([
            'Password' => $existingHash = CryptographicHashService::create()->hash('phrase'),
        ]);
        // There should never be a write
        $member->expects($this->never())->method('write');

        $this->assertTrue(
            $passwordService->verifyForMember('phrase', $member),
            'Verification of correct passphase is true'
        );
    }

    public function testPasswordChangesHashMethodWhenRehashing()
    {
        // Create a legacy hash to verify
        $legacyHasher = new PasswordEncryptor_Blowfish();
        $salt = $legacyHasher->salt('does not matter');
        $hash = $legacyHasher->encrypt('phrase', $salt);

        $member = $this->mockMember([
            'Password' => $hash,
            'Salt' => $salt,
            'PasswordEncryption' => 'blowfish',
        ]);

        // Expect a write
        $member->expects($this->once())->method('write');

        $passwordService = PasswordHashService::create();

        // Verify and then the hash should have changed
        $this->assertTrue(
            $passwordService->verifyForMember('phrase', $member),
            'Verification of correct passphase is true even when rehashing'
        );
        $this->assertNotSame($hash, $member->Password, 'The password is now different from the existing hash');

        $this->assertTrue(
            $passwordService->verifyForMember('phrase', $member),
            'Correct phrase is still correctly verified after rehash'
        );
        $this->assertFalse(
            LegacyMemberHash::isLegacyHash($member->Password),
            'Legacy password hash has been changed to newer method'
        );
    }

    protected function mockMember(array $record = [])
    {
        $mock = $this->getMockBuilder(Member::class)
            ->getMock();

        // Mock getting properties
        $mock->expects($this->any())->method('__get')->willReturnCallback(function ($key) use (&$record) {
            if (!isset($record[$key])) {
                $this->fail('Did not expect to return a value for $member->' . $key);
            }
            return $record[$key];
        });

        // And mock setting them
        $mock->expects($this->any())
            ->method('__set')
            ->willReturnCallback(function ($key, $value) use (&$record, $mock) {
                $record[$key] = $value;
                return $mock;
            });

        // Overload calls to `write` to save time...
        $mock->method('write')->willReturnArgument(1);

        return $mock;
    }
}
