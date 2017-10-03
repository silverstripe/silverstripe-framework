<?php

namespace SilverStripe\Core\Manifest;

use InvalidArgumentException;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\View\TemplateGlobalProvider;

/**
 * Helper for mapping module resources to paths / urls
 */
class ModuleResourceLoader implements TemplateGlobalProvider
{
    use Injectable;

    /**
     * Convert a file of the form "vendor/package:resource" into a BASE_PATH-relative file
     * For other files, return original value
     *
     * @param string $resource
     * @return string
     */
    public function resolvePath($resource)
    {
        $resourceObj = $this->resolveModuleResource($resource);
        if ($resourceObj) {
            return $resourceObj->getRelativePath();
        }
        return $resource;
    }

    /**
     * Resolves resource specifier to the given url.
     *
     * @param string $resource
     * @return string
     */
    public function resolveURL($resource)
    {
        $resourceObj = $this->resolveModuleResource($resource);
        if ($resourceObj) {
            return $resourceObj->getURL();
        }
        return $resource;
    }

    /**
     * Template wrapper for resolvePath
     *
     * @param string $resource
     * @return string
     */
    public static function resourcePath($resource)
    {
        return static::singleton()->resolvePath($resource);
    }

    /**
     * Template wrapper for resolveURL
     *
     * @param string $resource
     * @return string
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
     * Returns null if not a module resource.
     *
     * @param string $resource
     * @return ModuleResource|null
     */
    protected function resolveModuleResource($resource)
    {
        // String of the form vendor/package:resource. Excludes "http://bla" as that's an absolute URL
        if (!preg_match('#(?<module>[^/: ]*/[^/: ]*) *: *(?<resource>[^ ]*)#', $resource, $matches)) {
            return null;
        }
        $module = $matches['module'];
        $resource = $matches['resource'];
        $moduleObj = ModuleLoader::getModule($module);
        if (!$moduleObj) {
            throw new InvalidArgumentException("Can't find module '$module'");
        }
        $resourceObj = $moduleObj->getResource($resource);
        if (!$resourceObj->exists()) {
            throw new InvalidArgumentException("Module '$module' does not have specified resource '$resource'");
        }
        return $resourceObj;
    }
}
