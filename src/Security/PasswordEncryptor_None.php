<?php

namespace SilverStripe\Security;

/**
 * Cleartext passwords (used in SilverStripe 2.1).
 * Also used when Security::$encryptPasswords is set to FALSE.
 * Not recommended.
 */
class PasswordEncryptor_None extends PasswordEncryptor
{
    public function encrypt(string $password, bool $salt = null, SilverStripe\Security\Member $member = null): string|null
    {
        return $password;
    }

    public function salt(string $password, $member = null): bool
    {
        return false;
    }
}
