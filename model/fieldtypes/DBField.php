<?php
/**
 * Single field in the database.
 *
 * Every field from the database is represented as a sub-class of DBField.
 *
 * <b>Multi-value DBField objects</b>
 *
 * Sometimes you will want to make DBField classes that don't have a 1-1 match
 * to database fields.  To do this, there are a number of fields for you to
 * overload:
 *
 *  - Overload {@link writeToManipulation} to add the appropriate references to
 *		the INSERT or UPDATE command
 *  - Overload {@link addToQuery} to add the appropriate items to a SELECT
 *		query's field list
 *  - Add appropriate accessor methods
 *
 * <b>Subclass Example</b>
 *
 * The class is easy to overload with custom types, e.g. the MySQL "BLOB" type
 * (http://dev.mysql.com/doc/refman/5.0/en/blob.html).
 *
 * <code>
 * class Blob extends DBField {
 * 	function requireField() {
 * 		DB::requireField($this->tableName, $this->name, "blob");
 *  }
 * }
 * </code>
 *
 * @todo remove MySQL specific code from subclasses
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
	 * @config
	 */
	private static $escape_type = 'raw';

	/**
	 * Subclass of {@link SearchFilter} for usage in {@link defaultSearchFilter()}.
	 *
	 * @var string
	 * @config
	 */
	private static $default_search_filter_class = 'PartialMatchFilter';

	/**
	 * @var $default mixed Default-value in the database.
	 * Might be overridden on DataObject-level, but still useful for setting defaults on
	 * already existing records after a db-build.
	 */
	protected $defaultVal;

	public function __construct($name = null) {
		$this->name = $name;

		parent::__construct();
	}

	/**
	 * Create a DBField object that's not bound to any particular field.
	 *
	 * Useful for accessing the classes behaviour for other parts of your code.
	 *
	 * @param string $className class of field to construct
	 * @param mixed $value value of field
	 * @param string $name Name of field
	 * @param mixed $object Additional parameter to pass to field constructor
	 * @return DBField
	 */
	public static function create_field($className, $value, $name = null, $object = null) {
		$dbField = Object::create($className, $name, $object);
		$dbField->setValue($value, null, false);

		return $dbField;
	}

	/**
	 * Set the name of this field.
	 *
	 * The name should never be altered, but it if was never given a name in
	 * the first place you can set a name.
	 *
	 * If you try an alter the name a warning will be thrown.
	 *
	 * @param string $name
	 *
	 * @return DBField
	 */
	public function setName($name) {
		if($this->name && $this->name !== $name) {
			user_error("DBField::setName() shouldn't be called once a DBField already has a name."
				. "It's partially immutable - it shouldn't be altered after it's given a value.", E_USER_WARNING);
		}

		$this->name = $name;

		return $this;
	}

	/**
	 * Returns the name of this field.
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the value of this field.
	 *
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Set the value on the field.
	 *
	 * Optionally takes the whole record as an argument, to pick other values.
	 *
	 * @param mixed $value
	 * @param array $record
	 */
	public function setValue($value, $record = null) {
		$this->value = $value;
	}


	/**
	 * Determines if the field has a value which is not considered to be 'null'
	 * in a database context.
	 *
	 * @return boolean
	 */
	public function exists() {
		return (bool)$this->value;
	}

	/**
	 * Return the transformed value ready to be sent to the database. This value
	 * will be escaped automatically by the prepared query processor, so it
	 * should not be escaped or quoted at all.
	 *
	 * The field values could also be in paramaterised format, such as
	 * array('MAX(?,?)' => array(42, 69)), allowing the use of raw SQL values such as
	 * array('NOW()' => array()).
	 *
	 * @see SQLWriteExpression::addAssignments for syntax examples
	 *
	 * @param $value mixed The value to check
	 * @return mixed The raw value, or escaped parameterised details
	 */
	public function prepValueForDB($value) {
		if($value === null || $value === "" || $value === false) {
			return null;
		} else {
			return $value;
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
	public function writeToManipulation(&$manipulation) {
		$manipulation['fields'][$this->name] = $this->exists()
			? $this->prepValueForDB($this->value) : $this->nullValue();
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
	public function addToQuery(&$query) {

	}

	public function setTable($tableName) {
		$this->tableName = $tableName;
	}

	/**
	 * @return string
	 */
	public function forTemplate() {
		return $this->XML();
	}

	public function HTMLATT() {
		return Convert::raw2htmlatt($this->RAW());
	}

	public function URLATT() {
		return urlencode($this->RAW());
	}

	public function RAWURLATT() {
		return rawurlencode($this->RAW());
	}

	public function ATT() {
		return Convert::raw2att($this->RAW());
	}

	public function RAW() {
		return $this->value;
	}

	public function JS() {
		return Convert::raw2js($this->RAW());
	}

	/**
	 * Return JSON encoded value
	 * @return string
	 */
	public function JSON() {
		return Convert::raw2json($this->RAW());
	}

	public function HTML(){
		return Convert::raw2xml($this->RAW());
	}

	public function XML(){
		return Convert::raw2xml($this->RAW());
	}

	/**
	 * Returns the value to be set in the database to blank this field.
	 * Usually it's a choice between null, 0, and ''
	 *
	 * @return mixed
	 */
	public function nullValue() {
		return null;
	}

	/**
	 * Saves this field to the given data object.
	 */
	public function saveInto($dataObject) {
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
	 *       search filters (note: parameter hack now in place to pass in the required full path - using $this->name
	 *       won't work)
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
	abstract public function requireField();

	public function debug() {
		return <<<DBG
<ul>
	<li><b>Name:</b>{$this->name}</li>
	<li><b>Table:</b>{$this->tableName}</li>
	<li><b>Value:</b>{$this->value}</li>
</ul>
DBG;
	}

	public function __toString() {
		return $this->forTemplate();
	}
}
