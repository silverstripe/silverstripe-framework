<?php

/**
 * A simple injection manager that manages loading beans and injecting
 * dependencies between them. It borrows quite a lot from ideas taken from
 * Spring's configuration.
 *
 * There are two ways to have services managed by Injector; firstly
 * by specifying an explicit configuration array, secondly by annotating
 * various classes and preprocessing them to generate this configuration
 * automatically (@TODO). 
 *
 * Specify a configuration array of the format
 *
 * array(
 *		array(
 *			'id'			=> 'BeanId',					// the name to be used if diff from the filename
 *			'piority'		=> 1,							// priority. If another bean is defined with the same ID, 
 *															// but has a lower priority, it is NOT overridden
 *			'class'			=> 'ClassName',					// the name of the PHP class
 *			'src'			=> '/path/to/file'				// the location of the class
 *			'type'			=> 'singleton|prototype'		// if you want prototype object generation, set it as the type
 *															// By default, singleton is assumed
 *
 *			'construct'		=> array(						// properties to set at construction
 *				'scalar',									
 *				'#$BeanId',
 *			)
 *			'properties'	=> array(
 *				'name' => 'value'							// scalar value
 *				'name' => '#$BeanId',						// a reference to another bean
 *				'name' => array(
 *					'scalar',
 *					'#$BeanId'
 *				)
 *			)
 *		)
 *		// alternatively
 *		'MyBean'		=> array(
 *			'class'			=> 'ClassName',
 *		)
 *		// or simply
 *		'OtherBean'		=> 'SomeClass',
 * )
 *
 * In addition to specifying the bindings directly in the configuration,
 * you can simply create a publicly accessible property on the target
 * class which will automatically be injected.
 * 
 * @author marcus@silverstripe.com.au
 * @package sapphire
 * @subpackage injector
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class Injector {

	/**
	 * Local store of all services
	 *
	 * @var array
	 */
	private $serviceCache;

	/**
	 * Cache of items that need to be mapped for each service that gets injected
	 *
	 * @var array
	 */
	private $injectMap;

	/**
	 * A store of all the service configurations that have been defined.
	 *
	 * @var array
	 */
	private $specs;
	
	/**
	 * A map of all the properties that should be automagically set on a 
	 * service
	 */
	private $autoProperties;

	/**
	 * A singleton if you want to use it that way
	 *
	 * @var Injector
	 */
	private static $instance;
	
	
	/**
	 * Indicates whether or not to automatically scan properties in injected objects to auto inject
	 * stuff, similar to the way grails does things. 
	 *
	 * @var boolean
	 */
	private $autoScanProperties = false;
	
	/**
	 * The object used to create new class instances
	 * 
	 * Use a custom class here to change the way classes are created to use
	 * a custom creation method. By default the InjectionCreator class is used,
	 * which simply creates a new class via 'new', however this could be overridden
	 * to use, for example, SilverStripe's Object::create() method.
	 *
	 * @var InjectionCreator
	 */
	protected $objectCreator;

	/**
	 * Create a new injector. 
	 *
	 * @param array $config
	 *				Service configuration
	 */
	public function __construct($config = null) {
		$this->injectMap = array();
		$this->serviceCache = array();
		$this->autoProperties = array();
		$this->specs = array();

		$creatorClass = isset($config['creator']) ? $config['creator'] : 'InjectionCreator';
		$this->objectCreator = new $creatorClass;

		if ($config) {
			$this->load($config);
		}
		
		self::$instance = $this;
	}

	/**
	 * If a user wants to use the injector as a static reference
	 *
	 * @param array $config
	 */
	public static function inst($config=null) {
		if (!self::$instance) {
			self::$instance = new Injector($config);
		}
		return self::$instance;
	}
	
	/**
	 * Indicate whether we auto scan injected objects for properties to set. 
	 *
	 * @param boolean $val
	 */
	public function setAutoScanProperties($val) {
		$this->autoScanProperties = $val;
	}
	
	/**
	 * Sets the object to use for creating new objects
	 *
	 * @param InjectionCreator $obj 
	 */
	public function setObjectCreator($obj) {
		$this->objectCreator = $obj;
	}
	
	/**
	 * Add in a specific mapping that should be catered for on a type. 
	 * This allows configuration of what should occur when an object
	 * of a particular type is injected, and what items should be injected
	 * for those properties / methods.
	 *
	 * @param type $class
	 *					The class to set a mapping for
	 * @param type $property
	 *					The property to set the mapping for
	 * @param type $injectType 
	 *					The registered type that will be injected
	 * @param string $injectVia
	 *					Whether to inject by setting a property or calling a setter
	 */
	public function setInjectMapping($class, $property, $toInject, $injectVia = 'property') {
		$mapping = isset($this->injectMap[$class]) ? $this->injectMap[$class] : array();
		
		$mapping[$property] = array('name' => $toInject, 'type' => $injectVia);
		
		$this->injectMap[$class] = $mapping;
	}
	
	/**
	 * Add an object that should be automatically set on managed objects
	 *
	 * This allows you to specify, for example, that EVERY managed object
	 * will be automatically inject with a log object by the following
	 *
	 * $injector->addAutoProperty('log', new Logger());
	 *
	 * @param string $property
	 *				the name of the property
	 * @param object $object
	 *				the object to be set
	 */
	public function addAutoProperty($property, $object) {
		$this->autoProperties[$property] = $object;
	}

	/**
	 * Load services using the passed in configuration for those services
	 *
	 * @param array $config
	 */
	public function load($config = array()) {
		$services = array();

		foreach ($config as $specId => $spec) {
			if (is_string($spec)) {
				$spec = array('class' => $spec);
			}

			$file = isset($spec['src']) ? $spec['src'] : null; 
			$name = null;

			if (file_exists($file)) {
				$filename = basename($file);
				$name = substr($filename, 0, strrpos($filename, '.'));
			}

			// class is whatever's explicitly set, 
			$class = isset($spec['class']) ? $spec['class'] : $name;
			
			// or the specid if nothing else available.
			if (!$class && is_string($specId)) {
				$class = $specId;
			}
			
			// make sure the class is set...
			$spec['class'] = $class;

			$id = is_string($specId) ? $specId : (isset($spec['id']) ? $spec['id'] : $class); 
			
			$priority = isset($spec['priority']) ? $spec['priority'] : 1;
			
			// see if we already have this defined. If so, check 
			// priority weighting
			if (isset($this->specs[$id]) && isset($this->specs[$id]['priority'])) {
				if ($this->specs[$id]['priority'] > $priority) {
					return;
				}
			}

			// okay, actually include it now we know we're going to use it
			if (file_exists($file)) {
				require_once $file;
			}

			// make sure to set the id for later when instantiating
			// to ensure we get cached
			$spec['id'] = $id;

//			We've removed this check because new functionality means that the 'class' field doesn't need to refer
//			specifically to a class anymore - it could be a compound statement, ala SilverStripe's old Object::create
//			functionality
//			
//			if (!class_exists($class)) {
//				throw new Exception("Failed to load '$class' from $file");
//			}

			// store the specs for now - we lazy load on demand later on. 
			$this->specs[$id] = $spec;

			// EXCEPT when there's already an existing instance at this id.
			// if so, we need to instantiate and replace immediately
			if (isset($this->serviceCache[$id])) {
				$this->instantiate($spec, $id);
			}
		}

		return $this;
	}

	/**
	 * Recursively convert a value into its proper representation with service references
	 * resolved to actual objects
	 *
	 * @param string $value 
	 */
	public function convertServiceProperty($value) {
		if (is_array($value)) {
			$newVal = array();
			foreach ($value as $k => $v) {
				$newVal[$k] = $this->convertServiceProperty($v);
			}
			return $newVal;
		}
		
		if (is_string($value) && strpos($value, '#$') === 0) {
			$id = substr($value, 2);
			if (!$this->hasService($id)) {
				throw new Exception("Undefined service $id for property when trying to resolve property");
			}
			return $this->get($id);
		}
		return $value;
	}

	/**
	 * Instantiate a managed object
	 *
	 * Given a specification of the form
	 *
	 * array(
	 *		'class' => 'ClassName',
	 *		'properties' => array('property' => 'scalar', 'other' => '#$BeanRef')
	 *		'id' => 'ServiceId',
	 *		'type' => 'singleton|prototype'
	 * )
	 *
	 * will create a new object, store it in the service registry, and
	 * set any relevant properties
	 *
	 * Optionally, you can pass a class name directly for creation
	 * 
	 * To access this from the outside, you should call ->get('Name') to ensure
	 * the appropriate checks are made on the specific type. 
	 *
	 * @param array $spec
	 *				The specification of the class to instantiate
	 */
	protected function instantiate($spec, $id=null) {
		if (is_string($spec)) {
			$spec = array('class' => $spec);
		}
		$class = $spec['class'];
		
		// create the object, using any constructor bindings
		$constructorParams = array();
		if (isset($spec['constructor']) && is_array($spec['constructor'])) {
			$constructorParams = $spec['constructor'];
		}

		$object = $this->objectCreator->create($this, $class, $constructorParams);
		
		// figure out if we have a specific id set or not. In some cases, we might be instantiating objects
		// that we don't manage directly; we don't want to store these in the service cache below
		if (!$id) {
			$id = isset($spec['id']) ? $spec['id'] : null;
		}

		// now set the service in place if needbe. This is NOT done for prototype beans, as they're
		// created anew each time
		$type = isset($spec['type']) ? $spec['type'] : null; 
		if ($id && (!$type || $type != 'prototype')) {
			// this ABSOLUTELY must be set before the object is injected.
			// This prevents circular reference errors down the line
			$this->serviceCache[$id] = $object;
		}

		// now inject safely
		$this->inject($object, $id);

		return $object;
	}

	/**
	 * Inject $object with available objects from the service cache
	 *
	 * @param object $object
	 *				The object to inject
	 * @param string $asType
	 *				The ID this item was loaded as. This is so that the property configuration
	 *				for a type is referenced correctly in case $object is no longer the same
	 *				type as the loaded config specification had it as. 
	 */
	public function inject($object, $asType=null) {
		$objtype = $asType ? $asType : get_class($object);
		$mapping = isset($this->injectMap[$objtype]) ? $this->injectMap[$objtype] : null;
		
		// first off, set any properties defined in the service specification for this
		// object type
		if (isset($this->specs[$objtype]) && isset($this->specs[$objtype]['properties'])) {
			foreach ($this->specs[$objtype]['properties'] as $key => $value) {
				$val = $this->convertServiceProperty($value);
				if (method_exists($object, 'set'.$key)) {
					$object->{'set'.$key}($val);
				} else {
					$object->$key = $val;
				}
			}
		}

		// now, use any cached information about what properties this object type has
		// and set based on name resolution
		if (!$mapping) {
			if ($this->autoScanProperties) {
				// we use an object to prevent array copies if/when passed around
				$mapping = new ArrayObject();

				// This performs public variable based injection
				$robj = new ReflectionObject($object);
				$properties = $robj->getProperties();

				foreach ($properties as $propertyObject) {
					/* @var $propertyObject ReflectionProperty */
					if ($propertyObject->isPublic() && !$propertyObject->getValue($object)) {
						$origName = $propertyObject->getName();
						$name = ucfirst($origName);
						if ($this->hasService($name)) {
							// Pull the name out of the registry
							$value = $this->get($name);
							$propertyObject->setValue($object, $value);
							$mapping[$origName] = array('name' => $name, 'type' => 'property');
						}
					}
				}

				// and this performs setter based injection
				$methods = $robj->getMethods(ReflectionMethod::IS_PUBLIC);

				foreach ($methods as $methodObj) {
					/* @var $methodObj ReflectionMethod */
					$methName = $methodObj->getName();
					if (strpos($methName, 'set') === 0) {
						$pname = substr($methName, 3);
						if ($this->hasService($pname)) {
							// Pull the name out of the registry
							$value = $this->get($pname);
							$methodObj->invoke($object, $value);
							$mapping[$methName] = array('name' => $pname, 'type' => 'method');
						}
					}
				}

				// we store the information about what needs to be injected for objects of this
				// type here
				$this->injectMap[get_class($object)] = $mapping;
			}
		} else {
			foreach ($mapping as $prop => $spec) {
				if ($spec['type'] == 'property') {
					$value = $this->get($spec['name']);
					$object->$prop = $value;
				} else {
					$method = $prop;
					$value = $this->get($spec['name']);
					$object->$method($value);
				}
			}
		}
		
//		disabled static injections for now
//		if (isset($class::$injections)) {
//			foreach ($class::$injections as $key => $val) {
//				$props[$key] = $val;
//			}
//		}
//		
		
		foreach ($this->autoProperties as $property => $value) {
			if (!isset($object->$property)) {
				$value = $this->convertServiceProperty($value);
				$object->$property = $value;
			}
		}

		// Call the 'injected' method if it exists
		if (method_exists($object, 'injected')) {
			$object->injected();
		}
	}

	/**
	 * Does the given service exist, and if so, what's the stored name for it?
	 * 
	 * We do a special check here for services that are using compound names. For example, 
	 * we might want to say that a property should be injected with Log.File or Log.Memory,
	 * but have only registered a 'Log' service, we'll instead return that. 
	 * 
	 * Will recursively call hasService for each depth of dotting
	 * 
	 * @return string 
	 *				The name of the service (as it might be different from the one passed in)
	 */
	public function hasService($name) {
		// common case, get it overwith first
		if (isset($this->specs[$name])) {
			return $name;
		}
		
		// okay, check whether we've got a compound name - don't worry about 0 index, cause that's an 
		// invalid name
		if (!strpos($name, '.')) {
			return null;
		}
		
		return $this->hasService(substr($name, 0, strrpos($name, '.')));
	}

	/**
	 * Register a service object with an optional name to register it as the
	 * service for
	 */
	public function registerService($service, $replace=null) {
		$registerAt = get_class($service);
		if ($replace != null) {
			$registerAt = $replace;
		}
		
		$this->serviceCache[$registerAt] = $service;
		$this->inject($service);
	}
	
	/**
	 * Register a service with an explicit name
	 */
	public function registerNamedService($name, $service) {
		$this->serviceCache[$name] = $service;
		$this->inject($service);
	}
	
	/**
	 * Get a named managed object
	 * 
	 * @param $name the name of the service to retrieve
	 */
	public function get($name) {
		// reassign the name as it might actually be a compound name
		if ($serviceName = $this->hasService($name)) {
			// check to see what the type of bean is. If it's a prototype,
			// we don't want to return the singleton version of it.
			$spec = $this->specs[$serviceName];
			$type = isset($spec['type']) ? $spec['type'] : null;

			if ($type && $type == 'prototype') {
				return $this->instantiate($spec, $serviceName);
			} else {
				if (!isset($this->serviceCache[$serviceName])) {
					$this->instantiate($spec, $serviceName);
				}
				return $this->serviceCache[$serviceName];
			}
		}
		
		// If no specific config for this object, we'll just return a new instance
		// of the object, which means it'll get instantiated and injected appropriately
		return $this->instantiate($name);
	}
}

/**
 * A class for creating new objects by the injector
 */
class InjectionCreator {
	/**
	 *
	 * @param string $object
	 *					A string representation of the class to create
	 * @param array $params
	 *					An array of parameters to be passed to the constructor
	 */
	public function create(Injector $injector, $class, $params = array()) {
		$reflector = new ReflectionClass($class);
		if (count($params)) {
			return $reflector->newInstanceArgs($injector->convertServiceProperty($params));
		}
		return $reflector->newInstance();
	}
}

class SilverStripeInjectionCreator {
	/**
	 *
	 * @param string $object
	 *					A string representation of the class to create
	 * @param array $params
	 *					An array of parameters to be passed to the constructor
	 */
	public function create(Injector $injector, $class, $params = array()) {
		$class = Object::getCustomClass($class);
		$reflector = new ReflectionClass($class);
		return $reflector->newInstanceArgs($injector->convertServiceProperty($params));
	}
}