<?php
namespace SilverStripe\Security;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Security\HashMethod\LegacyMemberHash;

class PasswordHashService
{
    use Injectable;
    use Configurable;

    private static $dependencies = [
        'CryptoHashService' => '%$' . CryptographicHashService::class,
    ];

    /**
     * @var CryptographicHashService
     */
    protected $cryptoHashService;

    public function setForMember($plaintext, Member $member, $skipValidation = false)
    {
        $member->Password = $this->getCryptoHashService()->hash($plaintext);
        // Member "Salt" and "PasswordEncryption" columns are deprecated and don't need to be updated with new hash
        // types. They should be left alone for user code using `Member::encryptWithUserSettings`

        // Use a password validator to assert the password isn't bad
        $member->validate()
        $member->write();
    }

    public function verifyForMember($plaintext, Member $member, $skipRehash = false)
    {
        // Use the attached hash service
        $cryptoService = $this->getCryptoHashService();
        $hash = $member->Password;

        // Provide any fallback for the "old ways"
        // TODO Remove in 5.0
        if (LegacyMemberHash::isLegacyHash($hash)) {
            $hash = LegacyMemberHash::prepLegacyHash($hash, $member);
        }

        $result = $cryptoService->verify($plaintext, $hash);

        // If the verification failed, we've been told not to rehash, or a rehash is not required we can end here
        // We can early return without the worry of timing attack because the failure path always returns here.
        if (!$result || $skipRehash || !$cryptoService->needsRehash($hash)) {
            return $result;
        }

        $this->setForMember($plaintext, $member);

        return true;
    }

    /**
     * @return CryptographicHashService
     */
    public function getCryptoHashService()
    {
        return $this->cryptoHashService;
    }

    /**
     * @param CryptographicHashService $cryptoHashService
     * @return $this
     */
    public function setCryptoHashService(CryptographicHashService $cryptoHashService)
    {
        $this->cryptoHashService = $cryptoHashService;

        return $this;
    }
}
