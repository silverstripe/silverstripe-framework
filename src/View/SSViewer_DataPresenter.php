<?php

namespace SilverStripe\View;

use InvalidArgumentException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Object;

/**
 * This extends SSViewer_Scope to mix in data on top of what the item provides. This can be "global"
 * data that is scope-independant (like BaseURL), or type-specific data that is layered on top cross-cut like
 * (like $FirstLast etc).
 *
 * It's separate from SSViewer_Scope to keep that fairly complex code as clean as possible.
 */
class SSViewer_DataPresenter extends SSViewer_Scope
{

	private static $globalProperties = null;
	private static $iteratorProperties = null;

	/**
	 * Overlay variables. Take precedence over anything from the current scope
	 * @var array|null
	 */
	protected $overlay;

	/**
	 * Underlay variables. Concede precedence to overlay variables or anything from the current scope
	 * @var array|null
	 */
	protected $underlay;

	public function __construct($item, $overlay = null, $underlay = null, $inheritedScope = null)
	{
		parent::__construct($item, $inheritedScope);

		// Build up global property providers array only once per request
		if (self::$globalProperties === null) {
			self::$globalProperties = array();
			// Get all the exposed variables from all classes that implement the TemplateGlobalProvider interface
			$this->createCallableArray(self::$globalProperties, "SilverStripe\\View\\TemplateGlobalProvider",
				"get_template_global_variables");
		}

		// Build up iterator property providers array only once per request
		if (self::$iteratorProperties === null) {
			self::$iteratorProperties = array();
			// Get all the exposed variables from all classes that implement the TemplateIteratorProvider interface
			// //call non-statically
			$this->createCallableArray(self::$iteratorProperties, "SilverStripe\\View\\TemplateIteratorProvider",
				"get_template_iterator_variables", true);
		}

		$this->overlay = $overlay ? $overlay : array();
		$this->underlay = $underlay ? $underlay : array();
	}

	protected function createCallableArray(&$extraArray, $interfaceToQuery, $variableMethod, $createObject = false)
	{
		$implementers = ClassInfo::implementorsOf($interfaceToQuery);
		if ($implementers) {
			foreach ($implementers as $implementer) {

				// Create a new instance of the object for method calls
				if ($createObject) {
					$implementer = new $implementer();
				}

				// Get the exposed variables
				$exposedVariables = call_user_func(array($implementer, $variableMethod));

				foreach ($exposedVariables as $varName => $details) {
					if (!is_array($details)) {
						$details = array(
							'method' => $details,
							'casting' => Config::inst()->get('SilverStripe\\View\\ViewableData', 'default_cast',
								Config::FIRST_SET)
						);
					}

					// If just a value (and not a key => value pair), use it for both key and value
					if (is_numeric($varName)) {
						$varName = $details['method'];
					}

					// Add in a reference to the implementing class (might be a string class name or an instance)
					$details['implementer'] = $implementer;

					// And a callable array
					if (isset($details['method'])) {
						$details['callable'] = array($implementer, $details['method']);
					}

					// Save with both uppercase & lowercase first letter, so either works
					$lcFirst = strtolower($varName[0]) . substr($varName, 1);
					$extraArray[$lcFirst] = $details;
					$extraArray[ucfirst($varName)] = $details;
				}
			}
		}
	}

	/**
	 * Get the injected value
	 *
	 * @param string $property Name of property
	 * @param array $params
	 * @param bool $cast If true, an object is always returned even if not an object.
	 * @return array Result array with the keys 'value' for raw value, or 'obj' if contained in an object
	 * @throws InvalidArgumentException
	 */
	public function getInjectedValue($property, $params, $cast = true)
	{
		$on = $this->itemIterator ? $this->itemIterator->current() : $this->item;

		// Find the source of the value
		$source = null;

		// Check for a presenter-specific override
		if (array_key_exists($property, $this->overlay)) {
			$source = array('value' => $this->overlay[$property]);
		}
		// Check if the method to-be-called exists on the target object - if so, don't check any further
		// injection locations
		else {
			if (isset($on->$property) || method_exists($on, $property)) {
				$source = null;
			} // Check for a presenter-specific override
			else {
				if (array_key_exists($property, $this->underlay)) {
					$source = array('value' => $this->underlay[$property]);
				} // Then for iterator-specific overrides
				else {
					if (array_key_exists($property, self::$iteratorProperties)) {
						$source = self::$iteratorProperties[$property];
						if ($this->itemIterator) {
							// Set the current iterator position and total (the object instance is the first item in
							// the callable array)
							$source['implementer']->iteratorProperties($this->itemIterator->key(),
								$this->itemIteratorTotal);
						} else {
							// If we don't actually have an iterator at the moment, act like a list of length 1
							$source['implementer']->iteratorProperties(0, 1);
						}
					} // And finally for global overrides
					else {
						if (array_key_exists($property, self::$globalProperties)) {
							$source = self::$globalProperties[$property];  //get the method call
						}
					}
				}
			}
		}

		if ($source) {
			$res = array();

			// Look up the value - either from a callable, or from a directly provided value
			if (isset($source['callable'])) {
				$res['value'] = call_user_func_array($source['callable'], $params);
			} elseif (isset($source['value'])) {
				$res['value'] = $source['value'];
			} else {
				throw new InvalidArgumentException("Injected property $property does't have a value or callable " .
					"value source provided");
			}

			// If we want to provide a casted object, look up what type object to use
			if ($cast) {
				// If the handler returns an object, then we don't need to cast.
				if (is_object($res['value'])) {
					$res['obj'] = $res['value'];
				} else {
					// Get the object to cast as
					$casting = isset($source['casting']) ? $source['casting'] : null;

					// If not provided, use default
					if (!$casting) {
						$casting = Config::inst()->get('SilverStripe\\View\\ViewableData', 'default_cast',
							Config::FIRST_SET);
					}

					$obj = Injector::inst()->get($casting, false, array($property));
					$obj->setValue($res['value']);

					$res['obj'] = $obj;
				}
			}

			return $res;
		}
		return null;
	}

