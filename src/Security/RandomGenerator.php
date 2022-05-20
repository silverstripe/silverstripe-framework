<?php

namespace SilverStripe\Security;

use Error;
use Exception;
use SilverStripe\Dev\Deprecation;

/**
 * Convenience class for generating cryptographically secure pseudo-random strings/tokens
 */
class RandomGenerator
{
    /**
     * @return string A 128-character, randomly generated ASCII string
     * @throws Exception If no suitable CSPRNG is installed
     * @deprecated 4.4.0:5.0.0
     */
    public function generateEntropy()
    {
        Deprecation::notice('4.4', __METHOD__ . ' has been deprecated. Use random_bytes instead');

        try {
            return bin2hex(random_bytes(64));
        } catch (Error $e) {
            throw $e; // This is required so that Error exceptions in PHP 5 aren't caught below
        } catch (Exception $e) {
            throw new Exception(
                'It appears there is no suitable CSPRNG (random number generator) installed. '
                . 'Please review the server requirements documentation: '
                . 'https://docs.silverstripe.org/en/getting_started/server_requirements/'
            );
        }
    }

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
