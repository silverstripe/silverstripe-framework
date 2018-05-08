<?php

namespace SilverStripe\Security;

use Exception;
use SilverStripe\Core\Config\Config;

/**
 * Hashing using built-in password_hash()/password_verify() in PHP
 */
class PasswordEncryptor_PHPPasswordHash extends PasswordEncryptor
{
    /**
     * @var int
     * @config
     */
    private static $algorithm = PASSWORD_DEFAULT;

    /**
     * @var array
     * @config
     */
    private static $options = [];

    public function encrypt($password, $salt = null, $member = null)
    {
        $algorithm = Config::inst()->get(static::class, 'algorithm');
        $options = Config::inst()->get(static::class, 'options');
        return password_hash($password, $algorithm, $options);
    }

    public function check($hash, $password, $salt = null, $member = null)
    {
        return password_verify($password, $hash);
    }

    public function salt($password, $member = null)
    {
        return '';
    }
}
