<?php

namespace SilverStripe\Core\Injector;

/**
 * Used to locate configuration for a particular named service.
 *
 * If it isn't found, return null.
 */
interface ServiceConfigurationLocator
{

    /**
     * Finds the Injector config for a named service.
     *
     * @param string $name
     * @return mixed
     */
    public function locateConfigFor($name);
}
