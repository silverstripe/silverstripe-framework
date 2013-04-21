<?php
/**
 * Apply this interface to any {@link DBField} that doesn't have a 1-1 mapping with a database field.
 * This includes multi-value fields and transformed fields
 *
 * @todo Unittests for loading and saving composite values (see GIS module for existing similiar unittests)
 * 
 * Example with a combined street name and number:
 * <code>
* class Street extends DBField implements CompositeDBField {
* 	protected $streetNumber;
* 	protected $streetName;
* 	protected $isChanged = false;
* 	static $composite_db = return array(
* 		"Number" => "Int",
* 		"Name" => "Text"
* 	);
* 		
* 	function requireField() {
* 		DB::requireField($this->tableName, "{$this->name}Number", 'Int');
* 		DB::requireField($this->tableName, "{$this->name}Name", 'Text');
* 	}
* 
* 	function writeToManipulation(&$manipulation) {
* 		if($this->getStreetName()) {
* 			$manipulation['fields']["{$this->name}Name"] = $this->prepValueForDB($this->getStreetName());
* 		} else {
* 			$manipulation['fields']["{$this->name}Name"] = DBField::create_field('Varchar', $this->getStreetName())
* 				->nullValue();
* 		}
* 		
* 		if($this->getStreetNumber()) {
* 			$manipulation['fields']["{$this->name}Number"] = $this->prepValueForDB($this->getStreetNumber());
* 		} else {
* 			$manipulation['fields']["{$this->name}Number"] = DBField::create_field('Int', $this->getStreetNumber())
* 				->nullValue();
* 		}
* 	}
* 	
* 	function addToQuery(&$query) {
* 		parent::addToQuery($query);
* 		$query->setSelect("{$this->name}Number");
* 		$query->setSelect("{$this->name}Name");
* 	}
* 	
* 	function setValue($value, $record = null, $markChanged=true) {
* 		if ($value instanceof Street && $value->exists()) {
* 			$this->setStreetName($value->getStreetName(), $markChanged);
* 			$this->setStreetNumber($value->getStreetNumber(), $markChanged);
* 			if($markChanged) $this->isChanged = true;
* 		} else if($record && isset($record[$this->name . 'Name']) && isset($record[$this->name . 'Number'])) {
* 			if($record[$this->name . 'Name'] && $record[$this->name . 'Number']) {
* 				$this->setStreetName($record[$this->name . 'Name'], $markChanged);
* 				$this->setStreetNumber($record[$this->name . 'Number'], $markChanged);
* 			} 
* 			if($markChanged) $this->isChanged = true;
* 		} else if (is_array($value)) {
* 			if (array_key_exists('Name', $value)) {
* 				$this->setStreetName($value['Name'], $markChanged);
* 			}
* 			if (array_key_exists('Number', $value)) {
* 				$this->setStreetNumber($value['Number'], $markChanged);
* 			}
* 			if($markChanged) $this->isChanged = true;
* 		}
* 	}
* 	
* 	function setStreetNumber($val, $markChanged=true) {
* 		$this->streetNumber = $val;
* 		if($markChanged) $this->isChanged = true;
* 	}
* 	
* 	function setStreetName($val, $markChanged=true) {
* 		$this->streetName = $val;
* 		if($markChanged) $this->isChanged = true;
* 	}
* 	
* 	function getStreetNumber() {
* 		return $this->streetNumber;
* 	}
* 	
* 	function getStreetName() {
* 		return $this->streetName;
* 	}
* 	
* 	function isChanged() {
* 		return $this->isChanged;
* 	}
* 	
* 	function exists() {
* 		return ($this->getStreetName() || $this->getStreetNumber());
* 	}
* }
 * </code>
 *
 * @package framework
 * @subpackage model
 */
interface CompositeDBField {
	
	/**
	 * Similiar to {@link DataObject::$db},
	 * holds an array of composite field names.
	 * Don't include the fields "main name",
	 * it will be prefixed in {@link requireField()}.
	 * 
	 * @var array $composite_db
	 */
	//static $composite_db;
	
	/**
	 * Set the value of this field in various formats.
	 * Used by {@link DataObject->getField()}, {@link DataObject->setCastedField()}
	 * {@link DataObject->dbObject()} and {@link DataObject->write()}.
	 * 
	 * As this method is used both for initializing the field after construction,
	 * and actually changing its values, it needs a {@link $markChanged}
	 * parameter. 
	 * 
	 * @param DBField|array $value
	 * @param DataObject|array $record An array or object that this field is part of
	 * @param boolean $markChanged Indicate wether this field should be marked changed. 
	 *  Set to FALSE if you are initializing this field after construction, rather
	 *  than setting a new value.
	 */
	public function setValue($value, $record = null, $markChanged = true);
	
	/**
	 * Used in constructing the database schema.
	 * Add any custom properties defined in {@link $composite_db}.
	 * Should make one or more calls to {@link DB::requireField()}.
	 */
	//abstract public function requireField();
	
	/**
	 * Add the custom internal values to an INSERT or UPDATE
	 * request passed through the ORM with {@link DataObject->write()}.
	 * Fields are added in $manipulation['fields']. Please ensure
	 * these fields are escaped for database insertion, as no
	 * further processing happens before running the query.
	 * Use {@link DBField->prepValueForDB()}.
	 * Ensure to write NULL or empty values as well to allow 
	 * unsetting a previously set field. Use {@link DBField->nullValue()}
	 * for the appropriate type.
	 * 
	 * @param array $manipulation
	 */
	public function writeToManipulation(&$manipulation);
	
	/**
	 * Add all columns which are defined through {@link requireField()}
	 * and {@link $composite_db}, or any additional SQL that is required
	 * to get to these columns. Will mostly just write to the {@link SQLQuery->select}
	 * array.
	 * 
	 * @param SQLQuery $query
	 */
	public function addToQuery(&$query);
	
	/**
	 * Return array in the format of {@link $composite_db}.
	 * Used by {@link DataObject->hasOwnDatabaseField()}.
	 * @return array
	 */
	public function compositeDatabaseFields();
	
	/**
	 * Determines if the field has been changed since its initialization.
	 * Most likely relies on an internal flag thats changed when calling
	 * {@link setValue()} or any other custom setters on the object.
	 * 
	 * @return boolean
	 */
	public function isChanged();
	
	/**
	 * Determines if any of the properties in this field have a value,
	 * meaning at least one of them is not NULL.
	 * 
	 * @return boolean
	 */
	public function exists();
	
}
