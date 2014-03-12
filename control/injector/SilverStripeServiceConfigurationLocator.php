<?php

/**
 * Use the SilverStripe configuration system to lookup config for a 
 * particular service.
 *
 * @package framework
 * @subpackage injector
 */
class SilverStripeServiceConfigurationLocator extends ServiceConfigurationLocator {
	
	/**
	 * List of Injector configurations cached from Config in class => config format.
	 * If any config is false, this denotes that this class and all its parents 
	 * have no configuration specified.
	 * 
	 * @var array
	 */
	protected $configs = array();
	
	public function locateConfigFor($name) {
		
		// Check direct or cached result
		$config = $this->configFor($name);
		if($config !== null) return $config;
		
		// do parent lookup if it's a class
		if (class_exists($name)) {
			$parents = array_reverse(array_keys(ClassInfo::ancestry($name)));
			array_shift($parents);

			foreach ($parents as $parent) {
				// have we already got for this? 
				$config = $this->configFor($parent);
				if($config !== null) {
					// Cache this result
					$this->configs[$name] = $config;
					return $config;
				}
			}
		}
		
		// there is no parent config, so we'll record that as false so we don't do the expensive
		// lookup through parents again
		$this->configs[$name] = false;
	}
	
	/**
	 * Retrieves the config for a named service without performing a hierarchy walk
	 * 
	 * @param string $name Name of service
	 * @return mixed Returns either the configuration data, if there is any. A missing config is denoted 
	 * by a value of either null (there is no direct config assigned and a hierarchy walk is necessary)
	 * or false (there is no config for this class, nor within the hierarchy for this class). 
	 */
	protected function configFor($name) {
		
		// Return cached result
		if (isset($this->configs[$name])) {
			return $this->configs[$name]; // Potentially false
		}
		
		$config = Config::inst()->get('Injector', $name);
		if ($config) {
			$this->configs[$name] = $config;
			return $config;
		} else {
			return null;
		}
	}
}
