<?php
/**
 * A ViewableData object is any object that can be rendered into a template/view.
 *
 * A view interrogates the object being currently rendered in order to get data to render into the template. This data
 * is provided and automatically escaped by ViewableData. Any class that needs to be available to a view (controllers,
 * {@link DataObject}s, page controls) should inherit from this class.
 *
 * @package framework
 * @subpackage view
 */
class ViewableData extends Object implements IteratorAggregate {

	/**
	 * An array of objects to cast certain fields to. This is set up as an array in the format:
	 *
	 * <code>
	 * public static $casting = array (
	 *     'FieldName' => 'ClassToCastTo(Arguments)'
	 * );
	 * </code>
	 *
	 * @var array
	 * @config
	 */
	private static $casting = array(
		'CSSClasses' => 'Varchar'
	);

	/**
	 * The default object to cast scalar fields to if casting information is not specified, and casting to an object
	 * is required.
	 *
	 * @var string
	 * @config
	 */
	private static $default_cast = 'Text';

	/**
	 * @var array
	 */
	private static $casting_cache = array();

	// -----------------------------------------------------------------------------------------------------------------

	/**
	 * A failover object to attempt to get data from if it is not present on this object.
	 *
	 * @var ViewableData
	 */
	protected $failover;

	/**
	 * @var ViewableData
	 */
	protected $customisedObject;

	/**
	 * @var array
	 */
	private $objCache = array();

	// -----------------------------------------------------------------------------------------------------------------

	/**
	 * Converts a field spec into an object creator. For example: "Int" becomes "new Int($fieldName);" and "Varchar(50)"
	 * becomes "new Varchar($fieldName, 50);".
	 *
	 * @param string $fieldSchema The field spec
	 * @return string
	 */
	public static function castingObjectCreator($fieldSchema) {
		Deprecation::notice('2.5', 'Use Object::create_from_string() instead');
	}

	/**
	 * Convert a field schema (e.g. "Varchar(50)") into a casting object creator array that contains both a className
	 * and castingHelper constructor code. See {@link castingObjectCreator} for more information about the constructor.
	 *
	 * @param string $fieldSchema
	 * @return array
	 */
	public static function castingObjectCreatorPair($fieldSchema) {
		Deprecation::notice('2.5', 'Use Object::create_from_string() instead');
	}

	// FIELD GETTERS & SETTERS -----------------------------------------------------------------------------------------

	/**
	 * Check if a field exists on this object or its failover.
	 *
	 * @param string $property
	 * @return bool
	 */
	public function __isset($property) {
		return $this->hasField($property) || ($this->failover && $this->failover->hasField($property));
	}

	/**
	 * Get the value of a property/field on this object. This will check if a method called get{$property} exists, then
	 * check if a field is available using {@link ViewableData::getField()}, then fall back on a failover object.
	 *
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property) {
		if($this->hasMethod($method = "get$property")) {
			return $this->$method();
		} elseif($this->hasField($property)) {
			return $this->getField($property);
		} elseif($this->failover) {
			return $this->failover->$property;
		}
	}

	/**
	 * Set a property/field on this object. This will check for the existence of a method called set{$property}, then
	 * use the {@link ViewableData::setField()} method.
	 *
	 * @param string $property
	 * @param mixed $value
	 */
	public function __set($property, $value) {
		if($this->hasMethod($method = "set$property")) {
			$this->$method($value);
		} else {
			$this->setField($property, $value);
		}
	}

	/**
	 * Set a failover object to attempt to get data from if it is not present on this object.
	 *
	 * @param ViewableData $failover
	 */
	public function setFailover(ViewableData $failover) {
		// Ensure cached methods from previous failover are removed
		if ($this->failover) {
			$this->removeMethodsFrom('failover');
		}

		$this->failover = $failover;
		$this->defineMethods();
	}

	/**
	 * Get the current failover object if set
	 *
	 * @return ViewableData|null
	 */
	public function getFailover() {
		return $this->failover;
	}

