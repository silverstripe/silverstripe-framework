<?php

namespace SilverStripe\Security;

use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\DB;

/**
 * Uses MySQL's PASSWORD encryption. Requires an active DB connection.
 *
 * @deprecated 5.2.0 Use another subclass of SilverStripe\Security\PasswordEncryptor instead.
 */
class PasswordEncryptor_MySQLPassword extends PasswordEncryptor
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
        return DB::prepared_query("SELECT PASSWORD(?)", [$password])->value();
    }

    public function salt($password, $member = null)
    {
        return false;
    }
}
