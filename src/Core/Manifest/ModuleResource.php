<?php

namespace SilverStripe\Core\Manifest;

use InvalidArgumentException;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Path;

/**
 * This object represents a single resource file attached to a module, and can be used
 * as a reference to this to be later turned into either a URL or file path.
 */
class ModuleResource
{
    /**
     * @var Module
     */
    protected $module = null;

    /**
     * Path to this resource relative to the module (no leading slash)
     *
     * @var string
     */
    protected $relativePath = null;

    /**
     * Nested resources for this parent resource
     *
     * @var ModuleResource[]
     */
    protected $resources = [];

    /**
     * ModuleResource constructor.
     *
     * @param Module $module
     * @param string $relativePath
     */
    public function __construct(Module $module, $relativePath)
    {
        $this->module = $module;
        $this->relativePath = Path::normalise($relativePath, true);
        if (empty($this->relativePath)) {
            throw new InvalidArgumentException("Resource cannot have empty path");
        }
    }

    /**
     * Return the full filesystem path to this resource.
     *
     * Note: In the case that this resource is mapped to the `_resources` folder, this will
     * return the original rather than the copy / symlink.
     *
     * @return string Path with no trailing slash E.g. /var/www/module
     */
    public function getPath()
    {
        return Path::join($this->module->getPath(), $this->relativePath);
    }

    /**
     * Get the path of this resource relative to the base path.
     *
     * Note: In the case that this resource is mapped to the `_resources` folder, this will
     * return the original rather than the copy / symlink.
     *
     * @return string Relative path (no leading /)
     */
    public function getRelativePath()
    {
        // Root module
        $parent = $this->module->getRelativePath();
        if (!$parent) {
            return $this->relativePath;
        }
        return Path::join($parent, $this->relativePath);
    }

    /**
     * Public URL to this resource.
     * Note: May be either absolute url, or root-relative url
     *
     * In the case that this resource is mapped to the `_resources` folder this
     * will be the mapped url rather than the original path.
     *
     * @return string
     */
    public function getURL()
    {
        $generator = Injector::inst()->get(ResourceURLGenerator::class);
        return $generator->urlForResource($this);
    }

    /**
     * Synonym for getURL() for APIs that expect a Link method
     *
     * @return mixed
     */
    public function Link()
    {
        return $this->getURL();
    }

    /**
     * Determine if this resource exists
     *
     * @return bool
     */
    public function exists()
    {
        return file_exists($this->getPath() ?? '');
    }

    /**
     * Get relative path
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getRelativePath();
    }

    /**
     * @return Module
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Get nested resource relative to this.
     * Note: Doesn't support `..` or `.` relative syntax
     *
     * @param string $path
     * @return ModuleResource
     */
    public function getRelativeResource($path)
    {
        // Defer to parent module
        $relativeToModule = Path::join($this->relativePath, $path);
        return $this->getModule()->getResource($relativeToModule);
    }
}
