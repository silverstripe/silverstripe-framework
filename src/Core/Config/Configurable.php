<?php

namespace SilverStripe\Core\Config;

/**
 * Provides extensions to this object to integrate it with standard config API methods.
 *
 * Note that all classes can have configuration applied to it, regardless of whether it
 * uses this trait.
 */
trait Configurable
{

    /**
     * Get a configuration accessor for this class. Short hand for Config::inst()->get($this->class, .....).
     * @return Config_ForClass
     */
    public static function config()
    {
        return Config::forClass(get_called_class());
    }

    /**
     * Gets the uninherited value for the given config option
     *
     * @param string $name
     * @return mixed
     */
    public function uninherited($name)
    {
        return $this->config()->uninherited($name);
    }
}
