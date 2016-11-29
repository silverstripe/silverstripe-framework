<?php

namespace SilverStripe\Security;

/**
 * Cleartext passwords (used in SilverStripe 2.1).
 * Also used when Security::$encryptPasswords is set to FALSE.
 * Not recommended.
 */
class PasswordEncryptor_None extends PasswordEncryptor
{
    public function encrypt($password, $salt = null, $member = null)
    {
        return $password;
    }

    public function salt($password, $member = null)
    {
        return false;
    }
}
