<?php

namespace SilverStripe\Core\Injector;

use SilverStripe\Core\Config\Config;

/**
 * Use the SilverStripe configuration system to lookup config for a
 * particular service.
 */
class SilverStripeServiceConfigurationLocator implements ServiceConfigurationLocator
{

    /**
     * List of Injector configurations cached from Config in class => config format.
     * If any config is false, this denotes that this class and all its parents
     * have no configuration specified.
     *
     * @var array
     */
    protected $configs = [];

    public function locateConfigFor($name)
    {
        // Check direct or cached result
        $config = $this->configFor($name);
        if (!$config) {
            return null;
        }

        // If config is in `%$Source` format then inherit from the named config
        if (is_string($config) && stripos($config ?? '', '%$') === 0) {
            $name = substr($config ?? '', 2);
            return $this->locateConfigFor($name);
        }

        // Return the located config
        return $config;
    }

    /**
     * Retrieves the config for a named service without performing a hierarchy walk
     *
     * @param string $name Name of service
     * @return mixed Get config for this service
     */
    protected function configFor($name)
    {
        // Return cached result
        if (array_key_exists($name, $this->configs ?? [])) {
            return $this->configs[$name];
        }

        $config = Config::inst()->get(Injector::class, $name);
        $this->configs[$name] = $config;
        return $config;
    }
}
