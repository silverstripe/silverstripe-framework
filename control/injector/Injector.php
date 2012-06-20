<?php

/**
 * A simple injection manager that manages creating objects and injecting
 * dependencies between them. It borrows quite a lot from ideas taken from
 * Spring's configuration, but is adapted to the stateless PHP way of doing
 * things. 
 * 
 * In its simplest form, the dependency injector can be used as a mechanism to
 * instantiate objects. Simply call
 * 
 * Injector::inst()->get('ClassName')
 * 
 * and a new instance of ClassName will be created and returned to you. 
 * 
 * Classes can have specific configuration defined for them to 
 * indicate dependencies that should be injected. This takes the form of 
 * a static variable $dependencies defined in the class (or configuration),
 * which indicates the name of a property that should be set. 
 * 
 * eg 
 * 
 * <code>
 * class MyController extends Controller {
 * 
 *		public $permissions;
 *		public $defaultText;
 * 
 *		static $dependencies = array(
 *			'defaultText'		=> 'Override in configuration',
 *			'permissions'		=> '%$PermissionService',
 *		);
 * }
 * </code>
 * 
 * will result in an object of type MyController having the defaultText property
 * set to 'Override in configuration', and an object identified
 * as PermissionService set into the property called 'permissions'. The %$ 
 * syntax tells the injector to look the provided name up as an item to be created
 * by the Injector itself. 
 * 
 * A key concept of the injector is whether to manage the object as
 * 
 * * A pseudo-singleton, in that only one item will be created for a particular
 *   identifier (but the same class could be used for multiple identifiers)
 * * A prototype, where the same configuration is used, but a new object is
 *   created each time
 * * unmanaged, in which case a new object is created and injected, but no 
 *   information about its state is managed.
 * 
 * Additional configuration of items managed by the injector can be done by 
 * providing configuration for the types, either by manually loading in an 
 * array describing the configuration, or by specifying the configuration
 * for a type via SilverStripe's configuration mechanism. 
 *
 * Specify a configuration array of the format
 *
 * array(
 *		array(
 *			'id'			=> 'BeanId',					// the name to be used if diff from the filename
 *			'priority'		=> 1,							// priority. If another bean is defined with the same ID, 
 *															// but has a lower priority, it is NOT overridden
 *			'class'			=> 'ClassName',					// the name of the PHP class
 *			'src'			=> '/path/to/file'				// the location of the class
 *			'type'			=> 'singleton|prototype'		// if you want prototype object generation, set it as the type
 *															// By default, singleton is assumed
 *
 *			'construct'		=> array(						// properties to set at construction
 *				'scalar',									
 *				'%$BeanId',
 *			)
 *			'properties'	=> array(
 *				'name' => 'value'							// scalar value
 *				'name' => '%$BeanId',						// a reference to another bean
 *				'name' => array(
 *					'scalar',
 *					'%$BeanId'
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
 * class which will automatically be injected if the autoScanProperties 
 * option is set to true. This means a class defined as
 * 
 * <code>
 * class MyController extends Controller {
 * 
 *		private $permissionService;
 * 
 *		public setPermissionService($p) {
 *			$this->permissionService = $p;
 *		} 
 * }
 * </code>
 * 
 * will have setPermissionService called if
 * 
 * * Injector::inst()->setAutoScanProperties(true) is called and
 * * A service named 'PermissionService' has been configured 
 * 
 * @author marcus@silverstripe.com.au
 * @package framework
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
	 * A map of all the properties that should be automagically set on all 
	 * objects instantiated by the injector
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
		$this->serviceCache = array(
			'Injector'		=> $this,
		);
		$this->specs = array(
			'Injector'		=> array('class' => 'Injector')
		);
		
		$this->autoProperties = array();
		

		$creatorClass = isset($config['creator']) ? $config['creator'] : 'InjectionCreator';
		$locatorClass = isset($config['locator']) ? $config['locator'] : 'ServiceConfigurationLocator';
		
		$this->objectCreator = new $creatorClass;
		$this->configLocator = new $locatorClass;
		
		if ($config) {
			$this->load($config);
		}
		
		self::$instance = $this;
	}

	/**
	 * If a user wants to use the injector as a static reference
	 *
	 * @param array $config
	 * @return Injector
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
	 * Accessor (for testing purposes)
	 * @return InjectionCreator
	 */
	public function getObjectCreator() {
		return $this->objectCreator;
	}
	
	/**
	 * Set the configuration locator 
	 * @param ServiceConfigurationLocator $configLocator 
	 */
	public function setConfigLocator($configLocator) {
		$this->configLocator = $configLocator;
	}
	
	/**
	 * Retrieve the configuration locator 
	 * @return ServiceConfigurationLocator 
	 */
	public function getConfigLocator() {
		return $this->configLocator;
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
		return $this;
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
			
			// see if we already have this defined. If so, check priority weighting
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
	 * Update the configuration of an already defined service
	 * 
	 * Use this if you don't want to register a complete new config, just append
	 * to an existing configuration. Helpful to avoid overwriting someone else's changes
	 * 
	 * updateSpec('RequestProcessor', 'filters', '%$MyFilter')
	 *
	 * @param string $id
	 *				The name of the service to update the definition for
	 * @param string $property
	 *				The name of the property to update. 
	 * @param mixed $value 
	 *				The value to set
	 * @param boolean $append
	 *				Whether to append (the default) when the property is an array
	 */
	public function updateSpec($id, $property, $value, $append = true) {
		if (isset($this->specs[$id]['properties'][$property])) {
			// by ref so we're updating the actual value
			$current = &$this->specs[$id]['properties'][$property];
			if (is_array($current) && $append) {
				$current[] = $value;
			} else {
				$this->specs[$id]['properties'][$property] = $value;
			}
			
			// and reload the object; existing bindings don't get
			// updated though! (for now...) 
			if (isset($this->serviceCache[$id])) {
				$this->instantiate($spec, $id);
			}
		}
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
		
		if (is_string($value) && strpos($value, '%$') === 0) {
			$id = substr($value, 2);
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
	 *		'properties' => array('property' => 'scalar', 'other' => '%$BeanRef')
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
	 *
	 * @param array $spec
	 *				The specification of the class to instantiate
	 * @param string $id
	 *				The name of the object being created. If not supplied, then the id will be inferred from the
	 *				object being created
	 * @param string $type
	 *				Whether to create as a singleton or prototype object. Allows code to be explicit as to how it
	 *				wants the object to be returned
	 */
	protected function instantiate($spec, $id=null, $type = null) {
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
		if (!$type) {
			$type = isset($spec['type']) ? $spec['type'] : null; 
		}
		
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
	 * @todo Track all the existing objects that have had a service bound
	 * into them, so we can update that binding at a later point if needbe (ie
	 * if the managed service changes)
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
				$this->setObjectProperty($object, $key, $val);
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

		$injections = Config::inst()->get(get_class($object), 'dependencies');
		// If the type defines some injections, set them here
		if ($injections && count($injections)) {
			foreach ($injections as $property => $value) {
				// we're checking isset in case it already has a property at this name
				// this doesn't catch privately set things, but they will only be set by a setter method, 
				// which should be responsible for preventing further setting if it doesn't want it. 
				if (!isset($object->$property)) {
					$value = $this->convertServiceProperty($value);
					$this->setObjectProperty($object, $property, $value);
				}
			}
		}

		foreach ($this->autoProperties as $property => $value) {
			if (!isset($object->$property)) {
				$value = $this->convertServiceProperty($value);
				$this->setObjectProperty($object, $property, $value);
			}
		}

		// Call the 'injected' method if it exists
		if (method_exists($object, 'injected')) {
			$object->injected();
		}
	}

	/**
	 * Helper to set a property's value
	 *
	 * @param object $object
	 *					Set an object's property to a specific value
	 * @param string $name
	 *					The name of the property to set
	 * @param mixed $value 
	 *					The value to set
	 */
	protected function setObjectProperty($object, $name, $value) {
		if (method_exists($object, 'set'.$name)) {
			$object->{'set'.$name}($value);
		} else {
			$object->$name = $value;
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
	 * 
	 * @param stdClass $service
	 *					The object to register
	 * @param string $replace
	 *					The name of the object to replace (if different to the 
	 *					class name of the object to register)
	 * 
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
	 * Removes a named object from the cached list of objects managed
	 * by the inject
	 * 
	 * @param type $name 
	 *				The name to unregister
	 */
	public function unregisterNamedObject($name) {
		unset($this->serviceCache[$name]);
	}

	/**
	 * Clear out all objects that are managed by the injetor. 
	 */
	public function unregisterAllObjects() {
		$this->serviceCache = array('Injector' => $this);
	}
	
	/**
	 * Get a named managed object
	 * 
	 * Will first check to see if the item has been registered as a configured service/bean
	 * and return that if so. 
	 * 
	 * Next, will check to see if there's any registered configuration for the given type
	 * and will then try and load that
	 * 
	 * Failing all of that, will just return a new instance of the 
	 * specificied object.
	 * 
	 * @param string $name 
	 *				the name of the service to retrieve. If not a registered 
	 *				service, then a class of the given name is instantiated
	 * @param boolean $asSingleton
	 *				Whether to register the created object as a singleton
	 *				if no other configuration is found
	 * @param array $constructorArgs
	 *				Optional set of arguments to pass as constructor arguments
	 *				if this object is to be created from scratch 
	 *				(ie asSingleton = false)
	 * 
	 */
	public function get($name, $asSingleton = true, $constructorArgs = null) {
		// reassign the name as it might actually be a compound name
		if ($serviceName = $this->hasService($name)) {
			// check to see what the type of bean is. If it's a prototype,
			// we don't want to return the singleton version of it.
			$spec = $this->specs[$serviceName];
			$type = isset($spec['type']) ? $spec['type'] : null;

			// if we're explicitly a prototype OR we're not wanting a singleton
			if (($type && $type == 'prototype') || !$asSingleton) {
				if ($spec && $constructorArgs) {
					$spec['constructor'] = $constructorArgs;
				}
				return $this->instantiate($spec, $serviceName, !$type ? 'prototype' : $type);
			} else {
				if (!isset($this->serviceCache[$serviceName])) {
					$this->instantiate($spec, $serviceName);
				}
				return $this->serviceCache[$serviceName];
			}
		}
		
		$config = $this->configLocator->locateConfigFor($name);
		if ($config) {
			$this->load(array($name => $config));
			if (isset($this->specs[$name])) {
				$spec = $this->specs[$name];
				return $this->instantiate($spec, $name);
			}
		}

		// If we've got this far, we're dealing with a case of a user wanting 
		// to create an object based on its name. So, we need to fake its config
		// if the user wants it managed as a singleton service style object
		$spec = array('class' => $name, 'constructor' => $constructorArgs);
		if ($asSingleton) {
			// need to load the spec in; it'll be given the singleton type by default
			$this->load(array($name => $spec));
			return $this->instantiate($spec, $name);
		}

		return $this->instantiate($spec);
	}
	
	/**
	 * Magic method to return an item directly
	 * 
	 * @param string $name
	 *				The named object to retrieve
	 * @return mixed
	 */
	public function __get($name) {
		return $this->get($name);
	}

	/**
	 * Similar to get() but always returns a new object of the given type
	 * 
	 * Additional parameters are passed through as 
	 * 
	 * @param type $name 
	 */
	public function create($name) {
		$constructorArgs = func_get_args();
		array_shift($constructorArgs);
		return $this->get($name, false, count($constructorArgs) ? $constructorArgs : null);
	}
	
	/**
	 * Creates an object with the supplied argument array
	 *   
	 * @param string $name
	 *				Name of the class to create an object of
	 * @param array $args
	 *				Arguments to pass to the constructor
	 * @return mixed
	 */
	public function createWithArgs($name, $constructorArgs) {
		return $this->get($name, false, $constructorArgs);
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

/**
 * Used to locate configuration for a particular named service. 
 * 
 * If it isn't found, return null 
 */
class ServiceConfigurationLocator {
	public function locateConfigFor($name) {
		
	}
}

/**
 * Use the SilverStripe configuration system to lookup config for a particular service
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