<?php
/**
 * Generic class for all data that will be accessed from a view.
 * 
 * View interrogate their controllers to provide them with the data they need.  They to this by
 * calling the methods provided by the ViewableData base-class, from which most Sapphire objects
 * are inherited.
 * 
 * 
 * ViewableData cover page controls, controllers, and data objects.  It's the basic unit of
 * data exchange.  More specifically, it's anything that can be put into a view.
 * @package sapphire
 * @subpackage view
 */
class ViewableData extends Object implements IteratorAggregate {
	/**
	 * The iterator position.
	 * @var int
	 */
	protected $iteratorPos;

	/**
	 * Total number of items in the iterator.
	 * @var int
	 */
	protected $iteratorTotalItems;
	
	/**
	 * Failover object.
	 * @var ViewableData
	 */
	protected $failover;
	
	/**
	 * A cast of this object's controls in object format
	 * @var array
	 */
	protected $_object_cache = array();
	
	/**
	 * A cast of this object's controls in XML-safe format
	 * @var array
	 */
	protected $_xml_cache = array();

	/**
	 * A cast of this object's controls in their native format (used by cachedCall)
	 * @var array
	 */
	protected $_natural_cache = array();
	
	/**
	 * @var $customisedObj ViewableData_Customised|ViewableData_ObjectCustomised
	 * Saves past customisations to make them available on subsequent rendering-calls.
	 * E.g. This enables LeftAndMain to access customisations on controller-actions in
	 * Left() and Right().
	 */
	protected $customisedObj;


	/**
	 * Define custom methods for this object.  Called once per class.
	 * Implements failover and cached methods.
	 */
	function defineMethods() {
		// Set up failover
		if($this->failover) {
			$this->addMethodsFrom('failover');
		}
		
		if(isset($_GET['debugfailover'])) {
			Debug::message("$this->class / $this->failover");
		}

		// Set up cached methods
		$methodNames = $this->allMethodNames();
		foreach($methodNames as $methodName) {
			if($methodName[0] == "_") {
				$trimmedName = substr($methodName,1);
				$this->createMethod($trimmedName, "return \$obj->cachedCall('$methodName', '$trimmedName', \$args);");
			}
		}
		parent::defineMethods();
	}
	
	/**
	 * Returns a "1 record iterator"
	 * Views <%control %> tags operate by looping over an item for as many instances as are 
	 * available.  When you stick a single ViewableData object in a control tag, the foreach()
	 * loop still needs to work.  We do this by creating an iterator that only returns one record.
	 * This will always return the current ViewableData object.
	 * @return ViewableData_Iterator A 1 record iterator
	 */
	function getIterator() {
		return new ViewableData_Iterator($this);
	}
	
	/**
	 * Accessor overloader.
	 * Allows default getting of fields via $this->getVal(), or mediation via a 
	 * getParamName() method.
	 * @param string $field The field name.
	 * @return mixed The field.
	 */
	public function __get($field) {
		if($this->hasMethod($funcName = "get$field")) {
			return $this->$funcName();
		} else if($this->hasField($field)) {
			return $this->getField($field);
		} else if($this->failover) {
			return $this->failover->$field;
		}
	}

	/**
	 * Setter overloader.
	 * Allows default setting of fields in $this->setValue(), or mediation via a 
	 * getParamName() method.
	 * @param string $field The field name.
	 * @param mixed $val The field value.
	 */
	public function __set($field, $val) {
		if($this->hasMethod($funcName = "set$field")) {
			return $this->$funcName($val);
		} else {
			$this->setField($field, $val);
		}
	}