	/**
	 * Check if a field exists on this object. This should be overloaded in child classes.
	 *
	 * @param string $field
	 * @return bool
	 */
	public function hasField($field) {
		return property_exists($this, $field);
	}

	/**
	 * Get the value of a field on this object. This should be overloaded in child classes.
	 *
	 * @param string $field
	 * @return mixed
	 */
	public function getField($field) {
		return $this->$field;
	}

	/**
	 * Set a field on this object. This should be overloaded in child classes.
	 *
	 * @param string $field
	 * @param mixed $value
	 */
	public function setField($field, $value) {
		$this->$field = $value;
	}

	// -----------------------------------------------------------------------------------------------------------------

	/**
	 * Add methods from the {@link ViewableData::$failover} object, as well as wrapping any methods prefixed with an
	 * underscore into a {@link ViewableData::cachedCall()}.
	 */
	public function defineMethods() {
		if($this->failover) {
			if(is_object($this->failover)) $this->addMethodsFrom('failover');
			else user_error("ViewableData::\$failover set to a non-object", E_USER_WARNING);

			if(isset($_REQUEST['debugfailover'])) {
				Debug::message("$this->class created with a failover class of {$this->failover->class}");
			}
		}

		foreach($this->allMethodNames() as $method) {
			if($method[0] == '_' && $method[1] != '_') {
				$this->createMethod(
					substr($method, 1),
					"return \$obj->deprecatedCachedCall('$method', \$args, '" . substr($method, 1) . "');"
				);
			}
		}

		parent::defineMethods();
	}

	/**
	 * Method to facilitate deprecation of underscore-prefixed methods automatically being cached.
	 *
	 * @param string $field
	 * @param array $arguments
	 * @param string $identifier an optional custom cache identifier
	 * @return unknown
	 */
	public function deprecatedCachedCall($method, $args = null, $identifier = null) {
		Deprecation::notice(
			'4.0',
			'You are calling an underscore-prefixed method (e.g. _mymethod()) without the underscore. This behaviour,
				and the caching logic behind it, has been deprecated.',
			Deprecation::SCOPE_GLOBAL
		);
		return $this->cachedCall($method, $args, $identifier);
	}

	/**
	 * Merge some arbitrary data in with this object. This method returns a {@link ViewableData_Customised} instance
	 * with references to both this and the new custom data.
	 *
	 * Note that any fields you specify will take precedence over the fields on this object.
	 *
	 * @param array|ViewableData $data
	 * @return ViewableData_Customised
	 */
	public function customise($data) {
		if(is_array($data) && (empty($data) || ArrayLib::is_associative($data))) {
			$data = new ArrayData($data);
		}

		if($data instanceof ViewableData) {
			return new ViewableData_Customised($this, $data);
		}

		throw new InvalidArgumentException (
			'ViewableData->customise(): $data must be an associative array or a ViewableData instance'
		);
	}

	/**
	 * @return ViewableData
	 */
	public function getCustomisedObj() {
		return $this->customisedObject;
	}

	/**
	 * @param ViewableData $object
	 */
	public function setCustomisedObj(ViewableData $object) {
		$this->customisedObject = $object;
	}

	// CASTING ---------------------------------------------------------------------------------------------------------

	/**
	 * Get the class a field on this object would be casted to, as well as the casting helper for casting a field to
	 * an object (see {@link ViewableData::castingHelper()} for information on casting helpers).
	 *
	 * The returned array contains two keys:
	 *  - className: the class the field would be casted to (e.g. "Varchar")
	 *  - castingHelper: the casting helper for casting the field (e.g. "return new Varchar($fieldName)")
	 *
	 * @param string $field
	 * @return array
	 */
	public function castingHelperPair($field) {
		Deprecation::notice('2.5', 'use castingHelper() instead');
		return $this->castingHelper($field);
	}

