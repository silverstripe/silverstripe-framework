<?php

namespace SilverStripe\Control;

use InvalidArgumentException;
use SilverStripe\Core\Manifest\ResourceURLGenerator;

/**
 * Generate URLs assuming that BASE_PATH is also the webroot
 * Standard SilverStripe 3 operation
 */
class SimpleResourceURLGenerator implements ResourceURLGenerator
{
    /*
     * @var string
     */
    private $nonceStyle;

    /*
     * Get the style of nonce-suffixes to use, or null if disabled
     *
     * @return string|null
     */
    public function getNonceStyle()
    {
        return $this->nonceStyle;
    }

    /*
     * Set the style of nonce-suffixes to use, or null to disable
     * Currently only "mtime" is allowed
     *
     * @param string|null $nonceStyle The style of nonces to apply, or null to disable
     * @return $this
     */
    public function setNonceStyle($nonceStyle)
    {
        if ($nonceStyle && $nonceStyle !== 'mtime') {
            throw new InvalidArgumentException('The only allowed NonceStyle is mtime');
        }
        $this->nonceStyle = $nonceStyle;
        return $this;
    }

    /**
     * Return the URL for a resource, prefixing with Director::baseURL() and suffixing with a nonce
     *
     * @param string $relativePath File or directory path relative to BASE_PATH
     * @return string Doman-relative URL
     * @throws InvalidArgumentException If the resource doesn't exist
     */
    public function urlForResource($relativePath)
    {
        $absolutePath = preg_replace('/\?.*/', '', Director::baseFolder() . '/' . $relativePath);

        if (!file_exists($absolutePath)) {
            throw new InvalidArgumentException("File {$relativePath} does not exist");
        }

        $nonce = '';
        // Don't add nonce to directories
        if ($this->nonceStyle && is_file($absolutePath)) {
            $nonce = (strpos($relativePath, '?') === false) ? '?' : '&';

            switch ($this->nonceStyle) {
                case 'mtime':
                    $nonce .= "m=" . filemtime($absolutePath);
                    break;
            }
        }

        return Director::baseURL() . $relativePath . $nonce;
    }
}
