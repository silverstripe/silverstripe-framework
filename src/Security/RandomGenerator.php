<?php

namespace SilverStripe\Security;

use Exception;

/**
 * Convenience class for generating cryptographically secure pseudo-random strings/tokens
 */
class RandomGenerator
{
    /**
     * Generates a random token that can be used for session IDs, CSRF tokens etc., based on
     * hash algorithms.
     *
     * If you are using it as a password equivalent (e.g. autologin token) do NOT store it
     * in the database as a plain text but encrypt it with Member::encryptWithUserSettings.
     *
     * @param string $algorithm Any identifier listed in hash_algos() (Default: whirlpool)
     * @return string Returned length will depend on the used $algorithm
     * @throws Exception When there is no valid source of CSPRNG
     */
    public function randomToken($algorithm = 'whirlpool')
    {
        return hash($algorithm ?? '', random_bytes(64));
    }
}