	/**
	 * Store the current overlay (as it doesn't directly apply to the new scope
	 * that's being pushed). We want to store the overlay against the next item
	 * "up" in the stack (hence upIndex), rather than the current item, because
	 * SSViewer_Scope::obj() has already been called and pushed the new item to
	 * the stack by this point
	 * @return SSViewer_Scope
	 */
	public function pushScope()
	{
		$scope = parent::pushScope();
		$upIndex = $this->getUpIndex();

		if ($upIndex !== null) {
			$itemStack = $this->getItemStack();
			$itemStack[$upIndex][SSViewer_Scope::ITEM_OVERLAY] = $this->overlay;

			$this->setItemStack($itemStack);
			$this->overlay = array();
		}

		return $scope;
	}

	/**
	 * Now that we're going to jump up an item in the item stack, we need to
	 * restore the overlay that was previously stored against the next item "up"
	 * in the stack from the current one
	 * @return SSViewer_Scope
	 */
	public function popScope()
	{
		$upIndex = $this->getUpIndex();

		if ($upIndex !== null) {
			$itemStack = $this->getItemStack();
			$this->overlay = $itemStack[$this->getUpIndex()][SSViewer_Scope::ITEM_OVERLAY];
		}

		return parent::popScope();
	}

	/**
	 * $Up and $Top need to restore the overlay from the parent and top-level
	 * scope respectively.
	 *
	 * @param string $name
	 * @param array $arguments
	 * @param bool $cache
	 * @param string $cacheName
	 * @return $this
	 */
	public function obj($name, $arguments = [], $cache = false, $cacheName = null)
	{
		$overlayIndex = false;

		switch ($name) {
			case 'Up':
				$upIndex = $this->getUpIndex();
				if ($upIndex === null) {
					user_error('Up called when we\'re already at the top of the scope', E_USER_ERROR);
				}

				$overlayIndex = $upIndex; // Parent scope
				break;
			case 'Top':
				$overlayIndex = 0; // Top-level scope
				break;
		}

		if ($overlayIndex !== false) {
			$itemStack = $this->getItemStack();
			if (!$this->overlay && isset($itemStack[$overlayIndex][SSViewer_Scope::ITEM_OVERLAY])) {
				$this->overlay = $itemStack[$overlayIndex][SSViewer_Scope::ITEM_OVERLAY];
			}
		}

		return parent::obj($name, $arguments, $cache, $cacheName);
	}

	public function getObj($name, $arguments = [], $cache = false, $cacheName = null)
	{
		$result = $this->getInjectedValue($name, (array)$arguments);
		if ($result) {
			return $result['obj'];
		}
		return parent::getObj($name, $arguments, $cache, $cacheName);
	}

	public function __call($name, $arguments)
	{
		//extract the method name and parameters
		$property = $arguments[0];  //the name of the public function being called

		//the public function parameters in an array
		if (isset($arguments[1]) && $arguments[1] != null) {
			$params = $arguments[1];
		} else {
			$params = array();
		}

		$val = $this->getInjectedValue($property, $params);
		if ($val) {
			$obj = $val['obj'];
			if ($name === 'hasValue') {
				$res = $obj instanceof Object
					? $obj->exists()
					: (bool)$obj;
			} else {
				// XML_val
				$res = $obj->forTemplate();
			}
			$this->resetLocalScope();
			return $res;
		} else {
			return parent::__call($name, $arguments);
		}
	}
}
