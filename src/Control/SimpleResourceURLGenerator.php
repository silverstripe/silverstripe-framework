<?php

namespace SilverStripe\Control;

use InvalidArgumentException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Manifest\ManifestFileFinder;
use SilverStripe\Core\Manifest\ModuleResource;
use SilverStripe\Core\Manifest\ResourceURLGenerator;
use Symfony\Component\Filesystem\Path;

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
        if ($nonceStyle && !in_array($nonceStyle, ['mtime', 'sha1', 'md5'])) {
            throw new InvalidArgumentException("NonceStyle '$nonceStyle' is not supported");
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
            list($exists, $absolutePath, $relativePath) = $this->resolveModuleResource($relativePath);
        } elseif (Director::is_absolute_url($relativePath)) {
            // Path is not relative, and probably not of this site
            return $relativePath;
        } else {
            // Save querystring for later
            if (strpos($relativePath ?? '', '?') !== false) {
                list($relativePath, $query) = explode('?', $relativePath ?? '');
            }

            list($exists, $absolutePath, $relativePath) = $this->resolvePublicResource($relativePath);
        }
        if (!$exists) {
            trigger_error("File {$relativePath} does not exist", E_USER_NOTICE);
        }

        // Switch slashes for URL
        $relativeURL = Convert::slashes($relativePath, '/');

        // Apply url rewrites
        $rules = Config::inst()->get(static::class, 'url_rewrites') ?: [];
        foreach ($rules as $from => $to) {
            $relativeURL = preg_replace($from ?? '', $to ?? '', $relativeURL ?? '');
        }

        // Apply nonce
        // Don't add nonce to directories
        if ($this->nonceStyle && $exists && is_file($absolutePath ?? '')) {
            switch ($this->nonceStyle) {
                case 'mtime':
                    $method = 'filemtime';
                    break;
                case 'sha1':
                    $method = 'sha1_file';
                    break;
                case 'md5':
                    $method = 'md5_file';
                    break;
            }

            if ($query) {
                $query .= '&';
            }
            $query .= "m=" . call_user_func($method, $absolutePath);
        }

        // Add back querystring
        if ($query) {
            $relativeURL .= '?' . $query;
        }

        return Director::baseURL() . $relativeURL;
    }

    /**
     * Update relative path for a module resource
     *
     * @param ModuleResource $resource
     * @return array List of [$exists, $absolutePath, $relativePath]
     */
    protected function resolveModuleResource(ModuleResource $resource)
    {
        // Load from module resource
        $relativePath = $resource->getRelativePath();
        $exists = $resource->exists();
        $absolutePath = $resource->getPath();

        // All resources mapped directly to public/_resources/
        $relativePath = Path::join(RESOURCES_DIR, $relativePath);
        return [$exists, $absolutePath, $relativePath];
    }

    /**
     * Determine if the requested $relativePath requires a public-only resource.
     * An error will occur if this file isn't immediately available in the public/ assets folder.
     *
     * @param string $relativePath Requested relative path which may have a public/ prefix.
     * This prefix will be removed if exists. This path will also be normalised to match DIRECTORY_SEPARATOR
     * @return bool True if the resource must be a public resource
     */
    protected function inferPublicResourceRequired(&$relativePath)
    {
        // Normalise path
        $relativePath = Path::normalize($relativePath);

        // Detect public-only request
        $publicOnly = stripos($relativePath ?? '', 'public' . DIRECTORY_SEPARATOR) === 0;
        if ($publicOnly) {
            $relativePath = substr($relativePath ?? '', strlen(Director::publicDir() . DIRECTORY_SEPARATOR));
        }

        // Trim slashes
        $relativePath = trim($relativePath, '/');

        return $publicOnly;
    }

    /**
     * Resolve a resource that may either exist in a public/ folder, or be exposed from the base path to
     * public/_resources/
     *
     * @param string $relativePath
     * @return array List of [$exists, $absolutePath, $relativePath]
     */
    protected function resolvePublicResource($relativePath)
    {
        // Determine if we should search both public and base resources, or only public
        $publicOnly = $this->inferPublicResourceRequired($relativePath);
        $publicDir = Director::publicFolder();

        // Search public folder first, and unless `public/` is prefixed, also private base path
        $publicPath = Path::join($publicDir, $relativePath);
        if (file_exists($publicPath ?? '')) {
            if (!Path::isBasePath($publicDir, $publicPath)) {
                throw new InvalidArgumentException("'$relativePath' must not be outside the public root");
            }
            // String is a literal url committed directly to public folder
            return [true, $publicPath, $relativePath];
        }

        // Fall back to private path (and assume expose will make this available to _resources/)
        $privatePath = Path::join(Director::baseFolder(), $relativePath);
        if (!$publicOnly && file_exists($privatePath ?? '')) {
            // String is private but exposed to _resources/, so rewrite to the symlinked base
            $relativePath = Path::join(RESOURCES_DIR, $relativePath);
            if (!Path::isBasePath(RESOURCES_DIR, $relativePath)) {
                throw new InvalidArgumentException("'$relativePath' must not be outside the resources root");
            }
            if (!Path::isBasePath(Director::baseFolder(), $privatePath)) {
                throw new InvalidArgumentException("'$privatePath' must not be outside the project root");
            }
            return [true, $privatePath, $relativePath];
        }

        // File doesn't exist, fail
        return [false, null, $relativePath];
    }
}
