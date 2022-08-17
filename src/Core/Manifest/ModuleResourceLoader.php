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
     * Convert a file of the form "vendor/package:resource" into a BASE_PATH-relative file
     * For other files, return original value
     *
     * @param string $resource
     * @return string
     */
    public function resolvePath(string $resource): string
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
     * @return string
     */
    public function resolveURL(string $resource): string
    {
        // Skip blank resources
        if (empty($resource)) {
            return null;
        }

        // Resolve resource to reference
        $resource = $this->resolveResource($resource);

        // Resolve resource to url
        /** @var ResourceURLGenerator $generator */
        $generator = Injector::inst()->get(ResourceURLGenerator::class);
        return $generator->urlForResource($resource);
    }

    /**
     * Template wrapper for resolvePath
     *
     * @param string $resource
     * @return string
     */
    public static function resourcePath(string $resource): string
    {
        return static::singleton()->resolvePath($resource);
    }

    /**
     * Template wrapper for resolveURL
     *
     * @param string $resource
     * @return string
     */
    public static function resourceURL(string $resource): string
    {
        return static::singleton()->resolveURL($resource);
    }

    public static function get_template_global_variables(): array
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
     * @return ModuleResource|string The resource, or input string if not a module resource
     */
    public function resolveResource(string $resource): string|SilverStripe\Core\Manifest\ModuleResource
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
