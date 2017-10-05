<?php

namespace SilverStripe\Core\Manifest;

use InvalidArgumentException;

/**
 * Generate URLs for client-side assets and perform any preparation of those assets needed.
 */
interface ResourceURLGenerator
{
    /**
     * Return the URL for a given resource within the project.
     *
     * As well as returning the URL, this method may also perform any changes needed to ensure that this
     * URL will resolve, for example, by copying files to another location
     *
     * @param string|ModuleResource $resource File or directory path relative to BASE_PATH, or ModuleResource instance
     * @return string URL, either domain-relative (starting with /) or absolute
     * @throws InvalidArgumentException If the resource doesn't exist or can't be sent to the browser
     */
    public function urlForResource($resource);
}
