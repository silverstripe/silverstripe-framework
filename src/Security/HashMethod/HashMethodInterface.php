<?php
namespace SilverStripe\Security\HashMethod;

interface HashMethodInterface
{
    /**
     * Return a hash of the given plaintext usually with the salt as part of the returned hash
     *
     * @param string $plaintext
     * @return string
     */
    public function hash($plaintext);

    /**
     * Verify that a given string matches the given hash
     *
     * @param string $plaintext
     * @param string $hash
     * @return boolean
     */
    public function verify($plaintext, $hash);

    /**
     * Indicate whether the given hash should be rehashed (because it was using an old algorithm or outdated algorithm
     * settings).
     *
     * @param $hash
     * @return boolean
     */
    public function needsRehash($hash);

    /**
     * Return a short token that uniquely identifies this hash method
     *
     * @return string
     */
    public function identifier();
}
