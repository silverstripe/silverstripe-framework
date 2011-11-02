<?php
/**
 * A ViewableData object is any object that can be rendered into a template/view.
 *
 * A view interrogates the object being currently rendered in order to get data to render into the template. This data
 * is provided and automatically escaped by ViewableData. Any class that needs to be available to a view (controllers,
 * {@link DataObject}s, page controls) should inherit from this class.
 *
 * @package sapphire
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
	 */
	public static $casting = array (
		'BaseHref'   => 'Varchar',
		'CSSClasses' => 'Varchar'
	);
	
	/**
	 * The default object to cast scalar fields to if casting information is not specified, and casting to an object
	 * is required.
	 *
	 * @var string
	 */
	public static $default_cast = 'HTMLVarchar';
	
	/**
	 * @var array
	 */
	private static $casting_cache = array();
	
	// -----------------------------------------------------------------------------------------------------------------
	
	/**
	 * @var int
	 */
	protected $iteratorPos, $iteratorTotalItems;
	
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
		user_error("Deprecated in a breaking way, use Object::create_from_string()", E_USER_WARNING);
	}
	
	/**
	 * Convert a field schema (e.g. "Varchar(50)") into a casting object creator array that contains both a className
	 * and castingHelper constructor code. See {@link castingObjectCreator} for more information about the constructor.
	 *
	 * @param string $fieldSchema
	 * @return array
	 */
	public static function castingObjectCreatorPair($fieldSchema) {
		user_error("Deprecated in a breaking way, use Object::create_from_string()", E_USER_WARNING);
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
				$this->createMethod (
					substr($method, 1), "return \$obj->cachedCall('$method', '" . substr($method, 1) . "', \$args);"
				);
			}
		}
		
		parent::defineMethods();
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
		user_error("castingHelperPair() Deprecated, use castingHelper() instead", E_USER_NOTICE);
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
			return $fieldSpec;
		}

		$specs = Object::combined_static(get_class($this), 'casting');
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
		if(!$class = $this->castingClass($field)) {
			$class = self::$default_cast;
		}
		
		return Object::get_static($class, 'escape_type');
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
					$casting = Object::uninherited_static($class, $field);
					
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
	 * @return string
	 */
	public function renderWith($template, $customFields = null) {
		if(!is_object($template)) {
			$template = new SSViewer($template);
		}
		
		$data = ($this->customisedObject) ? $this->customisedObject : $this;
		
		if(is_array($customFields) || $customFields instanceof ViewableData) {
			$data = $data->customise($customFields);
		}
		
		if($template instanceof SSViewer) {
			return $template->process($data);
		}
		
		throw new UnexpectedValueException (
			"ViewableData::renderWith(): unexpected $template->class object, expected an SSViewer instance"
		);
	}
	
	/**
	 * Get the value of a field on this object, automatically inserting the value into any available casting objects
	 * that have been specified.
	 *
	 * @param string $fieldName
	 * @param array $arguments
	 * @param bool $forceReturnedObject if TRUE, the value will ALWAYS be casted to an object before being returned,
	 *        even if there is no explicit casting information
	 * @param string $cacheName a custom cache name
	 */
	public function obj($fieldName, $arguments = null, $forceReturnedObject = true, $cache = false, $cacheName = null) {
		if(isset($_REQUEST['debug_profile'])) {
			Profiler::mark("obj.$fieldName", "on a $this->class object");
		}
		
		if(!$cacheName) $cacheName = $arguments ? $fieldName . implode(',', $arguments) : $fieldName;
		
		if(!isset($this->objCache[$cacheName])) {
			if($this->hasMethod($fieldName)) {
				$value = $arguments ? call_user_func_array(array($this, $fieldName), $arguments) : $this->$fieldName();
			} else {
				$value = $this->$fieldName;
			}
			
			if(!is_object($value) && ($this->castingClass($fieldName) || $forceReturnedObject)) {
				if(!$castConstructor = $this->castingHelper($fieldName)) {
					$castConstructor = $this->stat('default_cast');
				}
				
				$valueObject = Object::create_from_string($castConstructor, $fieldName);
				$valueObject->setValue($value, ($this->hasMethod('getAllFields') ? $this->getAllFields() : null));
				
				$value = $valueObject;
			}
			
			if($cache) $this->objCache[$cacheName] = $value;
		} else {
			$value = $this->objCache[$cacheName];
		}
		
		if(isset($_REQUEST['debug_profile'])) {
			Profiler::unmark("obj.$fieldName", "on a $this->class object");
		}
		
		if(!is_object($value) && $forceReturnedObject) {
			$default = Object::get_static('ViewableData', 'default_cast');
			$value   = new $default($fieldName);
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
	
	/** 
	 * Set the current iterator properties - where we are on the iterator.
	 *
	 * @param int $pos position in iterator
	 * @param int $totalItems total number of items
	 */
	public function iteratorProperties($pos, $totalItems) {
		$this->iteratorPos        = $pos;
		$this->iteratorTotalItems = $totalItems;
	}
	
	/**
	 * Returns true if this object is the first in a set.
	 *
	 * @return bool
	 */
	public function First() {
		return $this->iteratorPos == 0;
	}
	
	/**
	 * Returns true if this object is the last in a set.
	 *
	 * @return bool
	 */
	public function Last() {
		return $this->iteratorPos == $this->iteratorTotalItems - 1;
	}
	
	/**
	 * Returns 'first' or 'last' if this is the first or last object in the set.
	 *
	 * @return string|null
	 */
	public function FirstLast() {
		if($this->First()) return 'first';
		if($this->Last())  return 'last';
	}
	
	/**
	 * Return true if this object is between the first & last objects.
	 *
	 * @return bool
	 */
	public function Middle() {
		return !$this->First() && !$this->Last();
	}
	
	/**
	 * Return 'middle' if this object is between the first & last objects.
	 *
	 * @return string|null
	 */
	public function MiddleString() {
		if($this->Middle()) return 'middle';
	}
	
	/**
	 * Return true if this object is an even item in the set.
	 *
	 * @return bool
	 */
	public function Even() {
		return (bool) ($this->iteratorPos % 2);
	}
	
	/**
	 * Return true if this is an odd item in the set.
	 *
	 * @return bool
	 */
	public function Odd() {
		return !$this->Even();
	}
	
	/**
	 * Return 'even' or 'odd' if this object is in an even or odd position in the set respectively.
	 *
	 * @return string
	 */
	public function EvenOdd() {
		return ($this->Even()) ? 'even' : 'odd';
	}
	
	/**
	 * Return the numerical position of this object in the container set. The count starts at $startIndex.
	 *
	 * @param int $startIndex Number to start count from.
	 * @return int
	 */
	public function Pos($startIndex = 1) {
		return $this->iteratorPos + $startIndex;
	}
	
	/**
	 * Return the total number of "sibling" items in the dataset.
	 *
	 * @return int
	 */
	public function TotalItems() {
		return $this->iteratorTotalItems;
	}

	/**
	 * Returns the modulus of the numerical position of the item in the data set.
	 * The count starts from $startIndex, which defaults to 1.
	 * @param int $Mod The number to perform Mod operation to.
	 * @param int $startIndex Number to start count from.
	 * @return int
	 */
	public function Modulus($mod, $startIndex = 1) {
		return ($this->iteratorPos + $startIndex) % $mod;
	}
	
	public function MultipleOf($factor, $offset = 1) {
		return ($this->Modulus($factor, $offset) == 0);
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
		if($theme = SSViewer::current_theme()) {
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
	 * @see Member::currentUser()
	 */
	public function CurrentMember() {
		return Member::currentUser();
	}
	
	/**
	 * Return a CSRF-preventing ID to insert into a form.
	 *
	 * @return string
	 */
	public function getSecurityID() {
		$token = SecurityToken::inst();
		return $token->getValue();
	}
	
	/**
	 * @see Permission::check()
	 */
	public function HasPerm($code) {
		return Permission::check($code);
	}
	
	/**
	 * @see Director::absoluteBaseURL()
	 */
	public function BaseHref() {
		return Director::absoluteBaseURL();
	}
	
	/**
	 * @see Director::is_ajax()
	 */
	public function IsAjax() {
		return Director::is_ajax();
	}
	
	/**
	 * @see i18n::get_locale()
	 */
	public function i18nLocale() {
		return i18n::get_locale();
	}
	
	/**
	 * Return debug information about this object that can be rendered into a template
	 *
	 * @return ViewableData_Debugger
	 */
	public function Debug() {
		return new ViewableData_Debugger($this);
	}
	
	/**
	 * @see Controller::curr()
	 */
	public function CurrentPage() {
		return Controller::curr();
	}
	
	/**
	 * @see SSViewer::topLevel()
	 */
	public function Top() {
		return SSViewer::topLevel();
	}
}

/**
 * @package sapphire
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
 * @package sapphire
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
		
		if($this->object->hasMethod('getAllFields')) {
			$debug .= "<b>Debugging Information: all fields available in '{$this->object->class}'</b><br/><ul>";
			
			foreach($this->object->getAllFields() as $field => $value) {
				$debug .= "<li>\$$field</li>";
			}
			
			$debug .= "</ul>";
		}
		
		// check for an extra attached data
		if($this->object->hasMethod('data') && $this->object->data() != $this->object) {
			$debug .= Object::create('ViewableData_Debugger', $this->object->data())->forTemplate();
		}
		
		return $debug;
	}

}
