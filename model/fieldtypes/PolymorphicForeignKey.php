<?php

/**
 * A special ForeignKey class that handles relations with arbitrary class types
 *
 * @package framework
 * @subpackage model
 */
class PolymorphicForeignKey extends ForeignKey implements CompositeDBField {

	/**
	 * @var boolean $isChanged
	 */
	protected $isChanged = false;

	/**
	 * Value of relation class
	 *
	 * @var string
	 */
	protected $classValue = null;

	/**
	 * Field definition cache for compositeDatabaseFields
	 *
	 * @var string
	 */
	protected static $classname_spec_cache = array();

	/**
	 * Clear all cached classname specs. It's necessary to clear all cached subclassed names
	 * for any classes if a new class manifest is generated.
	 */
	public static function clear_classname_spec_cache() {
		self::$classname_spec_cache = array();
	}

	public function scaffoldFormField($title = null, $params = null) {
		// Opt-out of form field generation - Scaffolding should be performed on
		// the has_many end, or set programatically.
		// @todo - Investigate suitable FormField
		return null;
	}

	public function requireField() {
		$fields = $this->compositeDatabaseFields();
		if($fields) foreach($fields as $name => $type){
			DB::requireField($this->tableName, $this->name.$name, $type);
		}
	}

	public function writeToManipulation(&$manipulation) {

		// Write ID, checking that the value is valid
		$manipulation['fields'][$this->name . 'ID'] = $this->exists()
			? $this->prepValueForDB($this->getIDValue())
			: $this->nullValue();

		// Write class
		$classObject = DBField::create_field('Enum', $this->getClassValue(), $this->name . 'Class');
		$classObject->writeToManipulation($manipulation);
	}

	public function addToQuery(&$query) {
		parent::addToQuery($query);
		$query->selectField(
			"\"{$this->tableName}\".\"{$this->name}ID\"",
			"{$this->name}ID"
		);
		$query->selectField(
			"\"{$this->tableName}\".\"{$this->name}Class\"",
			"{$this->name}Class"
		);
	}

	/**
	 * Get the value of the "Class" this key points to
	 *
	 * @return string Name of a subclass of DataObject
	 */
	public function getClassValue() {
		return $this->classValue;
	}

	/**
	 * Set the value of the "Class" this key points to
	 *
	 * @param string $class Name of a subclass of DataObject
	 * @param boolean $markChanged Mark this field as changed?
	 */
	public function setClassValue($class, $markChanged = true) {
		$this->classValue = $class;
		if($markChanged) $this->isChanged = true;
	}

	/**
	 * Gets the value of the "ID" this key points to
	 *
	 * @return integer
	 */
	public function getIDValue() {
		return parent::getValue();
	}

	/**
	 * Sets the value of the "ID" this key points to
	 *
	 * @param integer $id
	 * @param boolean $markChanged Mark this field as changed?
	 */
	public function setIDValue($id, $markChanged = true) {
		parent::setValue($id);
		if($markChanged) $this->isChanged = true;
	}

	public function setValue($value, $record = null, $markChanged = true) {
		$idField = "{$this->name}ID";
		$classField = "{$this->name}Class";

		// Check if an object is assigned directly
		if($value instanceof DataObject) {
			$record = array(
				$idField => $value->ID,
				$classField => $value->class
			);
		}

		// Convert an object to an array
		if($record instanceof DataObject) {
			$record = $record->getQueriedDatabaseFields();
		}

		// Use $value array if record is missing
		if(empty($record) && is_array($value)) {
			$record = $value;
		}

		// Inspect presented values
		if(isset($record[$idField]) && isset($record[$classField])) {
			if(empty($record[$idField]) || empty($record[$classField])) {
				$this->setIDValue($this->nullValue(), $markChanged);
				$this->setClassValue('', $markChanged);
			} else {
				$this->setClassValue($record[$classField], $markChanged);
				$this->setIDValue($record[$idField], $markChanged);
			}
		}
	}

	public function getValue() {
		if($this->exists()) {
			return DataObject::get_by_id($this->getClassValue(), $this->getIDValue());
		}
	}

	public function compositeDatabaseFields() {

		// Ensure the table level cache exists
		if(empty(self::$classname_spec_cache[$this->tableName])) {
			self::$classname_spec_cache[$this->tableName] = array();
		}

		// Ensure the field level cache exists
		if(empty(self::$classname_spec_cache[$this->tableName][$this->name])) {

			// Get all class names
			$classNames = ClassInfo::subclassesFor('DataObject');
			unset($classNames['DataObject']);

			$schema = DB::get_schema();
			if($schema->hasField($this->tableName, "{$this->name}Class")) {
				$existing = DB::query("SELECT DISTINCT \"{$this->name}Class\" FROM \"{$this->tableName}\"")->column();
				$classNames = array_unique(array_merge($classNames, $existing));
			}

			self::$classname_spec_cache[$this->tableName][$this->name]
				= "Enum(array('" . implode("', '", array_filter($classNames)) . "'))";
		}

		return array(
			'ID' => 'Int',
			'Class' => self::$classname_spec_cache[$this->tableName][$this->name]
		);
	}

	public function isChanged() {
		return $this->isChanged;
	}

	public function exists() {
		return $this->getClassValue() && $this->getIDValue();
	}
}
