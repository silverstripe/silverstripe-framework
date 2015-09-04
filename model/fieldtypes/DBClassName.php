<?php

/**
 * Represents a classname selector, which respects obsolete clasess.
 *
 * @package framework
 * @subpackage model
 */
class DBClassName extends Enum {

	/**
	 * Base classname of class to enumerate.
	 * If 'DataObject' then all classes are included.
	 * If empty, then the baseClass of the parent object will be used
	 *
	 * @var string|null
	 */
	protected $baseClass = null;

	/**
	 * Parent object
	 *
	 * @var DataObject|null
	 */
	protected $record = null;

	/**
	 * Classname spec cache for obsolete classes. The top level keys are the table, each of which contains
	 * nested arrays with keys mapped to field names. The values of the lowest level array are the classnames
	 *
	 * @var array
	 */
	protected static $classname_cache = array();

	/**
	 * Clear all cached classname specs. It's necessary to clear all cached subclassed names
	 * for any classes if a new class manifest is generated.
	 */
	public static function clear_classname_cache() {
		self::$classname_cache = array();
	}

	/**
	 * Create a new DBClassName field
	 *
	 * @param string $name Name of field
	 * @param string|null $baseClass Optional base class to limit selections
	 */
	public function __construct($name = null, $baseClass = null) {
		$this->setBaseClass($baseClass);
		parent::__construct($name);
	}

	/**
	 * @return void
	 */
	public function requireField() {
		$parts = array(
			'datatype' => 'enum',
			'enums' => $this->getEnumObsolete(),
			'character set' => 'utf8',
			'collate' => 'utf8_general_ci',
			'default' => $this->getDefault(),
			'table' => $this->getTable(),
			'arrayValue' => $this->arrayValue
		);

		$values = array(
			'type' => 'enum',
			'parts' => $parts
		);

		DB::require_field($this->getTable(), $this->getName(), $values);
	}

	/**
	 * Get the base dataclass for the list of subclasses
	 *
	 * @return string
	 */
	public function getBaseClass() {
		// Use explicit base class
		if($this->baseClass) {
			return $this->baseClass;
		}
		// Default to the basename of the record
		if($this->record) {
			return ClassInfo::baseDataClass($this->record);
		}
		// During dev/build only the table is assigned
		$tableClass = $this->getClassNameFromTable($this->getTable());
		if($tableClass) {
			return $tableClass;
		}
		// Fallback to global default
		return 'DataObject';
	}

	/**
	 * Assign the base class
	 *
	 * @param string $baseClass
	 * @return $this
	 */
	public function setBaseClass($baseClass) {
		$this->baseClass = $baseClass;
		return $this;
	}

	/**
	 * Given a table name, find the base data class
	 *
	 * @param string $table
	 * @return string|null
	 */
	protected function getClassNameFromTable($table) {
		if(empty($table)) {
			return null;
		}
		$class = ClassInfo::baseDataClass($table);
		if($class) {
			return $class;
		}
		// If there is no class for this table, strip table modifiers (_Live / _versions) off the end
		if(preg_match('/^(?<class>.+)(_[^_]+)$/i', $this->getTable(), $matches)) {
			return $this->getClassNameFromTable($matches['class']);
		}
		
		return null;
	}

	/**
	 * Get list of classnames that should be selectable
	 *
	 * @return array
	 */
	public function getEnum() {
		$classNames = ClassInfo::subclassesFor($this->getBaseClass());
		unset($classNames['DataObject']);
		return $classNames;
	}

	/**
	 * Get the list of classnames, including obsolete classes.
	 *
	 * If table or name are not set, or if it is not a valid field on the given table,
	 * then only known classnames are returned.
	 *
	 * Values cached in this method can be cleared via `DBClassName::clear_classname_cache();`
	 *
	 * @return array
	 */
	public function getEnumObsolete() {
		// Without a table or field specified, we can only retrieve known classes
		$table = $this->getTable();
		$name = $this->getName();
		if(empty($table) || empty($name)) {
			return $this->getEnum();
		}

		// Ensure the table level cache exists
		if(empty(self::$classname_cache[$table])) {
			self::$classname_cache[$table] = array();
		}
		
		// Check existing cache
		if(!empty(self::$classname_cache[$table][$name])) {
			return self::$classname_cache[$table][$name];
		}
		
		// Get all class names
		$classNames = $this->getEnum();
		if(DB::get_schema()->hasField($table, $name)) {
			$existing = DB::query("SELECT DISTINCT \"{$name}\" FROM \"{$table}\"")->column();
			$classNames = array_unique(array_merge($classNames, $existing));
		}

		// Cache and return
		self::$classname_cache[$table][$name] = $classNames;
		return $classNames;
	}

	public function setValue($value, $record = null, $markChanged = true) {
		parent::setValue($value, $record, $markChanged);

		if($record instanceof DataObject) {
			$this->record = $record;
		}
	}

	public function getDefault() {
		// Check for assigned default
		$default = parent::getDefault();
		if($default) {
			return $default;
		}
		
		// Fallback to first option
		$enum = $this->getEnum();
		return reset($enum);
	}
}
