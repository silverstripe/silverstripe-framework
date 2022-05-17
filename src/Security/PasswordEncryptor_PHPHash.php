<?php

namespace SilverStripe\Security;

use Exception;

/**
 * Encryption using built-in hash types in PHP.
 * Please note that the implemented algorithms depend on the PHP
 * distribution and architecture.
 */
class PasswordEncryptor_PHPHash extends PasswordEncryptor
{

    protected $algorithm = 'sha1';

    /**
     * @param string $algorithm A PHP built-in hashing algorithm as defined by hash_algos()
     * @throws Exception
     */
    public function __construct($algorithm)
    {
        if (!in_array($algorithm, hash_algos())) {
            throw new Exception(
                sprintf('Hash algorithm "%s" not found in hash_algos()', $algorithm)
            );
        }

        $this->algorithm = $algorithm;
    }

    /**
     * @return string
     */
    public function getAlgorithm()
    {
        return $this->algorithm;
    }

    public function encrypt($password, $salt = null, $member = null)
    {
        return hash($this->algorithm ?? '', $password . $salt);
    }
}
