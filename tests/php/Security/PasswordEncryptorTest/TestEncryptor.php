<?php

namespace SilverStripe\Security\Tests\PasswordEncryptorTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Security\PasswordEncryptor;

class TestEncryptor extends PasswordEncryptor implements TestOnly
{
    public function encrypt($password, $salt = null, $member = null)
    {
        return 'password';
    }

    public function salt($password, $member = null)
    {
        return 'salt';
    }
}