	/**
	 * Is-set overloader.
	 * Will check to see if the given field exists on this object.  Calls the hasField() method,
	 * as well as checking failover classes.
	 * @param string $field The field name.
	 * @return boolean True if field exists
	 */
	public function __isset($field) {
		if($this->hasField($field)) {
			return true;
		}
		
		if($this->failover && $this->failover->hasField($field)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Get a field by it's name. This should be overloaded in child classes.
	 * @param string $field fieldname
	 */
	protected function getField($field) {
	}
	
	/**
	 * Set a fields value. This should be overloaded in child classes.
	 * @param string $field The field name.
	 * @param mixed $val The field value.
	 */
	protected function setField($field, $val) {
		$this->$field = $val;
	}
	
	/**
	 * Checks if a field exists on this object. This should be overloaded in child classes.
	 * @param string $field The field name
	 * @return boolean
	 */
	public function hasField($field) {
	}

	/**
	 * Cache used by castingHelperPair().
	 * @var array
	 */
	protected static $castingHelperPair_cache;

	/**
	 * Returns the "casting helper" for the given field and the casting class name.  A casting helper 
	 * is a piece of PHP code that, when evaluated, will create an object to represent the value.
	 * 
	 * The return value is an map containing two values:
	 *  - className: The name of the class (eg: 'Varchar')
	 *  - castingHelper: The casting helper (eg: 'return new Varchar($fieldName);')
	 * 
	 * @param string $field The field name
	 * @return array
	 */
	public function castingHelperPair($field) {
		$class = $this->class;
		
		if(!isset(self::$castingHelperPair_cache[$class])) {
			if($this->failover) {
				$this->failover->buildCastingHelperCache(self::$castingHelperPair_cache[$class]);
			}
			$this->buildCastingHelperCache(self::$castingHelperPair_cache[$class]);
			self::$castingHelperPair_cache[$class]['ClassName'] = array("className" => "Varchar", "castingHelper" => "return new Varchar(\$fieldName);");
		}

		return isset(self::$castingHelperPair_cache[$class][$field]) ? self::$castingHelperPair_cache[$class][$field] : null;
	}
	
	/**
	 * A helper function used by castingHelperPair() to build the cache.
	 * @param array
	 */
	public function buildCastingHelperCache(&$cache) {
		$class = $this->class ? $this->class : get_class($this);
		$classes = ClassInfo::ancestry($class);
		
		foreach($classes as $componentClass) {
			if($componentClass == "ViewableData") $isViewableData = true;
			if($componentClass == "DataObject") $isDataObject = true;

			if(isset($isDataObject) && $isDataObject) {
				$fields = Object::uninherited_static($componentClass, 'db');
				if($fields) foreach($fields as $fieldName => $fieldSchema) {
					$cache[$fieldName] = ViewableData::castingObjectCreatorPair($fieldSchema);
				}
			}
			if(isset($isViewableData) && $isViewableData) {
				$fields = Object::uninherited_static($componentClass, 'casting');
				if($fields) foreach($fields as $fieldName => $fieldSchema) {
					$cache[$fieldName] = ViewableData::castingObjectCreatorPair($fieldSchema);
				}
			}
		}
	}
	
	/**
	 * Returns the "casting helper" for the given field.  A casting helper 
	 * is a piece of PHP code that, when evaluated, will create an object to represent the value.
	 * @param string $field The field name.
	 * @return string
	 */
	public function castingHelper($field) {
		$pair = $this->castingHelperPair($field);
		return $pair['castingHelper'];
	}
	
	/**
	 * Converts a field spec into an object creator.
	 * For example: "Int" becomes "new Int($fieldName);" and "Varchar(50)" becomes "new Varchar($fieldName, 50);"
	 * @param string $fieldSchema The field spec.
	 * @return string
	 */
	public static function castingObjectCreator($fieldSchema) {
		if(strpos($fieldSchema,'(') === false) {
			return "return Object::create('{$fieldSchema}',\$fieldName);";
		} else {
			return "return new " . ereg_replace('^([^(]+)\\(','\\1($fieldName,', $fieldSchema) . ';';
		}
	}

	/**
	 * Converts a field spec into an object creator pair; this is a map containing className and castingHelper.
	 * See {@link castingObjectCreator} for more information.
	 * @param string $fieldSchema The field spec.
	 * @return array
	 */
	public static function castingObjectCreatorPair($fieldSchema) {
		if(strpos($fieldSchema,'(') === false) {
			return array(
				'className' => $fieldSchema, 
				'castingHelper' => "return Object::create('{$fieldSchema}',\$fieldName);"
			);
		} else if(ereg('^([^(]+)\\(', $fieldSchema, $parts)) {
			return array(
				'className' => $parts[1],
				'castingHelper' => "return new " . ereg_replace('^([^(]+)\\(','\\1($fieldName,', $fieldSchema) . ';',
			);
		} else {
			user_error("castingObjectCreatorPair: Bad field schema '$fieldSchema' in class $this->class", E_USER_WARNING);
		}
	}
	
	/**
	 * Return the string-format type for the given field.
	 *
	 * @param string $fieldName 
	 * @return string 'xml'|'raw'
	 */
	function escapeTypeForField($fieldName) {
		$helperPair = $this->castingHelperPair($fieldName);
		$castedClass = $helperPair['className'];
		if(!$castedClass || $castedClass == 'HTMLText' || $castedClass == 'HTMLVarchar') return "xml";
		else return "raw";
	}
	
	/**
	 * Return the object version of the given field/method.
	 * @param string $fieldName The name of the field/method.
	 * @param array $args The arugments.
	 * @param boolean $forceReturnObject If true, this method will *always* return an object.  If there's
	 * no sensible one available, it will return new ViewableData()
	 * @return mixed;
	 */
	public function obj($fieldName, $args = null, $forceReturnObject = false) {
		if(isset($_GET['debug_profile'])) {
			Profiler::mark("template($fieldName)", " on $this->class object");
		}
		
		if($args) {
			$identifier = $fieldName . ',' . implode(',', $args);
		} else {
			$identifier = $fieldName;
		}
		
		// Fix for PHP 5.3 - $args cannot be null
		if(is_null($args))
			$args=Array();
		
		if(isset($this->_object_cache[$identifier])) {
			$fieldObj = $this->_object_cache[$identifier];
		} else {
			if($this->hasMethod($fieldName)) {
				$val = call_user_func_array(array(&$this, $fieldName), $args);
			} else {
				$val = $this->$fieldName;
			}

			$this->_natural_cache[$identifier] = $val;

			if(is_object($val)) {
				$fieldObj = $val;
				
			} else {
				$helperPair = $this->castingHelperPair($fieldName);
				if(!$helperPair && $this->failover) {
					$helperPair = $this->failover->castingHelperPair($fieldName);
				}
				
				$constructor = $helperPair['castingHelper'];

				
				if($constructor) {
					$fieldObj = eval($constructor);
					if($this->hasMethod('getAllFields')) {
						$fieldObj->setValue($val, $this->getAllFields());
					} else {
						$fieldObj->setValue($val);
					}
				}
			}

			$this->_object_cache[$identifier] = isset($fieldObj) ? $fieldObj : null;
		}
		
		if(!isset($fieldObj) && $forceReturnObject){
			$fieldObj = new ViewableData();
		}

		if(isset($_GET['debug_profile'])) {
			Profiler::unmark("template($fieldName)", " on $this->class object");
		}
		
		return isset($fieldObj) ? $fieldObj : null;
	}
	
	/**
	 * Return the value (non-object) version of the given field/method.
	 * @deprecated ViewableData->val() is deprecated, use XML_val() instead
	 */
	public function val($fieldName, $args = null) {
		return $this->XML_val($fieldName, $args);
	}
	
	/**
	 * Returns the value of the given field / method in an XML-safe format.
	 * @param string $fieldName The field name.
	 * @param array $args The arguments.
	 * @param boolean $cache Cache calls to this function.
	 * @return string
	 */	
	public function XML_val($fieldName, $args = null, $cache = false) {
		if(isset($_GET['debug_profile'])) {
			Profiler::mark("template($fieldName)", " on $this->class object");
		}

		if($cache) {
			if($args) {
				$identifier = $fieldName . ',' . implode(',', $args);
			} else {
				$identifier = $fieldName;
			}
			
			if(isset($this->_xml_cache[$identifier])) {
				if(isset($_GET['debug_profile'])) {
					Profiler::unmark("template($fieldName)", " on $this->class object");
				}
				return $this->_xml_cache[$identifier];
			}
		}
		
		// Fix for PHP 5.3 - $args cannot be null
		if(is_null($args))
			$args=Array();
		
		// This will happen when cachedCall was called on an object; don't bother re-calling the method, just
		// do the conversion step below				
		if($cache && isset($this->_object_cache[$identifier])) {
			$val = $this->_object_cache[$identifier];
			
		// Get the field / method
		} else {
			if($this->hasMethod($fieldName)) {
				$val = call_user_func_array(array(&$this, $fieldName), $args);
			} else {
				$val = $this->$fieldName;
			}
			
			if(isset($identifier)) {
				$this->_natural_cache[$identifier] = $val;
			}
		}
		
		// Case 1: object; converted to XML_val() by
		if(is_object($val)) {
			if($cache) {
				$this->_object_cache[$identifier] = $val;
			}
			
			$val = $val->forTemplate();
			
			if($cache) {
				$this->_xml_cache[$identifier] = $val;
			}
		} else {
			// Identify the 'casted class' of this field, which will give us some hints about what kind of
			// data has been returned
			if(isset($_GET['debug_profile'])) {
				Profiler::mark('casting cost');
			}
			
			// Case 2: Check if the value is raw and must be made XML-safe
			if($this->escapeTypeForField($fieldName) != 'xml') $val = Convert::raw2xml($val);
			
			if(isset($_GET['debug_profile'])) {
				Profiler::unmark('casting cost');
			}
			
			if($cache) {
				$this->_xml_cache[$identifier] = $val;
			}
		}
		
		if(isset($_GET['debug_profile'])) {
			Profiler::unmark("template($fieldName)", " on $this->class object");
		}
		
		return $val;
	}
	
	/**
	 * Return a named array of calls to XML_val with different parameters.
	 * Each value in the array is used as the first argument to XML_val.  The result is a named array of the return values.
	 * 
	 * The intended use-case is when converting simple templates to PHP methods to optimise code, as we did in the form classes.
	 * If you're calling renderWith more than a few times on a very simple template, this can be useful.
	 * 
	 * extract(getXMLValues(array('Title','Field','Message')))
	 * // You can now use $Title, $Field, and $Message as you would in a template
	 * 
	 * @param array $elementList The list of field names.
	 * @return array
	 */
	public function getXMLValues($elementList) {
		foreach($elementList as $elementName) {
			$result[$elementName] = $this->XML_val($elementName);
		}
		
		return $result;
	}

	/**
	 * Return the value of the given field without any escaping.
	 * @param string $fieldName The field name.
	 * @param array $args The arguments.
	 * @return string
	 */
	public function RAW_val($fieldName, $args = null) {
		return Convert::xml2raw($this->XML_val($fieldName, $args));
	}
	
	/**
	 * Return the value of the given field in an SQL safe format.
	 * @param string $fieldName The field name.
	 * @param array $args The arguments.
	 * @return string
	 */
	public function SQL_val($fieldName, $args = null) {
		return Convert::xml2sql($this->XML_val($fieldName, $args));
	}
	
	/**
	 * Return the value of the given field in an JavaScript safe format.
	 * @param string $fieldName The field name.
	 * @param array $args The arguments.
	 * @return string
	 */
	public function JS_val($fieldName, $args = null) {
		return Convert::xml2js($this->XML_val($fieldName, $args));
	}
	
	/**
	 * Return the value of the given field in an XML attribute safe format.
	 * @param string $fieldName The field name.
	 * @param array $args The arguments.
	 * @return string
	 */
	public function ATT_val($fieldName, $args = null) {
		return Convert::xml2att($this->XML_val($fieldName, $args));
	}
	
	/**
	 * SSViewer's data-access method.
	 * All template calls to ViewableData are fed through this function.  It takes care of caching
	 * data, and linking up parents to support Menu1_Menu2() syntax for nested data.
	 * @param string $funcName the method to call
	 * @param string $identifier
	 * @param array $args The arguments
	 * @return mixed
	 */
	function cachedCall($funcName, $identifier = null, $args = null) {
		if(isset($_GET['debug_profile'])) {
			Profiler::mark("template($funcName)", " on $this->class");
		}
		
		if(!$identifier) {
			if($args) {
				$identifier = $funcName . ',' . implode(',', $args);
			} else {
				$identifier = $funcName;
			}
		}
		
		// Fix for PHP 5.3 - $args cannot be null
		if(is_null($args))
			$args=Array();
				
		if(isset($this->_natural_cache[$identifier])) {
			if(isset($_GET['debug_profile'])) {
				Profiler::unmark("template($funcName)", " on $this->class");
			}
			return $this->_natural_cache[$identifier];
		}
		
		if($this->hasMethod($funcName)) {
			$val = call_user_func_array(array(&$this, $funcName), $args);
		} else {
			$val = $this->$funcName;
		}
		
		$this->_natural_cache[$identifier] = $val;
		
		if(is_object($val)) {
			$this->_object_cache[$identifier] = $val;
		} else {
			$helperPair = $this->castingHelperPair($funcName);
			$castedClass = $helperPair['className'];
			if($castedClass && $castedClass != 'HTMLText' && $castedClass != 'HTMLVarchar' && $castedClass != 'Text') {
				$val = Convert::raw2xml($val);
			}
			
			$this->_xml_cache[$identifier] = $val;
		}
		
		if(isset($_GET['debug_profile'])) {
			Profiler::unmark("template($funcName)", " on $this->class");
		}
			
		return $val;
	}	
	
	/**
	 * @param $obj ViewableData_Customised|ViewableData_ObjectCustomised
	 */
	function setCustomisedObj($obj) {
		$this->customisedObj = $obj;
	}
	
	/**
	 * Returns true if the given method/parameter has a value
	 * If the item is an object, it will use the exists() method to determine existence
	 * @param string $funcName The function name.
	 * @param array $args The arguments.
	 * @return boolean
	 */
	function hasValue($funcName, $args = null) {
		$test = $this->cachedCall($funcName, null, $args); 
		
		if(is_object($test)) {
			return $test->exists();
		} else if($test && $test !== '<p></p>') {
			return true;
		}
	}
	
	/**
	 * Set up the "iterator properties" for this object.
	 * These are properties that give information about where we are in the set.
	 * @param int $pos Position in iterator
	 * @param int $totalItems Total number of items
	 */
	function iteratorProperties($pos, $totalItems) {
		$this->iteratorPos = $pos;
		$this->iteratorTotalItems = $totalItems;
	} 
	
	/**
	 * Returns true if this item is the first in the container set.
	 * @return boolean
	 */
	function First() {
		return $this->iteratorPos == 0;
	}

	/**
	 * Returns true if this item is the last in the container set.
	 * @return boolean
	 */
	function Last() {
		return $this->iteratorPos == $this->iteratorTotalItems - 1;
	}
	
	/**
	 * Returns 'first' if this item is the first in the container set.
	 * Returns 'last' if this item is the last in the container set.
	 */
	function FirstLast() {
		if($this->iteratorPos == 0) {
			return "first";
		} else if($this->iteratorPos == $this->iteratorTotalItems - 1) {
			return "last";
		} else {
			return "";
		}
	}
	
	/**
	 * Returns 'middle' if this item is between first and last.
	 * @return boolean
	 */
	function MiddleString(){
		if($this->Middle())
			return "middle";
		else
			return "";
	}

	/**
	 * Returns true if this item is one of the middle items in the container set.
	 * @return boolean
	 */
	function Middle() {
		return $this->iteratorPos > 0 && $this->iteratorPos < $this->iteratorTotalItems - 1;
	}

	/**
	 * Returns true if this item is an even item in the container set.
	 * @return boolean
	 */
	function Even() {
		return $this->iteratorPos % 2;
	}
	
	/**
	 * Returns true if this item is an even item in the container set.
	 * @return boolean
	 */
	function Odd() {
		return !$this->Even();
	}
	
	/**
	 * Returns 'even' if this item is an even item in the container set.
	 * Returns 'odd' if this item is an odd item in the container set.
	 * @return string
	 */
	function EvenOdd() {
		return $this->Even() ? 'even' : 'odd';
	}
		
	/**
	 * Returns the numerical number of this item in the dataset.
	 * The count starts from $startIndex, which defaults to 1.
	 * @param int $startIndex Number to start count from.
	 * @return int
	 */
	function Pos($startIndex = 1) {
		return $this->iteratorPos + $startIndex;
	}
	
	/**
	 * Return the total number of "sibling" items in the dataset.
	 * @return int
	 */
	function TotalItems() {
		return $this->iteratorTotalItems;
	}
	
	/**
	 * Returns the currently logged in user.
	 * @return Member
	 */
	function CurrentMember() {
		return Member::currentUser();
	}
	
	/**
	 * Returns the Security ID.
	 * This is used to prevent CRSF attacks in forms.
	 * @return int
	 */
	function SecurityID() {
		if(Session::get('SecurityID')) {
			$securityID = Session::get('SecurityID');
		} else {
			$securityID = rand();
			Session::set('SecurityID', $securityID);
		}
		
		return $securityID;
	}

    /**
     * Checks if the current user has the given permission.
     * Can be used to implement security-specific sections within templates
     * @return int The Permission record-ID if the permission can be found, null otherwise
     */
    function HasPerm($permCode) {
        return Permission::check($permCode);
    }
	
	/**
	 * Add some arbitrary data to this viewabledata object.  Returns a new object with the
	 * merged data.
	 * @param mixed $data The data to add.
	 * @return ViewableData
	 */
	function customise($data) {
		if(is_array($data)) {
			return new ViewableData_Customised($this, $data);
		} else if(is_object($data)) {
			return new ViewableData_ObjectCustomised($this, $data);
		} else {
			return $this;
		}
	}
	
	/**
	 * Render this data using the given template, and return the result as a string
	 * You can pass one of the following:
	 *  - A template name.
	 *  - An array of template names.  The first template that exists will be used.
	 *  - An SSViewer object.
	 * @param string|array|SSViewer The template.
	 * @return string
	 */
	function renderWith($template, $params = null) {
		if(!is_object($template)) {
			$template = new SSViewer($template);
		}
		
		
		// if the object is already customised (e.g. through Controller->run()), use it
		$obj = ($this->customisedObj) ? $this->customisedObj : $this;
		
		if($params) $obj = $this->customise($params);
		
		if(is_a($template,'SSViewer')) {
			return $template->process($obj);
		} else {
			user_error("ViewableData::renderWith() Was passed a $template->class object instead of a SSViewer object", E_USER_ERROR);
		}
	}

	/**
	 * Return the site's absolute base URL, with a slash on the end.
	 * @return string
	 */
	function BaseHref() {
		return Director::absoluteBaseURL();
	}
	
  /**
   * When rendering some objects it is necessary to iterate over the object being rendered, to
   * do this, you need access to itself.
   *
   * @return ViewableData
   */
  function Me() {
    return $this;
  }
	
	/**
	 * Returns wether the current request is triggered
	 * by an XMLHTTPRequest object.
	 *
	 * @return bool
	 */
	function IsAjax() {
		return Director::is_ajax();
	}
	
	/**
	 * @return string Locale configured in environment settings or user profile (e.g. 'en_US')
	 */
	function i18nLocale() {
		return i18n::get_locale();
	}

	/**
	 * Return a Debugger object.
	 * This is set up like so that you can put $Debug.Content into your template to get debugging
	 * information about $Content.
	 * @return ViewableData_Debugger
	 */
	function Debug() {
		$d = new ViewableData_Debugger($this);
		return $d->forTemplate();
	}

	/**
	 * Returns the current controller
	 * @return Controller
	 */
	function CurrentPage() {
		return Controller::curr();
	}
	
	/**
	 * Returns the top level ViewableData being rendered.
	 * @return ViewableData
	 */
	function Top() {
		return SSViewer::topLevel();
	}


	/**
	 * Returns the root directory of the theme we're working with.
	 * This can be useful for referencing images within the theme.  For example, you might put a reference to 
	 * <img src="$ThemeDir/images/something.gif"> in your template.
	 * 
	 * If your image is within a subtheme, such as mytheme_forum, you can set the subtheme parameter.  For example, 
	 * <img src="$ThemeDir(forum)/images/something.gif">
	 * 
	 * We don't recommend that you use this method when no theme is selected.  That is, we recommend that you only put
	 * $ThemeDir into your theme templates.  However, if no theme is selected, this will be the project folder/
	 * 
	 * @param subtheme The subtheme name.
	 */
	public function ThemeDir($subtheme = null) {
		$theme = SSViewer::current_theme();
		if($theme) {
			return "themes/$theme" . ($subtheme ? "_$subtheme" : "");
		} else {
			return project();
		}
	}
	
	/**
	 * Get part of class ancestry for css-class-usage.
	 * Avoids having to subclass just to built templates with new css-classes,
	 * and allows for versatile css inheritance and overrides.
	 * 
	 * <code>
	 * <body class="$CSSClasses">
	 * </code>
	 * 
	 * @uses ClassInfo
	 * 
	 * @param string Classname to stop traversing upwards the ancestry (Default: ViewableData)
	 * @return string space-separated attribute encoded classes
	 */	
	function CSSClasses($stopAtClass = false) {
		global $_ALL_CLASSES;
		if(!$stopAtClass) $stopAtClass = 'ViewableData';
		
		$classes = array();
		$classAnchestry = ClassInfo::ancestry($this->class);
		$viewableDataAnchestry = ClassInfo::ancestry($stopAtClass);
	  	foreach($classAnchestry as $anchestor) {
				if(!in_array($anchestor, $viewableDataAnchestry)) $classes[] = $anchestor;
		}
		
		// optionally add template identifier
		if(isset($this->template) && !in_array($this->template, $classes)) {
			$classes[] = $this->template;
		}

		return Convert::raw2att(implode(" ", $classes));
	}

	/**
	 * Object-casting information for class methods
	 * @var mixed
	 */
	public static $casting = array(
		'BaseHref' => 'Varchar',
		'CSSClasses' => 'Varchar',
	);
	
	/**
	 * Keep a record of the parent node of this data node.
	 * @var mixed
	 */
	protected $parent = null;
	
	/**
	 * Keep a record of the parent node of this data node.
	 * @var mixed
	 */
	protected $namedAs = null;
}

/**
 * A ViewableData object that has been customised with extra data. Use
 * ViewableData->customise() to create.
 * @package sapphire
 * @subpackage view
 */
class ViewableData_Customised extends ViewableData {
	public function castingHelperPair($field) {
		return $this->obj->castingHelperPair($field);
	}
		
	function __construct($obj, $extraData) {
		$this->obj = $obj;
		$this->obj->setCustomisedObj($this);
		$this->extraData = $extraData;
		
		parent::__construct();
	}
	
	function __call($funcName, $args) {
		if(isset($this->extraData[$funcName])) {
			return $this->extraData[$funcName];
		} else {
			return call_user_func_array(array(&$this->obj, $funcName), $args);
		}
	}
	
	
	function __get($fieldName) {
		if(isset($this->extraData[$fieldName])) {
			return $this->extraData[$fieldName];
		}
		return $this->obj->$fieldName;
	}
	
	function __set($fieldName, $val) {
		if(isset($this->extraData[$fieldName])) unset($this->extraData[$fieldName]);
		return $this->obj->$fieldName = $val;
	}
	

	function hasMethod($funcName) {
		return isset($this->extraData[$funcName]) || $this->obj->hasMethod($funcName);
	}
	
	
	function XML_val($fieldName, $args = null, $cache = false) {
		if(isset($this->extraData[$fieldName])) {
			if(isset($_GET['debug_profile'])) {
				Profiler::mark("template($fieldName)", " on $this->class object");
			}
			
			if(is_object($this->extraData[$fieldName])) {
				$val = $this->extraData[$fieldName]->forTemplate();
			} else {
				$val = $this->extraData[$fieldName];
			}
			
			if(isset($_GET['debug_profile'])) {
				Profiler::unmark("template($fieldName)", " on $this->class object");
			}
			
			return $val;
		} else {
			return $this->obj->XML_val($fieldName, $args, $cache);
		}
	}
	
	function obj($fieldName, $args = null, $forceReturnObject = false) {
		if(isset($this->extraData[$fieldName])) {
			if(!is_object($this->extraData[$fieldName])) {
				user_error("ViewableData_Customised::obj() '$fieldName' was requested from the array data as an object but it's not an object.  I can't cast it.", E_USER_WARNING);
			}
			return $this->extraData[$fieldName];
		} else {
			return $this->obj->obj($fieldName, $args, $forceReturnObject);
		}
	}

	function cachedCall($funcName, $identifier = null, $args = null) {
		if(isset($this->extraData[$funcName])) {
			return $this->extraData[$funcName];
		} else {
			return $this->obj->cachedCall($funcName, $identifier, $args);
		}
	}
	
	function customise($data) {
		if(is_array($data)) {
			$this->extraData = array_merge($this->extraData, $data);
			return $this;
		} else {
			return parent::customise($data);
		}
	}

	/**
	 * Original ViewableData object
	 * @var ViewableDate
	 */
	protected $obj;
	/**
	 * Array containing the extra data
	 * @var array
	 */
	protected $extraData;
}

/**
 * A ViewableData object that has been customised with an extra object. Use
 * ViewableData->customise() to create.
 * @package sapphire
 * @subpackage view
 */
class ViewableData_ObjectCustomised extends ViewableData {
	function __construct($obj, $extraObj) {
		$this->obj = $obj;
		$this->extraObj = $extraObj;
		$this->obj->setCustomisedObj($this);
		
		parent::__construct();
	}
	
	function __call($funcName, $args) {
		if($this->extraObj->hasMethod($funcName)) {
			return call_user_func_array(array(&$this->extraObj, $funcName), $args);
		} else {
			return call_user_func_array(array(&$this->obj, $funcName), $args);
		}
	}
	
	function __get($fieldName) {
		if($this->extraObj->hasField($fieldName)) {
			return $this->extraObj->$fieldName;
		} else {
			return $this->obj->$fieldName;
		}
	}
	
	function __set($fieldName, $val) {
		$this->extraObj->$fieldName = $val;
		$this->obj->$fieldName = $val;
	}
	
	function hasMethod($funcName) {
		return $this->extraObj->hasMethod($funcName) || $this->obj->hasMethod($funcName);
	}
	

	function cachedCall($funcName, $identifier = null, $args = null) {
		$result = $this->extraObj->cachedCall($funcName, $identifier, $args);
		
		if(!$result) {
			$result = $this->obj->cachedCall($funcName, $identifier, $args);
		}
		
		return $result;
	}
	
	function obj($fieldName, $args = null, $forceReturnObject = false) {
		if($this->extraObj->hasMethod($fieldName) || $this->extraObj->hasField($fieldName)) {
			return $this->extraObj->obj($fieldName, $args, $forceReturnObject);
		} else {
			return $this->obj->obj($fieldName, $args, $forceReturnObject);
		}
	}

	/**
	 * The extra object.
	 * @var ViewableData
	 */
	protected $extraObj;
	
	/**
	 * The original object.
	 * @var ViewableData
	 */
	protected $obj;
}

/**
 * Debugger helper.
 * @package sapphire
 * @subpackage view
 * @todo Finish this off
 */
class ViewableData_Debugger extends ViewableData {
	/**
	 * The original object
	 * @var ViewableData
	 */
	protected $obj;
	
	function __construct($obj) {
		$this->obj = $obj;
		parent::__construct();
	}
	
	/**
	 * Return debugging information, as XHTML. If a field name is passed,
	 * it will show debugging information on that field, otherwise it will show
	 * information on all methods and fields.
	 * @var string $field The field name.
	 * @return string
	 */
	function forTemplate($field = null) {
		if($field) {
			return "<b>Info on $field:<br/>" . 
				($this->obj->hasMethod($field) ? "Has method '$field'.  " : "") . 
				($this->obj->hasField($field) ? "Has field '$field'.  " : "");

		} else {
			echo "<b>Debug: all methods available in {$this->obj->class}</b><br/>";
			echo "<ul>";
			$names = $this->obj->allMethodNames();
			foreach($names as $name) {
				if(strtoupper($name[0]) == $name[0] && $name[0] != "_") {
					echo "<li>\$$name</li>";
				}
			}
			echo "</ul>";
			if($this->obj->hasMethod('getAllFields')) {
				echo "<b>Debug: all fields available in {$this->obj->class}</b><br/>";
				echo "<ul>";

				$data = $this->obj->getAllFields();
				foreach($data as $key => $val) {
					echo "<li>\$$key</li>";
				}
				echo "</ul>";
			}
		}
		
		if($this->obj->hasMethod('data')) {
			if($this->obj->data() != $this->obj) {
				$d = new ViewableData_Debugger($this->obj->data());
				echo $d->forTemplate();
			}
		}
	}
}

/**
 * Implementation of a "1 record iterator"
 * Views <%control %> tags operate by looping over an item for as many instances as are 
 * available.  When you stick a single ViewableData object in a control tag, the foreach()
 * loop still needs to work.  We do this by creating an iterator that only returns one record.
 * This will always return the current ViewableData object.
 */
class ViewableData_Iterator implements Iterator {
	function __construct($viewableData) {
		$this->viewableData = $viewableData;
		$this->show = true;
	}

	/** 
	 * Internal state toggler
	 * @var bool
	 */
	private $show;

	/** 
	 * This will always return the current ViewableData object.
	 */
	public function current() { 
		if($this->show) {
			return $this->viewableData;
		}
	}
	
	/** 
	 * Rewinds the iterator back to the start.
	 */
	public function rewind() { 
		$this->show = true;
	}
	
	/** 
	 * Return the key for the current object.
	 */
	public function key() { 
		return 0;
	}
	
	/** 
	 * Get the next object.
	 */
	public function next() {
		if($this->show) {
			$this->show = false;
			return $this->viewableData;
		} else {
			return null;
		}
	}
	
	/** 
	 * Check if there is a current object.
	 */
	public function valid() { 
		return $this->show;
	}
}

?>