	/**
	 * Return the "casting helper" (a piece of PHP code that when evaluated creates a casted value object) for a field
	 * on this object.
	 *
	 * @param string $field
	 * @return string
	 */
	public function castingHelper($field) {
		if($this->hasMethod('db') && $fieldSpec = $this->db($field)) {
			Deprecation::notice(
				'4.0',
				'ViewableData::castingHelper() will no longer extract casting information "db". Please override
				castingHelper in your ViewableData subclass.',
				Deprecation::SCOPE_GLOBAL
			);
			return $fieldSpec;
		}

		$specs = Config::inst()->get(get_class($this), 'casting');
		if(isset($specs[$field])) return $specs[$field];

		if($this->failover) return $this->failover->castingHelper($field);
	}

	/**
	 * Get the class name a field on this object will be casted to
	 *
	 * @param string $field
	 * @return string
	 */
	public function castingClass($field) {
		$spec = $this->castingHelper($field);
		if(!$spec) return null;

		$bPos = strpos($spec,'(');
		if($bPos === false) return $spec;
		else return substr($spec, 0, $bPos);
	}

	/**
	 * Return the string-format type for the given field.
	 *
	 * @param string $field
	 * @return string 'xml'|'raw'
	 */
	public function escapeTypeForField($field) {
		$class = $this->castingClass($field) ?: $this->config()->default_cast;

		return Config::inst()->get($class, 'escape_type', Config::FIRST_SET);
	}

	/**
	 * Save the casting cache for this object (including data from any failovers) into a variable
	 *
	 * @param reference $cache
	 */
	public function buildCastingCache(&$cache) {
		$ancestry = array_reverse(ClassInfo::ancestry($this->class));
		$merge    = true;

		foreach($ancestry as $class) {
			if(!isset(self::$casting_cache[$class]) && $merge) {
				$mergeFields = is_subclass_of($class, 'DataObject') ? array('db', 'casting') : array('casting');

				if($mergeFields) foreach($mergeFields as $field) {
					$casting = Config::inst()->get($class, $field, Config::UNINHERITED);
					if($casting) foreach($casting as $field => $cast) {
						if(!isset($cache[$field])) $cache[$field] = self::castingObjectCreatorPair($cast);
					}
				}

				if($class == 'ViewableData') $merge = false;
			} elseif($merge) {
				$cache = ($cache) ? array_merge(self::$casting_cache[$class], $cache) : self::$casting_cache[$class];
			}

			if($class == 'ViewableData') break;
		}
	}

	// TEMPLATE ACCESS LAYER -------------------------------------------------------------------------------------------

	/**
	 * Render this object into the template, and get the result as a string. You can pass one of the following as the
	 * $template parameter:
	 *  - a template name (e.g. Page)
	 *  - an array of possible template names - the first valid one will be used
	 *  - an SSViewer instance
	 *
	 * @param string|array|SSViewer $template the template to render into
	 * @param array $customFields fields to customise() the object with before rendering
	 * @return HTMLText
	 */
	public function renderWith($template, $customFields = null) {
		if(!is_object($template)) {
			$template = new SSViewer($template);
		}

		$data = ($this->customisedObject) ? $this->customisedObject : $this;

		if($customFields instanceof ViewableData) {
			$data = $data->customise($customFields);
		}
		if($template instanceof SSViewer) {
			return $template->process($data, is_array($customFields) ? $customFields : null);
		}

		throw new UnexpectedValueException (
			"ViewableData::renderWith(): unexpected $template->class object, expected an SSViewer instance"
		);
	}

	/**
	 * Generate the cache name for a field
	 *
	 * @param string $fieldName Name of field
	 * @param array $arguments List of optional arguments given
	 */
	protected function objCacheName($fieldName, $arguments) {
		return $arguments
			? $fieldName . ":" . implode(',', $arguments)
			: $fieldName;
	}

	/**
	 * Get a cached value from the field cache
	 *
	 * @param string $key Cache key
	 * @return mixed
	 */
	protected function objCacheGet($key) {
		if(isset($this->objCache[$key])) return $this->objCache[$key];
	}

