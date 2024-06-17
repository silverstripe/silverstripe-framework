<?php

namespace SilverStripe\Core\Injector;

use ArrayObject;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionMethod;
use ReflectionObject;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\DataObject;

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
 *      public $permissions;
 *      public $defaultText;
 *
 *      static $dependencies = array(
 *          'defaultText'       => 'Override in configuration',
 *          'permissions'       => '%$PermissionService',
 *      );
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
 * <code>
 * array(
 *      array(
 *          'id'            => 'BeanId',                    // the name to be used if diff from the filename
 *          'priority'      => 1,                           // priority. If another bean is defined with the same ID,
 *                                                          // but has a lower priority, it is NOT overridden
 *          'class'         => 'ClassName',                 // the name of the PHP class
 *          'src'           => '/path/to/file'              // the location of the class
 *          'type'          => 'singleton|prototype'        // if you want prototype object generation, set it as the
 *                                                          // type
 *                                                          // By default, singleton is assumed
 *
 *          'factory' => 'FactoryService'                   // A factory service to use to create instances.
 *          'construct'     => array(                       // properties to set at construction
 *              'scalar',
 *              '%$BeanId',
 *          )
 *          'properties'    => array(
 *              'name' => 'value'                           // scalar value
 *              'name' => '%$BeanId',                       // a reference to another bean
 *              'name' => array(
 *                  'scalar',
 *                  '%$BeanId'
 *              )
 *          )
 *      )
 *      // alternatively
 *      'MyBean'        => array(
 *          'class'         => 'ClassName',
 *      )
 *      // or simply
 *      'OtherBean'     => 'SomeClass',
 * )
 * </code>
 *
 * In addition to specifying the bindings directly in the configuration,
 * you can simply create a publicly accessible property on the target
 * class which will automatically be injected if the autoScanProperties
 * option is set to true. This means a class defined as
 *
 * <code>
 * class MyController extends Controller {
 *
 *      private $permissionService;
 *
 *      public setPermissionService($p) {
 *          $this->permissionService = $p;
 *      }
 * }
 * </code>
 *
 * will have setPermissionService called if
 *
 * * Injector::inst()->setAutoScanProperties(true) is called and
 * * A service named 'PermissionService' has been configured
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class Injector implements ContainerInterface
{

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
     * The default factory used to create new instances.
     *
     * The {@link InjectionCreator} is used by default, which simply directly
     * creates objects. This can be changed to use a different default creation
     * method if desired.
     *
     * Each individual component can also specify a custom factory to use by
     * using the `factory` parameter.
     *
     * @var Factory
     */
    protected $objectCreator;

    /**
     * Locator for determining Config properties for services
     *
     * @var ServiceConfigurationLocator
     */
    protected $configLocator;

    /**
     * Specify a service type singleton
     */
    const SINGLETON = 'singleton';

    /**
     * Specify a service type prototype
     */
    const PROTOTYPE = 'prototype';

    /**
     * Create a new injector.
     *
     * @param array $config
     *              Service configuration
     */
    public function __construct($config = null)
    {
        $this->injectMap = [];
        $this->serviceCache = [
            'Injector' => $this,
        ];
        $this->specs = [
            'Injector' => ['class' => static::class]
        ];
        $this->autoProperties = [];
        $creatorClass = isset($config['creator'])
            ? $config['creator']
            : InjectionCreator::class;
        $locatorClass = isset($config['locator'])
            ? $config['locator']
            : SilverStripeServiceConfigurationLocator::class;

        $this->objectCreator = new $creatorClass;
        $this->configLocator = new $locatorClass;

        if ($config) {
            $this->load($config);
        }
    }

    /**
     * The injector instance this one was copied from when Injector::nest() was called.
     *
     * @var Injector
     */
    protected $nestedFrom = null;

    /**
     * @return Injector
     */
    public static function inst()
    {
        return InjectorLoader::inst()->getManifest();
    }

    /**
     * Make the newly active {@link Injector} be a copy of the current active
     * {@link Injector} instance.
     *
     * You can then make changes to the injector with methods such as
     * {@link Injector::inst()->registerService()} which will be discarded
     * upon a subsequent call to {@link Injector::unnest()}
     *
     * @return Injector Reference to new active Injector instance
     */
    public static function nest()
    {
        // Clone current injector and nest
        $new = clone Injector::inst();
        InjectorLoader::inst()->pushManifest($new);
        return $new;
    }

    /**
     * Change the active Injector back to the Injector instance the current active
     * Injector object was copied from.
     *
     * @return Injector Reference to restored active Injector instance
     */
    public static function unnest()
    {
        // Unnest unless we would be left at 0 manifests
        $loader = InjectorLoader::inst();
        if ($loader->countManifests() <= 1) {
            user_error(
                "Unable to unnest root Injector, please make sure you don't have mis-matched nest/unnest",
                E_USER_WARNING
            );
        } else {
            $loader->popManifest();
        }
        return static::inst();
    }

    /**
     * Indicate whether we auto scan injected objects for properties to set.
     *
     * @param boolean $val
     */
    public function setAutoScanProperties($val)
    {
        $this->autoScanProperties = $val;
    }

    /**
     * Sets the default factory to use for creating new objects.
     *
     * @param \SilverStripe\Core\Injector\Factory $obj
     */
    public function setObjectCreator(Factory $obj)
    {
        $this->objectCreator = $obj;
    }

    /**
     * @return Factory
     */
    public function getObjectCreator()
    {
        return $this->objectCreator;
    }

    /**
     * Set the configuration locator
     * @param ServiceConfigurationLocator $configLocator
     */
    public function setConfigLocator($configLocator)
    {
        $this->configLocator = $configLocator;
    }

    /**
     * Retrieve the configuration locator
     * @return ServiceConfigurationLocator
     */
    public function getConfigLocator()
    {
        return $this->configLocator;
    }

    /**
     * Add in a specific mapping that should be catered for on a type.
     * This allows configuration of what should occur when an object
     * of a particular type is injected, and what items should be injected
     * for those properties / methods.
     *
     * @param string $class The class to set a mapping for
     * @param string $property The property to set the mapping for
     * @param string $toInject The registered type that will be injected
     * @param string $injectVia Whether to inject by setting a property or calling a setter
     */
    public function setInjectMapping($class, $property, $toInject, $injectVia = 'property')
    {
        $mapping = isset($this->injectMap[$class]) ? $this->injectMap[$class] : [];

        $mapping[$property] = ['name' => $toInject, 'type' => $injectVia];

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
     *                the name of the property
     * @param object $object
     *                the object to be set
     * @return $this
     */
    public function addAutoProperty($property, $object)
    {
        $this->autoProperties[$property] = $object;
        return $this;
    }

    /**
     * Load services using the passed in configuration for those services
     *
     * @param array $config
     * @return $this
     */
    public function load($config = [])
    {
        foreach ($config as $specId => $spec) {
            if (is_string($spec)) {
                $spec = ['class' => $spec];
            }

            $file = isset($spec['src']) ? $spec['src'] : null;

            // class is whatever's explicitly set,
            $class = isset($spec['class']) ? $spec['class'] : null;

            // or the specid if nothing else available.
            if (!$class && is_string($specId)) {
                $class = $specId;
            }

            // make sure the class is set...
            if (empty($class)) {
                throw new InvalidArgumentException('Missing spec class');
            }
            $spec['class'] = $class;

            $id = is_string($specId)
                ? $specId
                : (isset($spec['id']) ? $spec['id'] : $class);

            $priority = isset($spec['priority']) ? $spec['priority'] : 1;

            // see if we already have this defined. If so, check priority weighting
            if (isset($this->specs[$id]) && isset($this->specs[$id]['priority'])) {
                if ($this->specs[$id]['priority'] > $priority) {
                    return $this;
                }
            }

            // okay, actually include it now we know we're going to use it
            if (file_exists($file ?? '')) {
                require_once $file;
            }

            // make sure to set the id for later when instantiating
            // to ensure we get cached
            $spec['id'] = $id;

//          We've removed this check because new functionality means that the 'class' field doesn't need to refer
//          specifically to a class anymore - it could be a compound statement, ala SilverStripe's old Object::create
//          functionality
//
//          if (!class_exists($class)) {
//              throw new Exception("Failed to load '$class' from $file");
//          }

            // store the specs for now - we lazy load on demand later on.
            $this->specs[$id] = $spec;

            // EXCEPT when there's already an existing instance at this id.
            // if so, we need to instantiate and replace immediately
            if (isset($this->serviceCache[$id])) {
                $this->updateSpecConstructor($spec);
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
     *              The name of the service to update the definition for
     * @param string $property
     *              The name of the property to update.
     * @param mixed $value
     *              The value to set
     * @param boolean $append
     *              Whether to append (the default) when the property is an array
     */
    public function updateSpec($id, $property, $value, $append = true)
    {
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
                $this->instantiate(['class'=>$id], $id);
            }
        }
    }

    /**
     * Update a class specification to convert constructor configuration information if needed
     *
     * We do this as a separate process to avoid unneeded calls to convertServiceProperty
     *
     * @param array $spec
     *          The class specification to update
     */
    protected function updateSpecConstructor(&$spec)
    {
        if (isset($spec['constructor'])) {
            $spec['constructor'] = $this->convertServiceProperty($spec['constructor']);
        }
    }

    /**
     * Recursively convert a value into its proper representation with service references
     * resolved to actual objects
     *
     * @param string $value
     * @return array|mixed|string
     */
    public function convertServiceProperty($value)
    {
        if (is_array($value)) {
            $newVal = [];
            foreach ($value as $k => $v) {
                $newVal[$k] = $this->convertServiceProperty($v);
            }
            return $newVal;
        }

        // Evaluate service references
        if (is_string($value) && strpos($value ?? '', '%$') === 0) {
            $id = substr($value ?? '', 2);
            return $this->get($id);
        }

        // Evaluate constants surrounded by back ticks
        $hasBacticks = false;
        $allMissing = true;
        // $value must start and end with backticks, though there can be multiple
        // things being subsituted within $value e.g. "`VAR_ONE`:`VAR_TWO`:`VAR_THREE`"
        if (preg_match('/^`.+`$/', $value ?? '')) {
            $hasBacticks = true;
            preg_match_all('/`(?<name>[^`]+)`/', $value, $matches);
            foreach ($matches['name'] as $name) {
                $envValue = Environment::getEnv($name);
                $val = '';
                if ($envValue !== false) {
                    $val = $envValue;
                } elseif (defined($name)) {
                    $val = constant($name);
                }
                $value = str_replace("`$name`", $val, $value);
                if ($val) {
                    $allMissing = false;
                }
            }
        }
        // silverstripe sometimes explictly expects a null value rather than just an empty string
        if ($hasBacticks && $allMissing && $value === '') {
            return null;
        }

        return $value;
    }

    /**
     * Instantiate a managed object
     *
     * Given a specification of the form
     *
     * array(
     *        'class' => 'ClassName',
     *        'properties' => array('property' => 'scalar', 'other' => '%$BeanRef')
     *        'id' => 'ServiceId',
     *        'type' => 'singleton|prototype'
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
     *                The specification of the class to instantiate
     * @param string $id
     *                The name of the object being created. If not supplied, then the id will be inferred from the
     *                object being created
     * @param string $type
     *                Whether to create as a singleton or prototype object. Allows code to be explicit as to how it
     *                wants the object to be returned
     * @return object
     */
    protected function instantiate($spec, $id = null, $type = null)
    {
        if (is_string($spec)) {
            $spec = ['class' => $spec];
        }
        $class = $spec['class'];

        // create the object, using any constructor bindings
        $constructorParams = [];
        if (isset($spec['constructor']) && is_array($spec['constructor'])) {
            $constructorParams = $spec['constructor'];
        }

        // If we're dealing with a DataObject singleton without specific constructor params, pass through Singleton
        // flag as second argument
        if ((!$type || $type !== Injector::PROTOTYPE)
            && empty($constructorParams)
            && is_subclass_of($class, DataObject::class)) {
            $constructorParams = [null, DataObject::CREATE_SINGLETON];
        }

        if (isset($spec['factory']) && isset($spec['factory_method'])) {
            if (!method_exists($spec['factory'], $spec['factory_method'])) {
                throw new InvalidArgumentException(sprintf(
                    'Factory method "%s::%s" does not exist.',
                    $spec['factory'],
                    $spec['factory_method']
                ));
            }

            // If factory_method is statically callable, do not instantiate
            // factory i.e. just call factory_method statically.
            $factory = is_callable([$spec['factory'], $spec['factory_method']])
                ? $spec['factory']
                : $this->get($spec['factory']);
            $method = $spec['factory_method'];
            $object = call_user_func_array([$factory, $method], $constructorParams);
        } else {
            $factory = isset($spec['factory']) ? $this->get($spec['factory']) : $this->getObjectCreator();
            if (!$factory instanceof Factory) {
                throw new InvalidArgumentException(sprintf(
                    'Factory class "%s" does not implement "%s" interface.',
                    get_class($factory),
                    Factory::class
                ));
            }
            $object = $factory->create($class, $constructorParams);
        }
        if (!is_object($object)) {
            throw new InjectorNotFoundException('Factory does not return an object');
        }

        // Handle empty factory responses
        if (!$object) {
            return null;
        }

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

        if ($id && (!$type || $type !== Injector::PROTOTYPE)) {
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
     *              The object to inject
     * @param string $asType
     *              The ID this item was loaded as. This is so that the property configuration
     *              for a type is referenced correctly in case $object is no longer the same
     *              type as the loaded config specification had it as.
     */
    public function inject($object, $asType = null)
    {
        $objtype = $asType ? $asType : get_class($object);
        $mapping = isset($this->injectMap[$objtype]) ? $this->injectMap[$objtype] : null;

        $spec = empty($this->specs[$objtype]) ? [] : $this->specs[$objtype];

        // first off, set any properties defined in the service specification for this
        // object type
        if (!empty($spec['properties']) && is_array($spec['properties'])) {
            foreach ($this->specs[$objtype]['properties'] as $key => $value) {
                $val = $this->convertServiceProperty($value);
                $this->setObjectProperty($object, $key, $val);
            }
        }

        // Populate named methods
        if (!empty($spec['calls']) && is_array($spec['calls'])) {
            foreach ($spec['calls'] as $method) {
                // Ignore any blank entries from the array; these may be left in due to config system limitations
                if (!$method) {
                    continue;
                }

                // Format validation
                if (!is_array($method) || !isset($method[0]) || isset($method[2])) {
                    throw new InvalidArgumentException(
                        "'calls' entries in service definition should be 1 or 2 element arrays."
                    );
                }
                if (!is_string($method[0])) {
                    throw new InvalidArgumentException("1st element of a 'calls' entry should be a string");
                }
                if (isset($method[1]) && !is_array($method[1])) {
                    throw new InvalidArgumentException("2nd element of a 'calls' entry should an arguments array");
                }

                // Check that the method exists and is callable
                $objectMethod = [$object, $method[0]];
                if (!is_callable($objectMethod)) {
                    throw new InvalidArgumentException("'$method[0]' in 'calls' entry is not a public method");
                }

                // Call it
                call_user_func_array(
                    $objectMethod,
                    $this->convertServiceProperty(
                        isset($method[1]) ? $method[1] : []
                    ) ?? []
                );
            }
        }

        // now, use any cached information about what properties this object type has
        // and set based on name resolution
        if ($mapping === null) {
            // we use an object to prevent array copies if/when passed around
            $mapping = new ArrayObject();

            if ($this->autoScanProperties) {
                // This performs public variable based injection
                $robj = new ReflectionObject($object);
                $properties = $robj->getProperties();

                foreach ($properties as $propertyObject) {
                    /* @var $propertyObject ReflectionProperty */
                    if ($propertyObject->isPublic() && !$propertyObject->getValue($object)) {
                        $origName = $propertyObject->getName();
                        $name = ucfirst($origName ?? '');
                        if ($this->has($name)) {
                            // Pull the name out of the registry
                            $value = $this->get($name);
                            $propertyObject->setValue($object, $value);
                            $mapping[$origName] = ['name' => $name, 'type' => 'property'];
                        }
                    }
                }

                // and this performs setter based injection
                $methods = $robj->getMethods(ReflectionMethod::IS_PUBLIC);

                foreach ($methods as $methodObj) {
                    /* @var $methodObj ReflectionMethod */
                    $methName = $methodObj->getName();
                    if (strpos($methName ?? '', 'set') === 0) {
                        $pname = substr($methName ?? '', 3);
                        if ($this->has($pname)) {
                            // Pull the name out of the registry
                            $value = $this->get($pname);
                            $methodObj->invoke($object, $value);
                            $mapping[$methName] = ['name' => $pname, 'type' => 'method'];
                        }
                    }
                }
            }

            $injections = Config::inst()->get(get_class($object), 'dependencies');
            // If the type defines some injections, set them here
            if ($injections && count($injections ?? [])) {
                foreach ($injections as $property => $value) {
                    // we're checking empty in case it already has a property at this name
                    // this doesn't catch privately set things, but they will only be set by a setter method,
                    // which should be responsible for preventing further setting if it doesn't want it.
                    if (empty($object->$property)) {
                        $convertedValue = $this->convertServiceProperty($value);
                        $this->setObjectProperty($object, $property, $convertedValue);
                        $mapping[$property] = ['service' => $value, 'type' => 'service'];
                    }
                }
            }

            // we store the information about what needs to be injected for objects of this
            // type here
            $this->injectMap[$objtype] = $mapping;
        } else {
            foreach ($mapping as $prop => $propSpec) {
                switch ($propSpec['type']) {
                    case 'property':
                        $value = $this->get($propSpec['name']);
                        $object->$prop = $value;
                        break;


                    case 'method':
                        $method = $prop;
                        $value = $this->get($propSpec['name']);
                        $object->$method($value);
                        break;

                    case 'service':
                        if (empty($object->$prop)) {
                            $value = $this->convertServiceProperty($propSpec['service']);
                            $this->setObjectProperty($object, $prop, $value);
                        }
                        break;

                    default:
                        throw new \LogicException("Bad mapping type: " . $propSpec['type']);
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
     *                  Set an object's property to a specific value
     * @param string $name
     *                  The name of the property to set
     * @param mixed $value
     *                  The value to set
     */
    protected function setObjectProperty($object, $name, $value)
    {
        if (ClassInfo::hasMethod($object, 'set' . $name)) {
            $object->{'set' . $name}($value);
        } else {
            $object->$name = $value;
        }
    }

    /**
     * Does the given service exist?
     *
     * We do a special check here for services that are using compound names. For example,
     * we might want to say that a property should be injected with Log.File or Log.Memory,
     * but have only registered a 'Log' service, we'll instead return that.
     *
     * Will recursively call itself for each depth of dotting.
     *
     */
    public function has(string $name): bool
    {
        return (bool)$this->getServiceName($name);
    }

    /**
     * Does the given service exist, and if so, what's the stored name for it?
     *
     * We do a special check here for services that are using compound names. For example,
     * we might want to say that a property should be injected with Log.File or Log.Memory,
     * but have only registered a 'Log' service, we'll instead return that.
     *
     * Will recursively call itself for each depth of dotting.
     *
     * @param string $name
     * @return string|null The name of the service (as it might be different from the one passed in)
     */
    public function getServiceName($name)
    {
        // Lazy load in spec (disable inheritance to check exact service name)
        if ($this->getServiceSpec($name, false)) {
            return $name;
        }

        // okay, check whether we've got a compound name - don't worry about 0 index, cause that's an
        // invalid name
        if (!strpos($name ?? '', '.')) {
            return null;
        }

        return $this->getServiceName(substr($name ?? '', 0, strrpos($name ?? '', '.')));
    }

    /**
     * Register a service object with an optional name to register it as the
     * service for
     *
     * @param object $service The object to register
     * @param string $replace The name of the object to replace (if different to the
     * class name of the object to register)
     * @return $this
     */
    public function registerService($service, $replace = null)
    {
        $registerAt = get_class($service);
        if ($replace !== null) {
            $registerAt = $replace;
        }

        $this->specs[$registerAt] = ['class' => get_class($service)];
        $this->serviceCache[$registerAt] = $service;
        return $this;
    }

    /**
     * Removes a named object from the cached list of objects managed
     * by the inject
     *
     * @param string $name The name to unregister
     * @return $this
     */
    public function unregisterNamedObject($name)
    {
        unset($this->serviceCache[$name]);
        unset($this->specs[$name]);
        return $this;
    }

    /**
     * Clear out objects of one or more types that are managed by the injetor.
     *
     * @param array|string $types Base class of object (not service name) to remove
     * @return $this
     */
    public function unregisterObjects($types)
    {
        if (!is_array($types)) {
            $types = [ $types ];
        }

        // Filter all objects
        foreach ($this->serviceCache as $key => $object) {
            foreach ($types as $filterClass) {
                // Prevent destructive flushing
                if (strcasecmp($filterClass ?? '', 'object') === 0) {
                    throw new InvalidArgumentException("Global unregistration is not allowed");
                }
                if ($object instanceof $filterClass) {
                    $this->unregisterNamedObject($key);
                    break;
                }
            }
        }
        return $this;
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
     * Failing all of that, will just return a new instance of the specified object.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     *
     * @template T of object
     * @param class-string<T>|string $name The name of the service to retrieve. If not a registered
     * service, then a class of the given name is instantiated
     * @param bool $asSingleton If set to false a new instance will be returned.
     * If true a singleton will be returned unless the spec is type=prototype'
     * @param array $constructorArgs Args to pass in to the constructor. Note: Ignored for singletons
     * @return T|mixed Instance of the specified object
     */
    public function get($name, $asSingleton = true, $constructorArgs = [])
    {
        $object = $this->getNamedService($name, $asSingleton, $constructorArgs);

        if (!$object) {
            throw new InjectorNotFoundException("The '{$name}' service could not be found");
        }

        return $object;
    }

    /**
     * Returns the service, or `null` if it doesnt' exist. See {@link get()} for main usage.
     *
     * @template T of object
     * @param class-string<T>|string $name The name of the service to retrieve. If not a registered
     * service, then a class of the given name is instantiated
     * @param bool $asSingleton If set to false a new instance will be returned.
     * If true a singleton will be returned unless the spec is type=prototype'
     * @param array $constructorArgs Args to pass in to the constructor. Note: Ignored for singletons
     * @return T|mixed Instance of the specified object
     */
    protected function getNamedService($name, $asSingleton = true, $constructorArgs = [])
    {
        // Normalise service / args
        list($name, $constructorArgs) = $this->normaliseArguments($name, $constructorArgs);

        // Resolve name with the appropriate spec, or a suitable mock for new services
        list($name, $spec) = $this->getServiceNamedSpec($name, $constructorArgs);

        // Check if we are getting a prototype or singleton
        $type = $asSingleton
            ? (isset($spec['type']) ? $spec['type'] : Injector::SINGLETON)
            : Injector::PROTOTYPE;

        // Return existing instance for singletons
        if ($type === Injector::SINGLETON && isset($this->serviceCache[$name])) {
            return $this->serviceCache[$name];
        }

        // Update constructor args
        if ($type === Injector::PROTOTYPE && $constructorArgs) {
            // Passed in args are expected to already be normalised (no service references)
            $spec['constructor'] = $constructorArgs;
        } else {
            // Resolve references in constructor args
            $this->updateSpecConstructor($spec);
        }

        // Build instance
        return $this->instantiate($spec, $name, $type);
    }

    /**
     * Detect service references with constructor arguments included.
     * These will be split out of the service name reference and appended
     * to the $args
     *
     * @param string $name
     * @param array $args
     * @return array Two items with name and new args
     */
    protected function normaliseArguments($name, $args = [])
    {
        // Allow service names of the form "%$ServiceName"
        if (substr($name ?? '', 0, 2) == '%$') {
            $name = substr($name ?? '', 2);
        }

        if (strstr($name ?? '', '(')) {
            list($name, $extraArgs) = ClassInfo::parse_class_spec($name);
            if ($args) {
                $args = array_merge($args, $extraArgs);
            } else {
                $args = $extraArgs;
            }
        }
        $name = trim($name ?? '');
        return [$name, $args];
    }

    /**
     * Get or build a named service and specification
     *
     * @param string $name Service name
     * @param array $constructorArgs Optional constructor args
     * @return array
     */
    protected function getServiceNamedSpec($name, $constructorArgs = [])
    {
        $spec = $this->getServiceSpec($name);
        if ($spec) {
            // Resolve to exact service name (in case inherited)
            $name = $this->getServiceName($name);
        } else {
            // Late-generate config spec for non-configured spec
            $spec = [
                'class' => $name,
                'constructor' => $constructorArgs,
            ];
        }
        return [$name, $spec];
    }

    /**
     * Search for spec, lazy-loading in from config locator.
     * Falls back to parent service name if unloaded
     *
     * @param string $name
     * @param bool $inherit Set to true to inherit from parent service if `.` suffixed
     * E.g. 'Psr/Log/LoggerInterface.custom' would fail over to 'Psr/Log/LoggerInterface'
     * @return mixed|object
     */
    public function getServiceSpec($name, $inherit = true)
    {
        if (isset($this->specs[$name])) {
            return $this->specs[$name];
        }

        // Lazy load
        $config = $this->configLocator->locateConfigFor($name);
        if ($config) {
            $this->load([$name => $config]);
            if (isset($this->specs[$name])) {
                return $this->specs[$name];
            }
        }

        // Fail over to parent service if allowed
        if (!$inherit || !strpos($name ?? '', '.')) {
            return null;
        }

        return $this->getServiceSpec(substr($name ?? '', 0, strrpos($name ?? '', '.')));
    }

    /**
     * Magic method to return an item directly
     *
     * @template T of object
     * @param class-string<T>|string $name The named object to retrieve
     * @return T|mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Similar to get() but always returns a new object of the given type
     *
     * Additional parameters are passed through as
     *
     * @template T of object
     * @param class-string<T>|string $name
     * @param mixed ...$argument arguments to pass to the constructor
     * @return T|mixed A new instance of the specified object
     */
    public function create($name, $argument = null)
    {
        $constructorArgs = func_get_args();
        array_shift($constructorArgs);
        return $this->createWithArgs($name, $constructorArgs);
    }

    /**
     * Creates an object with the supplied argument array
     *
     * @template T of object
     * @param class-string<T>|string $name Name of the class to create an object of
     * @param array $constructorArgs Arguments to pass to the constructor
     * @return T|mixed
     */
    public function createWithArgs($name, $constructorArgs)
    {
        return $this->get($name, false, $constructorArgs);
    }
}
