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
    public function __construct(string $algorithm): void
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
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function encrypt(string $password, string $salt = null, SilverStripe\Security\Member $member = null): string
    {
        return hash($this->algorithm ?? '', $password . $salt);
    }
}
