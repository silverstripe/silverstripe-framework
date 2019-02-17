<?php
namespace SilverStripe\Security\HashMethod;

use Exception;
use SilverStripe\Security\CryptographicHashService;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberPassword;
use SilverStripe\Security\PasswordEncryptor;
use SilverStripe\Security\Security;

/**
 * Provides temporary fallback support for the <4.4 "PasswordEncryptor"s
 */
class LegacyMemberHash implements HashMethodInterface
{
    /**
     * LegacyMemberHash constructor.
     * @param Member $member
     */
    const IDENTIFIER = 'legacy';

    /**
     * Return a hash of the given plaintext usually with the salt as part of the returned hash
     *
     * @param string $plaintext
     * @return string
     * @throws Exception
     */
    public function hash($plaintext)
    {
        throw new Exception(sprintf(
            'Hashing with %s is deprecated and is not supported. Consider using a different hash method or use the '
                . 'legacy API directly if this is absolutely required.',
            __CLASS__
        ));
    }

    /**
     * Verify that a given string matches the given hash
     *
     * @param string $plaintext
     * @param string $hash
     * @return boolean
     * @throws \SilverStripe\Security\PasswordEncryptor_NotFoundException
     */
    public function verify($plaintext, $hash)
    {
        list($salt, $encryption, $hash) = $this->extractSaltAndEncryption($hash);

        $hasher = PasswordEncryptor::create_for_algorithm($encryption);

        return hash_equals(
            $hash,
            $hasher->encrypt($plaintext, $salt)
        );
    }

    /**
     * Indicate whether the given hash should be rehashed (because it was using an old algorithm or outdated algorithm
     * settings).
     *
     * @param $hash
     * @return boolean
     */
    public function needsRehash($hash)
    {
        // Get the configured default algo
        $algorithm = Security::config()->get('password_encryption_algorithm');

        list($_, $encryption) = $this->extractSaltAndEncryption($hash);

        // If the member record doesn't match the old config setting, it should be rehashed
        return $algorithm !== $encryption;
    }

    /**
     * Return a short token that uniquely identifies this hash method
     *
     * @return string
     */
    public function identifier()
    {
        return self::IDENTIFIER;
    }

    public static function isLegacyHash($hash)
    {
        // Check that we don't have the hash type separator
        return strpos($hash, CryptographicHashService::IDENTIFIER_SEPARATOR) === false;
    }

    /**
     * @param Member|MemberPassword $member
     * @return string
     */
    public static function prepLegacyHash($member)
    {
        return sprintf(
            '%s%s%s,%s,%s',
            self::IDENTIFIER,
            CryptographicHashService::IDENTIFIER_SEPARATOR,
            $member->PasswordEncryption,
            $member->Salt,
            $member->Password
        );
    }

    protected function extractSaltAndEncryption($hash)
    {
        $encryption = strtok($hash, ',');
        $salt = strtok(',');
        $hash = strtok(',');

        return [$salt, $encryption, $hash];
    }
}
