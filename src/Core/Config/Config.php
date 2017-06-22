<?php

namespace SilverStripe\Core\Config;

use InvalidArgumentException;
use SilverStripe\Config\Collections\ConfigCollectionInterface;
use SilverStripe\Config\Collections\MutableConfigCollectionInterface;

abstract class Config
{

    // -- Source options bitmask --

    /**
     * source options bitmask value - only get configuration set for this
     * specific class, not any of it's parents.
     *
     * @const
     */
    const UNINHERITED = 1;

    /**
     * @const source options bitmask value - do not use additional statics
     * sources (such as extension)
     */
    const EXCLUDE_EXTRA_SOURCES = 4;

    /**
     * Get the current active Config instance.
     *
     * In general use you will use this method to obtain the current Config
     * instance. It assumes the config instance has already been set.
     *
     * @return ConfigCollectionInterface
     */
    public static function inst()
    {
        return ConfigLoader::inst()->getManifest();
    }

    /**
     * Make this config available to be modified
     *
     * @return MutableConfigCollectionInterface
     */
    public static function modify()
    {
        $instance = static::inst();
        if ($instance instanceof MutableConfigCollectionInterface) {
            return $instance;
        }

        // By default nested configs should become mutable
        $instance = static::nest();
        if ($instance instanceof MutableConfigCollectionInterface) {
            return $instance;
        }

        throw new InvalidArgumentException("Nested config could not be made mutable");
    }

    /**
     * Make the newly active {@link Config} be a copy of the current active
     * {@link Config} instance.
     *
     * You can then make changes to the configuration by calling update and
     * remove on the new value returned by {@link Config::inst()}, and then discard
     * those changes later by calling unnest.
     *
     * @return ConfigCollectionInterface Active config
     */
    public static function nest()
    {
        // Clone current config and nest
        $new = self::inst()->nest();
        ConfigLoader::inst()->pushManifest($new);
        return $new;
    }

    /**
     * Change the active Config back to the Config instance the current active
     * Config object was copied from.
     *
     * @return ConfigCollectionInterface
     */
    public static function unnest()
    {
        // Unnest unless we would be left at 0 manifests
        $loader = ConfigLoader::inst();
        if ($loader->countManifests() <= 1) {
            user_error(
                "Unable to unnest root Config, please make sure you don't have mis-matched nest/unnest",
                E_USER_WARNING
            );
        } else {
            $loader->popManifest();
        }
        return static::inst();
    }

    /**
     * Get an accessor that returns results by class by default.
     *
     * Shouldn't be overridden, since there might be many Config_ForClass instances already held in the wild. Each
     * Config_ForClass instance asks the current_instance of Config for the actual result, so override that instead
     *
     * @param string $class
     * @return Config_ForClass
     */
    public static function forClass($class)
    {
        return new Config_ForClass($class);
    }
}
