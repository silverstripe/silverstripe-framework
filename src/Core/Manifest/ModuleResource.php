<?php

namespace SilverStripe\Core\Manifest;

use InvalidArgumentException;
use SilverStripe\Core\Injector\Injector;
use Symfony\Component\Filesystem\Path;

/**
 * This object represents a single resource file attached to a module, and can be used
 * as a reference to this to be later turned into either a URL or file path.
 */
class ModuleResource
{
    protected Module $module;

    /**
     * Path to this resource relative to the module (no leading slash)
     */
    protected string $relativePath;

    /**
     * Nested resources for this parent resource
     *
     * @var ModuleResource[]
     */
    protected $resources = [];

    public function __construct(Module $module, string $relativePath)
    {
        $this->module = $module;
        $this->relativePath = trim(Path::normalize($relativePath), '/');
        if (empty($this->relativePath)) {
            throw new InvalidArgumentException("Resource cannot have empty path");
        }
    }

    /**
     * Return the full filesystem path to this resource with no trailing slash..
     *
     * Note: In the case that this resource is mapped to the `_resources` folder, this will
     * return the original rather than the copy / symlink.
     */
    public function getPath(): string
    {
        return Path::join($this->module->getPath(), $this->relativePath);
    }

    /**
     * Get the path of this resource relative to the base path with no trailing or leading slash.
     *
     * Note: In the case that this resource is mapped to the `_resources` folder, this will
     * return the original rather than the copy / symlink.
     */
    public function getRelativePath(): string
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
     */
    public function exists(): bool
    {
        return file_exists($this->getPath() ?? '');
    }

    /**
     * Get relative path
     */
    public function __toString(): string
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
     *
     * @return ModuleResource
     */
    public function getRelativeResource(string $path)
    {
        // Defer to parent module
        $relativeToModule = Path::join($this->relativePath, $path);
        return $this->getModule()->getResource($relativeToModule);
    }
}
