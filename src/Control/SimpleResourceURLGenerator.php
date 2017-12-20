<?php

namespace SilverStripe\Control;

use InvalidArgumentException;
use PhpParser\Node\Scalar\MagicConst\Dir;
use SilverStripe\Assets\Filesystem;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Manifest\ManifestFileFinder;
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
    private static $url_rewrites = [];

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
        $query = '';
        if ($relativePath instanceof ModuleResource) {
            // Load from module resource
            $resource = $relativePath;
            $relativePath = $resource->getRelativePath();
            $exists = $resource->exists();
            $absolutePath = $resource->getPath();

            // Rewrite to resources with public directory
            if (Director::publicDir()) {
                // All resources mapped directly to resources/
                $relativePath = Filesystem::joinPaths(ManifestFileFinder::RESOURCES_DIR, $relativePath);
            } elseif (stripos($relativePath, ManifestFileFinder::VENDOR_DIR . DIRECTORY_SEPARATOR) === 0) {
                // @todo Non-public dir support will be removed in 5.0, so remove this block there
                // If there is no public folder, map to resources/ but trim leading vendor/ too (4.0 compat)
                $relativePath = Filesystem::joinPaths(
                    ManifestFileFinder::RESOURCES_DIR,
                    substr($relativePath, strlen(ManifestFileFinder::VENDOR_DIR))
                );
            }
        } else {
            // Remove querystring args, normalise path
            if (strpos($relativePath, '?') !== false) {
                list($relativePath, $query) = explode('?', $relativePath);
            }
            $relativePath = Filesystem::normalisePath($relativePath, true);

            // Detect public-only request
            $withPublic = stripos($relativePath, Director::publicDir() . DIRECTORY_SEPARATOR) === 0;
            if ($withPublic) {
                $relativePath = substr($relativePath, strlen(Director::publicDir() . DIRECTORY_SEPARATOR));
            }
            $absolutePath = null;
            $exists = false;
            if (!Director::publicDir()) {
                // @todo Non-public dir support will be removed in 5.0, so remove this block there
                // Throw if public path, but no public dir
                if ($withPublic) {
                    trigger_error('Requesting a public resource without a public folder has no effect', E_USER_WARNING);
                }
                $absolutePath = Filesystem::joinPaths(Director::baseFolder(), $relativePath);
                $exists = file_exists($absolutePath);

                // Rewrite vendor/ to resources/ folder
                if (stripos($relativePath, ManifestFileFinder::VENDOR_DIR . DIRECTORY_SEPARATOR) === 0) {
                    $relativePath = Filesystem::joinPaths(
                        ManifestFileFinder::RESOURCES_DIR,
                        substr($relativePath, strlen(ManifestFileFinder::VENDOR_DIR))
                    );
                }
            } else {
                // Search public folder first, and unless `public/` is prefixed, also private base path
                $publicPath = Filesystem::joinPaths(Director::publicFolder(), $relativePath);
                $privatePath = Filesystem::joinPaths(Director::baseFolder(), $relativePath);
                if (file_exists($publicPath)) {
                    // String is a literal url comitted directly to public folder
                    $absolutePath = $publicPath;
                    $exists = true;
                } elseif (!$withPublic && file_exists($privatePath)) {
                    // String is private but exposed to resources/
                    $absolutePath = $privatePath;
                    $exists = true;
                    $relativePath = Filesystem::joinPaths(ManifestFileFinder::RESOURCES_DIR, $relativePath);
                }
            }
        }
        if (!$exists) {
            trigger_error("File {$relativePath} does not exist", E_USER_NOTICE);
        }

        // Switch slashes for URL
        $relativeURL = Convert::slashes($relativePath, '/');

        // Apply url rewrites
        $rules = Config::inst()->get(static::class, 'url_rewrites') ?: [];
        foreach ($rules as $from => $to) {
            $relativeURL = preg_replace($from, $to, $relativeURL);
        }

        // Apply nonce
        // Don't add nonce to directories
        if ($this->nonceStyle && $exists && is_file($absolutePath)) {
            switch ($this->nonceStyle) {
                case 'mtime':
                    if ($query) {
                        $query .= '&';
                    }
                    $query .= "m=" . filemtime($absolutePath);
                    break;
            }
        }

        // Add back querystring
        if ($query) {
            $relativeURL .= '?' . $query;
        }

        return Director::baseURL() . $relativeURL;
    }
}
