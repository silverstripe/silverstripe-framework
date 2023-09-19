<?php

namespace SilverStripe\Security;

use SilverStripe\Dev\Deprecation;

/**
 * Cleartext passwords (used in SilverStripe 2.1).
 * Not recommended.
 *
 * @deprecated 5.2.0 Use another subclass of SilverStripe\Security\PasswordEncryptor instead.
 */
class PasswordEncryptor_None extends PasswordEncryptor
{
    public function __construct()
    {
        Deprecation::notice(
            '5.2.0',
            'Use another subclass of SilverStripe\Security\PasswordEncryptor instead.',
            Deprecation::SCOPE_CLASS
        );
    }

    public function encrypt($password, $salt = null, $member = null)
    {
        return $password;
    }

    public function salt($password, $member = null)
    {
        return false;
    }
}
