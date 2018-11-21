<?php
namespace SilverStripe\Security\HashMethod;

use SilverStripe\Core\Config\Configurable;

/**
 * The default cryptographic hash method. A "simple" proxy to PHPs provided password hashing API.
 */
class PhpPasswordHash implements HashMethodInterface
{
    use Configurable;

    /**
     * The algorithm to be used with password_hash. Changing this will cause passwords to be upgraded over time to the
     * new setting. This must be set to the value of one of the PHP password algorithm constants.
     *
     * @config
     * @var int
     */
    private static $algorithm = PASSWORD_DEFAULT;

    /**
     * PHPs password_hash function takes various options when it hashes in case the implementor wants or requires
     * stricter password security. This will be directly passed through to password_hash and may also trigger rehashes
     * when these settings change
     *
     * @config
     * @var array
     */
    private static $alogrithm_options = [];

    /**
     * Return a hash of the given plaintext usually with the salt as part of the returned hash
     *
     * @param string $plaintext
     * @return string
     */
    public function hash($plaintext)
    {
        return password_hash(
            $plaintext,
            $this->config()->get('algorithm'),
            (array) $this->config()->get('algorithm_options')
        );
    }

    /**
     * Verify that a given string matches the given hash
     *
     * @param string $plaintext
     * @param string $hash
     * @return boolean
     */
    public function verify($plaintext, $hash)
    {
        return password_verify($plaintext, $hash);
    }

    /**
     * Indicate whether the given hash should be rehashed (because it was using an old algorithm or outdated algorithm
     * settings).
     *
     * @param $hash
     * @return boolean
     */
    public function needsRehash($hash)
    {
        return password_needs_rehash(
            $hash,
            $this->config()->get('algorithm'),
            (array) $this->config()->get('algorithm_options')
        );
    }

    /**
     * Return a short token that uniquely identifies this hash method
     *
     * @return string
     */
    public function identifier()
    {
        return 'phppw';
    }
}
