<?php
/**
 * Single field in the database.
 * Every field from the database is represented as a sub-class of DBField.
 * 
 * <b>Multi-value DBField objects</b>
 * 
 * Sometimes you will want to make DBField classes that don't have a 1-1 match to database fields.  To do this, there are a
 * number of fields for you to overload.
 *  - Overload {@link writeToManipulation} to add the appropriate references to the INSERT or UPDATE command
 *  - Overload {@link addToQuery} to add the appropriate items to a SELECT query's field list
 *  - Add appropriate accessor methods 
 * 
 * <b>Subclass Example</b>
 * 
 * The class is easy to overload with custom types, e.g. the MySQL "BLOB" type (http://dev.mysql.com/doc/refman/5.0/en/blob.html).
 * 
 * <code>
 * class Blob extends DBField {                                                                                                   
 * 	function requireField() {                                                                                                         
 * 		DB::requireField($this->tableName, $this->name, "blob");
 *  }
 * }
 * </code>
 * 
 * @package framework
 * @subpackage model
 */
abstract class DBField extends ViewableData {
	
	protected $value;
	
	protected $tableName;
	
	protected $name;
	
	protected $arrayValue;
	
	/**
	 * The escape type for this field when inserted into a template - either "xml" or "raw".
	 *
	 * @var string
	 */
	public static $escape_type = 'raw';
	
	/**
	 * Subclass of {@link SearchFilter} for usage in {@link defaultSearchFilter()}.
	 *
	 * @var string
	 */
	public static $default_search_filter_class = 'PartialMatchFilter';
	
	/**
	 * @var $default mixed Default-value in the database.
	 * Might be overridden on DataObject-level, but still useful for setting defaults on
	 * already existing records after a db-build.
	 */
	protected $defaultVal;
	
	function __construct($name = null) {
		$this->name = $name;
		
		parent::__construct();
	}
	

	static function create() {
		Deprecation::notice('3.0', 'DBField::create() is deprecated as it clashes with Object::create(). Use DBField::create_field() instead.');

		return call_user_func_array(array('DBField', 'create_field'), func_get_args());
	}

	/**
	 * Create a DBField object that's not bound to any particular field.
	 * Useful for accessing the classes behaviour for other parts of your code.
	 */
	static function create_field($className, $value, $name = null, $object = null) {
		$dbField = Object::create($className, $name, $object);
		$dbField->setValue($value, null, false);
		return $dbField;
	}
	
	/**
	 * Set the name of this field.
	 * The name should never be altered, but it if was never given a name in the first place you can set a name.
	 * If you try an alter the name a warning will be thrown. 
	 */
	function setName($name) {
		if($this->name) {
			user_error("DBField::setName() shouldn't be called once a DBField already has a name.  It's partially immutable - it shouldn't be altered after it's given a value.", E_USER_WARNING);
		}
		$this->name = $name;
	}
	
	/**
	 * Returns the name of this field.
	 * @return string
	 */
	function getName() {
		return $this->name;
	}
	
	/**
	 * Returns the value of this field.
	 * @return mixed
	 */
	function getValue() {
		return $this->value;
	}
	
	/**
	 * Set the value on the field.
	 * Optionally takes the whole record as an argument,
	 * to pick other values.
	 *
	 * @param mixed $value
	 * @param array $record
	 */
	function setValue($value, $record = null) {
		$this->value = $value;
	}
	
	
	/**
	 * Determines if the field has a value which
	 * is not considered to be 'null' in
	 * a database context.
	 * 
	 * @return boolean
	 */
	public function exists() {
		return ($this->value);
	}
	
	/**
	 * Return an encoding of the given value suitable
	 * for inclusion in a SQL statement. If necessary,
	 * this should include quotes.
	 * 
	 * @param $value mixed The value to check
	 * @return string The encoded value
	 */
	function prepValueForDB($value) {
		if($value === null || $value === "" || $value === false) {
			return "null";
		} else {
			return DB::getConn()->prepStringForDB($value);
		}
	}
	
