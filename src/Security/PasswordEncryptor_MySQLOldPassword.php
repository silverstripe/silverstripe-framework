<?php

namespace SilverStripe\Security;

use SilverStripe\ORM\DB;

/**
 * Uses MySQL's OLD_PASSWORD encyrption. Requires an active DB connection.
 */
class PasswordEncryptor_MySQLOldPassword extends PasswordEncryptor
{
    public function encrypt($password, $salt = null, $member = null)
    {
        return DB::prepared_query("SELECT OLD_PASSWORD(?)", array($password))->value();
    }

    public function salt($password, $member = null)
    {
        return false;
    }
}
