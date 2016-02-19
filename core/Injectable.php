<?php

namespace SilverStripe\Framework\Core;

use Injector;


/**
 * A class that can be instantiated or replaced via DI
 */
trait Injectable {


	/**
	 * An implementation of the factory method, allows you to create an instance of a class
	 *
	 * This method will defer class substitution to the Injector API, which can be customised
	 * via the Config API to declare substitution classes.
	 *
	 * This can be called in one of two ways - either calling via the class directly,
	 * or calling on Object and passing the class name as the first parameter. The following
	 * are equivalent:
	 *    $list = DataList::create('SiteTree');
	 *	  $list = SiteTree::get();
	 *
	 * @param string $class the class name
	 * @param mixed $arguments,... arguments to pass to the constructor
	 * @return static
	 */
	public static function create() {
		$args = func_get_args();

		// Class to create should be the calling class if not Object,
		// otherwise the first parameter
		$class = get_called_class();
		if($class == 'Object') {
			$class = array_shift($args);
		}

		return \Injector::inst()->createWithArgs($class, $args);
	}

	/**
	 * Creates a class instance by the "singleton" design pattern.
	 * It will always return the same instance for this class,
	 * which can be used for performance reasons and as a simple
	 * way to access instance methods which don't rely on instance
	 * data (e.g. the custom SilverStripe static handling).
	 *
	 * @param string $className Optional classname (if called on Object directly)
	 * @return static The singleton instance
	 */
	public static function singleton() {
		$args = func_get_args();

		// Singleton to create should be the calling class if not Object,
		// otherwise the first parameter
		$class = get_called_class();
		if($class === 'Object') {
			$class = array_shift($args);
		}

		return Injector::inst()->get($class);
	}
}