	/**
	 * Prepare the current field for usage in a 
	 * database-manipulation (works on a manipulation reference).
	 * 
	 * Make value safe for insertion into
	 * a SQL SET statement by applying addslashes() - 
	 * can also be used to apply special SQL-commands
	 * to the raw value (e.g. for GIS functionality).
	 * {@see prepValueForDB}
	 * 
	 * @param array $manipulation
	 */
	function writeToManipulation(&$manipulation) {
		$manipulation['fields'][$this->name] = $this->exists() ? $this->prepValueForDB($this->value) : $this->nullValue();
	}
	
	/**
	 * Add custom query parameters for this field,
	 * mostly SELECT statements for multi-value fields. 
	 * 
	 * By default, the ORM layer does a
	 * SELECT <tablename>.* which
	 * gets you the default representations
	 * of all columns.
	 *
	 * @param SS_Query $query
	 */
	function addToQuery(&$query) {
		
	}
	
	function setTable($tableName) {
		$this->tableName = $tableName;
	}
	
	/**
	 * @return string
	 */
	public function forTemplate() {
		return $this->XML();
	}

	function HTMLATT() {
		return Convert::raw2htmlatt($this->value);
	}
		
	function URLATT() {
		return urlencode($this->value);
	}

	function RAWURLATT() {
		return rawurlencode($this->value);
	}

	function ATT() {
		return Convert::raw2att($this->value);
	}
	
	function RAW() {
		return $this->value;
	}
	
	function JS() {
		return Convert::raw2js($this->value);
	}
	
	function HTML(){
		return Convert::raw2xml($this->value);
	}
	
	function XML(){
		return Convert::raw2xml($this->value);
	}
	
	/**
	 * Returns the value to be set in the database to blank this field.
	 * Usually it's a choice between null, 0, and ''
	 */
	function nullValue() {
		return "null";
	}

	/**
	 * Saves this field to the given data object.
	 */
	function saveInto($dataObject) {
		$fieldName = $this->name;
		if($fieldName) {
			$dataObject->$fieldName = $this->value;
		} else {
			user_error("DBField::saveInto() Called on a nameless '" . get_class($this) . "' object", E_USER_ERROR);
		}
	}
	
	/**
	 * Returns a FormField instance used as a default
	 * for form scaffolding.
	 *
	 * Used by {@link SearchContext}, {@link ModelAdmin}, {@link DataObject::scaffoldFormFields()}
	 * 
	 * @param string $title Optional. Localized title of the generated instance
	 * @return FormField
	 */
	public function scaffoldFormField($title = null) {
		$field = new TextField($this->name, $title);
		
		return $field;
	}
	
	/**
	 * Returns a FormField instance used as a default
	 * for searchform scaffolding.
	 *
	 * Used by {@link SearchContext}, {@link ModelAdmin}, {@link DataObject::scaffoldFormFields()}.
	 * 
	 * @param string $title Optional. Localized title of the generated instance
	 * @return FormField
	 */
	public function scaffoldSearchField($title = null) {
		return $this->scaffoldFormField($title);
	}
	
	/**
	 * @todo documentation
	 * 
	 * @todo figure out how we pass configuration parameters to
	 * search filters (note: parameter hack now in place to pass in the required full path - using $this->name won't work)
	 *
	 * @return SearchFilter
	 */
	public function defaultSearchFilter($name = false) {
		$name = ($name) ? $name : $this->name;
		$filterClass = $this->stat('default_search_filter_class');
		return new $filterClass($name);
	}
	
	/**
	 * Add the field to the underlying database.
	 */
	abstract function requireField();
	
	function debug() {
		return <<<DBG
<ul>
	<li><b>Name:</b>{$this->name}</li>
	<li><b>Table:</b>{$this->tableName}</li>
	<li><b>Value:</b>{$this->value}</li>
</ul>
DBG;
	}
	
}
