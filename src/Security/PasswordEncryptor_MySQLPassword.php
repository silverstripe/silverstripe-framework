<?php

namespace SilverStripe\Security;

use SilverStripe\ORM\DB;

/**
 * Uses MySQL's PASSWORD encryption. Requires an active DB connection.
 */
class PasswordEncryptor_MySQLPassword extends PasswordEncryptor
{
    public function encrypt($password, $salt = null, $member = null)
    {
        return DB::prepared_query("SELECT PASSWORD(?)", array($password))->value();
    }

    public function salt($password, $member = null)
    {
        return false;
    }
}
