<?php

namespace SilverStripe\Control;

use InvalidArgumentException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Manifest\ModuleResource;
use SilverStripe\Core\Manifest\ResourceURLGenerator;

/**
 * Generate URLs assuming that BASE_PATH is also the webroot
 * Standard SilverStripe 3 operation
 */
class SimpleResourceURLGenerator implements ResourceURLGenerator
{
    /**
     * Rewrites applied after generating url.
     * Note: requires either silverstripe/vendor-plugin-helper or silverstripe/vendor-plugin
     * to ensure the file is available.
     *
     * @config
     * @var array
     */
    private static $url_rewrites = [
        '#^vendor/#i' => 'resources/',
    ];

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
     * @param string|ModuleResource $relativePath File or directory path relative to BASE_PATH
     * @return string Doman-relative URL
     * @throws InvalidArgumentException If the resource doesn't exist
     */
    public function urlForResource($relativePath)
    {
        if ($relativePath instanceof ModuleResource) {
            // Load from module resource
            $resource = $relativePath;
            $relativePath = $resource->getRelativePath();
            $exists = $resource->exists();
            $absolutePath = $resource->getPath();
        } elseif (Director::is_absolute_url($relativePath)) {
            // Path is not relative, and probably not of this site
            return $relativePath;
        } else {
            // Use normal string
            $absolutePath = preg_replace('/\?.*/', '', Director::baseFolder() . '/' . $relativePath);
            $exists = file_exists($absolutePath);
        }
        if (!$exists) {
            throw new InvalidArgumentException("File {$relativePath} does not exist");
        }

        // Apply url rewrites
        $rules = Config::inst()->get(static::class, 'url_rewrites') ?: [];
        foreach ($rules as $from => $to) {
            $relativePath = preg_replace($from, $to, $relativePath);
        }

        // Apply nonce
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
