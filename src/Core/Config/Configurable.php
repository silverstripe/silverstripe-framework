<?php

namespace SilverStripe\Core\Config;

use SilverStripe\Dev\Deprecation;

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
     * Get inherited config value
     *
     * @deprecated 5.0 Use ->config()->get() instead
     * @param string $name
     * @return mixed
     */
    public function stat($name)
    {
        Deprecation::notice('5.0', 'Use ->get');
        return $this->config()->get($name);
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

    /**
     * Update the config value for a given property
     *
     * @deprecated 5.0 Use ->config()->set() instead
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function set_stat($name, $value)
    {
        Deprecation::notice('5.0', 'Use ->config()->set()');
        $this->config()->set($name, $value);
        return $this;
    }
}