	/**
	 * Store a value in the field cache
	 *
	 * @param string $key Cache key
	 * @param mixed $value
	 */
	protected function objCacheSet($key, $value) {
		$this->objCache[$key] = $value;
	}

	/**
	 * Get the value of a field on this object, automatically inserting the value into any available casting objects
	 * that have been specified.
	 *
	 * @param string $fieldName
	 * @param array $arguments
	 * @param bool $forceReturnedObject if TRUE, the value will ALWAYS be casted to an object before being returned,
	 *        even if there is no explicit casting information
	 * @param bool $cache Cache this object
	 * @param string $cacheName a custom cache name
	 */
	public function obj($fieldName, $arguments = null, $forceReturnedObject = true, $cache = false, $cacheName = null) {
		if(!$cacheName && $cache) $cacheName = $this->objCacheName($fieldName, $arguments);

		$value = $cache ? $this->objCacheGet($cacheName) : null;
		if(!isset($value)) {
			// HACK: Don't call the deprecated FormField::Name() method
			$methodIsAllowed = true;
			if($this instanceof FormField && $fieldName == 'Name') $methodIsAllowed = false;

			if($methodIsAllowed && $this->hasMethod($fieldName)) {
				$value = $arguments ? call_user_func_array(array($this, $fieldName), $arguments) : $this->$fieldName();
			} else {
				$value = $this->$fieldName;
			}

			if(!is_object($value) && ($this->castingClass($fieldName) || $forceReturnedObject)) {
				if(!$castConstructor = $this->castingHelper($fieldName)) {
					$castConstructor = $this->config()->default_cast;
				}

				$valueObject = Object::create_from_string($castConstructor, $fieldName);
				$valueObject->setValue($value, $this);

				$value = $valueObject;
			}

			if($cache) $this->objCacheSet($cacheName, $value);
		}

		if(!is_object($value) && $forceReturnedObject) {
			$default = $this->config()->default_cast;
			$castedValue = new $default($fieldName);
			$castedValue->setValue($value);
			$value = $castedValue;
		}

		return $value;
	}

	/**
	 * A simple wrapper around {@link ViewableData::obj()} that automatically caches the result so it can be used again
	 * without re-running the method.
	 *
	 * @param string $field
	 * @param array $arguments
	 * @param string $identifier an optional custom cache identifier
	 */
	public function cachedCall($field, $arguments = null, $identifier = null) {
		return $this->obj($field, $arguments, false, true, $identifier);
	}

	/**
	 * Checks if a given method/field has a valid value. If the result is an object, this will return the result of the
	 * exists method, otherwise will check if the result is not just an empty paragraph tag.
	 *
	 * @param string $field
	 * @param array $arguments
	 * @param bool $cache
	 * @return bool
	 */
	public function hasValue($field, $arguments = null, $cache = true) {
		$result = $cache ? $this->cachedCall($field, $arguments) : $this->obj($field, $arguments, false, false);

		if(is_object($result) && $result instanceof Object) {
			return $result->exists();
		} else {
			// Empty paragraph checks are a workaround for TinyMCE
			return ($result && $result !== '<p></p>');
		}
	}

	/**#@+
	 * @param string $field
	 * @param array $arguments
	 * @param bool $cache
	 * @return string
	 */

	/**
	 * Get the string value of a field on this object that has been suitable escaped to be inserted directly into a
	 * template.
	 */
	public function XML_val($field, $arguments = null, $cache = false) {
		$result = $this->obj($field, $arguments, false, $cache);
		return (is_object($result) && $result instanceof Object) ? $result->forTemplate() : $result;
	}

	/**
	 * Return the value of the field without any escaping being applied.
	 */
	public function RAW_val($field, $arguments = null, $cache = true) {
		return Convert::xml2raw($this->XML_val($field, $arguments, $cache));
	}

