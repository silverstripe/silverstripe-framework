<?php

namespace SilverStripe\Core\Manifest;

use InvalidArgumentException;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\TemplateGlobalProvider;

/**
 * Helper for mapping module resources to paths / urls
 */
class ModuleResourceLoader implements TemplateGlobalProvider
{
    use Injectable;

    /**
     * Convert a file of the form "vendor/package:resource" into a BASE_PATH-relative file or folder
     * For other files, return original value
     *
     * @param string $resource
     * @return string|null
     */
    public function resolvePath($resource)
    {
        // Skip blank resources
        if (empty($resource)) {
            return null;
        }
        $resourceObj = $this->resolveResource($resource);
        if ($resourceObj instanceof ModuleResource) {
            return $resourceObj->getRelativePath();
        }
        return $resource;
    }

    /**
     * Resolves resource specifier to the given url.
     *
     * @param string $resource
     * @return string|null
     */
    public function resolveURL($resource)
    {
        // Skip blank resources
        if (empty($resource)) {
            return null;
        }

        // Resolve resource to reference
        $resource = $this->resolveResource($resource);

        // Resolve resource to url
        $generator = Injector::inst()->get(ResourceURLGenerator::class);
        return $generator->urlForResource($resource);
    }

    /**
     * Template wrapper for resolvePath
     *
     * @param string $resource
     * @return string|null
     */
    public static function resourcePath($resource)
    {
        return static::singleton()->resolvePath($resource);
    }

    /**
     * Template wrapper for resolveURL
     *
     * @param string $resource
     * @return string|null
     */
    public static function resourceURL($resource)
    {
        return static::singleton()->resolveURL($resource);
    }

    public static function get_template_global_variables()
    {
        return [
            'resourcePath',
            'resourceURL'
        ];
    }

    /**
     * Return module resource for the given path, if specified as one.
     * Returns the original resource otherwise.
     *
     * @param string $resource
     * @return ModuleResource|string The resource (or directory), or input string if not a module resource
     */
    public function resolveResource($resource)
    {
        // String of the form vendor/package:resource. Excludes "http://bla" as that's an absolute URL
        if (!preg_match('#^ *(?<module>[^/: ]+/[^/: ]+) *: *(?<resource>[^ ]*)$#', $resource ?? '', $matches)) {
            return $resource;
        }
        $module = $matches['module'];
        $resource = $matches['resource'];
        $moduleObj = ModuleLoader::getModule($module);
        if (!$moduleObj) {
            throw new InvalidArgumentException("Can't find module '$module', the composer.json file may be missing from the modules installation directory");
        }
        $resourceObj = $moduleObj->getResource($resource);

        return $resourceObj;
    }
}
