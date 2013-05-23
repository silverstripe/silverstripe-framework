<?php

/**
 * Use the SilverStripe configuration system to lookup config for a 
 * particular service.
 *
 * @package framework
 * @subpackage injector
 */
class SilverStripeServiceConfigurationLocator {
	
	private $configs = array();
	
	public function locateConfigFor($name) {
		
		if (isset($this->configs[$name])) {
			return $this->configs[$name];
		}
		
		$config = Config::inst()->get('Injector', $name);
		if ($config) {
			$this->configs[$name] = $config;
			return $config;
		}
		
		// do parent lookup if it's a class
		if (class_exists($name)) {
			$parents = array_reverse(array_keys(ClassInfo::ancestry($name)));
			array_shift($parents);
			foreach ($parents as $parent) {
				// have we already got for this? 
				if (isset($this->configs[$parent])) {
					return $this->configs[$parent];
				}
				$config = Config::inst()->get('Injector', $parent);
				if ($config) {
					$this->configs[$name] = $config;
					return $config;
				} else {
					$this->configs[$parent] = false;
				}
			}
			
			// there is no parent config, so we'll record that as false so we don't do the expensive
			// lookup through parents again
			$this->configs[$name] = false;
		}
	}
}