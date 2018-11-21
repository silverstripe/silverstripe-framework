<?php
namespace SilverStripe\Security\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\CryptographicHashService;
use SilverStripe\Security\HashMethod\LegacyMemberHash;
use SilverStripe\Security\Member;
use SilverStripe\Security\PasswordEncryptor_Blowfish;

class CryptographicHashServiceTest extends SapphireTest
{
    public function testVerifyLegacyHash()
    {
        // Create a legacy hash to verify
        $legacyHasher = new PasswordEncryptor_Blowfish();
        $salt = $legacyHasher->salt('does not matter');
        $hash = $legacyHasher->encrypt('phrase', $salt);

        $this->assertTrue(LegacyMemberHash::isLegacyHash($hash), 'Legacy hashes can be identified');

        // Create a fake member for denying `write`.
        $fakeMember = $this->getMockBuilder(Member::class)->setConstructorArgs([
            'Salt' => $salt,
            'PasswordEncryption' => 'blowfish',
        ])->getMock();

        $hash = LegacyMemberHash::prepLegacyHash($hash, $fakeMember);
        // Assert that prep has added necessary boilerplate
        $this->assertStringStartsWith(
            'legacy**blowfish,' . $salt . ',',
            $hash,
            'prepLegacyHash appends the given hash with relevant details from the given Member'
        );

        // Verify the hash
        $service = CryptographicHashService::create();
        $this->assertTrue($service->verify('phrase', $hash), 'Correct passphrase returns true');
        $this->assertFalse($service->verify('phrase1', $hash), 'Incorrect passphrase returns false');
    }
}
