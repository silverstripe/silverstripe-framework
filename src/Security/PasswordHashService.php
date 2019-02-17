<?php
namespace SilverStripe\Security;

use InvalidArgumentException;
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

    public function setForMember($plaintext, Member $member, $skipWrite = false)
    {
        // We use `setField` because ->Password runs into the setter which would be recursive
        $member->setField('Password', $this->getCryptoHashService()->hash($plaintext));

        // Member "Salt" and "PasswordEncryption" columns are deprecated and don't need to be updated with new hash
        // types. They should be left alone for user code using `Member::encryptWithUserSettings` (also deprecated)

        if (!$skipWrite) {
            $member->write();
        }
    }

    /**
     * @param string $plaintext
     * @param Member|MemberPassword $member
     * @param bool $skipRehash
     * @return bool
     */
    public function verifyForMember($plaintext, $member, $skipRehash = false)
    {
        if (!$member instanceof Member && !$member instanceof MemberPassword) {
            throw new InvalidArgumentException(sprintf(
                '%s expects method 2 to be an instance of %s or %s. %s given',
                __METHOD__,
                Member::class,
                MemberPassword::class,
                is_object($member) ? get_class($member) : gettype($member)
            ));
        }

        // Use the attached hash service
        $cryptoService = $this->getCryptoHashService();
        $hash = $member->Password;

        // Provide temporary fallback for the "old ways"
        // TODO Remove in 5.0
        if (LegacyMemberHash::isLegacyHash($hash)) {
            $hash = LegacyMemberHash::prepLegacyHash($member);
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
