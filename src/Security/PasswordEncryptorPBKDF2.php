<?php

namespace SilverStripe\Security;

use RuntimeException;

/**
 * Provides Password-Based Key Derivation Function hashing for passwords, using the provided algorithm (default
 * is SHA512), which is NZISM compliant under version 3.2 section 17.2.
 */
class PasswordEncryptorPBKDF2 extends PasswordEncryptor
{
    private string $algorithm = 'sha512';

    /**
     * The number of internal iterations for hash_pbkdf2() to perform for the derivation. Please note that if you
     * change this from the default value you will break existing hashes stored in the database, so these would
     * need to be regenerated.
     */
    private int $iterations = 30000;

    /**
     * @throws RuntimeException If the provided algorithm is not available in the current environment
     */
    public function __construct(string $algorithm, ?int $iterations = null)
    {
        if (!in_array($algorithm, hash_hmac_algos())) {
            throw new RuntimeException(
                sprintf('Hash algorithm "%s" not found in hash_hmac_algos()', $algorithm)
            );
        }

        $this->algorithm = $algorithm;

        if ($iterations !== null) {
            $this->iterations = $iterations;
        }
    }

    /**
     * Get the name of the algorithm that will be used to hash the password
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    /**
     * Get the number of iterations that will be used to hash the password
     */
    public function getIterations(): int
    {
        return $this->iterations;
    }

    public function encrypt($password, $salt = null, $member = null)
    {
        return hash_pbkdf2(
            $this->getAlgorithm() ?? '',
            (string) $password,
            (string) $salt,
            $this->getIterations() ?? 0
        );
    }
}