	/**
	 * Return the value of a field in an SQL-safe format.
	 */
	public function SQL_val($field, $arguments = null, $cache = true) {
		return Convert::raw2sql($this->RAW_val($field, $arguments, $cache));
	}

	/**
	 * Return the value of a field in a JavaScript-save format.
	 */
	public function JS_val($field, $arguments = null, $cache = true) {
		return Convert::raw2js($this->RAW_val($field, $arguments, $cache));
	}

	/**
	 * Return the value of a field escaped suitable to be inserted into an XML node attribute.
	 */
	public function ATT_val($field, $arguments = null, $cache = true) {
		return Convert::raw2att($this->RAW_val($field, $arguments, $cache));
	}

	/**#@-*/

	/**
	 * Get an array of XML-escaped values by field name
	 *
	 * @param array $elements an array of field names
	 * @return array
	 */
	public function getXMLValues($fields) {
		$result = array();

		foreach($fields as $field) {
			$result[$field] = $this->XML_val($field);
		}

		return $result;
	}

	// ITERATOR SUPPORT ------------------------------------------------------------------------------------------------

	/**
	 * Return a single-item iterator so you can iterate over the fields of a single record.
	 *
	 * This is useful so you can use a single record inside a <% control %> block in a template - and then use
	 * to access individual fields on this object.
	 *
	 * @return ArrayIterator
	 */
	public function getIterator() {
		return new ArrayIterator(array($this));
	}

	// UTILITY METHODS -------------------------------------------------------------------------------------------------

	/**
	 * When rendering some objects it is necessary to iterate over the object being rendered, to do this, you need
	 * access to itself.
	 *
	 * @return ViewableData
	 */
	public function Me() {
		return $this;
	}

	/**
	 * Return the directory if the current active theme (relative to the site root).
	 *
	 * This method is useful for things such as accessing theme images from your template without hardcoding the theme
	 * page - e.g. <img src="$ThemeDir/images/something.gif">.
	 *
	 * This method should only be used when a theme is currently active. However, it will fall over to the current
	 * project directory.
	 *
	 * @param string $subtheme the subtheme path to get
	 * @return string
	 */
	public function ThemeDir($subtheme = false) {
		if(
			Config::inst()->get('SSViewer', 'theme_enabled')
			&& $theme = Config::inst()->get('SSViewer', 'theme')
		) {
			return THEMES_DIR . "/$theme" . ($subtheme ? "_$subtheme" : null);
		}

		return project();
	}

	/**
	 * Get part of the current classes ancestry to be used as a CSS class.
	 *
	 * This method returns an escaped string of CSS classes representing the current classes ancestry until it hits a
	 * stop point - e.g. "Page DataObject ViewableData".
	 *
	 * @param string $stopAtClass the class to stop at (default: ViewableData)
	 * @return string
	 * @uses ClassInfo
	 */
	public function CSSClasses($stopAtClass = 'ViewableData') {
		$classes       = array();
		$classAncestry = array_reverse(ClassInfo::ancestry($this->class));
		$stopClasses   = ClassInfo::ancestry($stopAtClass);

		foreach($classAncestry as $class) {
			if(in_array($class, $stopClasses)) break;
			$classes[] = $class;
		}

		// optionally add template identifier
		if(isset($this->template) && !in_array($this->template, $classes)) {
			$classes[] = $this->template;
		}

		return Convert::raw2att(implode(' ', $classes));
	}

	/**
	 * Return debug information about this object that can be rendered into a template
	 *
	 * @return ViewableData_Debugger
	 */
	public function Debug() {
		return new ViewableData_Debugger($this);
	}

}

/**
 * @package framework
 * @subpackage view
 */
class ViewableData_Customised extends ViewableData {

	/**
	 * @var ViewableData
	 */
	protected $original, $customised;

	/**
	 * Instantiate a new customised ViewableData object
	 *
	 * @param ViewableData $originalObject
	 * @param ViewableData $customisedObject
	 */
	public function __construct(ViewableData $originalObject, ViewableData $customisedObject) {
		$this->original   = $originalObject;
		$this->customised = $customisedObject;

		$this->original->setCustomisedObj($this);

		parent::__construct();
	}

	public function __call($method, $arguments) {
		if($this->customised->hasMethod($method)) {
			return call_user_func_array(array($this->customised, $method), $arguments);
		}

		return call_user_func_array(array($this->original, $method), $arguments);
	}

	public function __get($property) {
		if(isset($this->customised->$property)) {
			return $this->customised->$property;
		}

		return $this->original->$property;
	}

	public function __set($property, $value) {
		$this->customised->$property = $this->original->$property = $value;
	}

	public function hasMethod($method) {
		return $this->customised->hasMethod($method) || $this->original->hasMethod($method);
	}

	public function cachedCall($field, $arguments = null, $identifier = null) {
		if($this->customised->hasMethod($field) || $this->customised->hasField($field)) {
			$result = $this->customised->cachedCall($field, $arguments, $identifier);
		} else {
			$result = $this->original->cachedCall($field, $arguments, $identifier);
		}

		return $result;
	}

	public function obj($fieldName, $arguments = null, $forceReturnedObject = true, $cache = false, $cacheName = null) {
		if($this->customised->hasField($fieldName) || $this->customised->hasMethod($fieldName)) {
			return $this->customised->obj($fieldName, $arguments, $forceReturnedObject, $cache, $cacheName);
		}

		return $this->original->obj($fieldName, $arguments, $forceReturnedObject, $cache, $cacheName);
	}

}

/**
 * Allows you to render debug information about a {@link ViewableData} object into a template.
 *
 * @package framework
 * @subpackage view
 */
class ViewableData_Debugger extends ViewableData {

	/**
	 * @var ViewableData
	 */
	protected $object;

	/**
	 * @param ViewableData $object
	 */
	public function __construct(ViewableData $object) {
		$this->object = $object;
		parent::__construct();
	}

	/**
	 * @return string The rendered debugger
	 */
	public function __toString() {
		return $this->forTemplate();
	}

	/**
	 * Return debugging information, as XHTML. If a field name is passed, it will show debugging information on that
	 * field, otherwise it will show information on all methods and fields.
	 *
	 * @param string $field the field name
	 * @return string
	 */
	public function forTemplate($field = null) {
		// debugging info for a specific field
		if($field) return "<b>Debugging Information for {$this->class}->{$field}</b><br/>" .
			($this->object->hasMethod($field)? "Has method '$field'<br/>" : null)             .
			($this->object->hasField($field) ? "Has field '$field'<br/>"  : null)             ;

		// debugging information for the entire class
		$reflector = new ReflectionObject($this->object);
		$debug     = "<b>Debugging Information: all methods available in '{$this->object->class}'</b><br/><ul>";

		foreach($this->object->allMethodNames() as $method) {
			// check that the method is public
			if($method[0] === strtoupper($method[0]) && $method[0] != '_') {
				if($reflector->hasMethod($method) && $method = $reflector->getMethod($method)) {
					if($method->isPublic()) {
						$debug .= "<li>\${$method->getName()}";

						if(count($method->getParameters())) {
							$debug .= ' <small>(' . implode(', ', $method->getParameters()) . ')</small>';
						}

						$debug .= '</li>';
					}
				} else {
					$debug .= "<li>\$$method</li>";
				}
			}
		}

		$debug .= '</ul>';

		if($this->object->hasMethod('toMap')) {
			$debug .= "<b>Debugging Information: all fields available in '{$this->object->class}'</b><br/><ul>";

			foreach($this->object->toMap() as $field => $value) {
				$debug .= "<li>\$$field</li>";
			}

			$debug .= "</ul>";
		}

		// check for an extra attached data
		if($this->object->hasMethod('data') && $this->object->data() != $this->object) {
			$debug .= ViewableData_Debugger::create($this->object->data())->forTemplate();
		}

		return $debug;
	}

}
