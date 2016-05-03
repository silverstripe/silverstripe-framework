<?php

namespace SilverStripe\Model\FieldType;


use Object;

use SilverStripe\Model\DataObject;
use SilverStripe\Model\DB;


/**
 * Apply this interface to any {@link DBField} that doesn't have a 1-1 mapping with a database field.
 * This includes multi-value fields and transformed fields
 *
 * @todo Unittests for loading and saving composite values (see GIS module for existing similiar unittests)
 *
 * Example with a combined street name and number:
 * <code>
* class Street extends DBComposite {
* 	private static $composite_db = return array(
* 		"Number" => "Int",
* 		"Name" => "Text"
* 	);
* }
 * </code>
 *
 * @package framework
 * @subpackage model
 */
abstract class DBComposite extends DBField {

	/**
	 * Similiar to {@link DataObject::$db},
	 * holds an array of composite field names.
	 * Don't include the fields "main name",
	 * it will be prefixed in {@link requireField()}.
	 *
	 * @config
	 * @var array
	 */
	private static $composite_db = array();

	/**
	 * Either the parent dataobject link, or a record of saved values for each field
	 *
	 * @var array|DataObject
	 */
	protected $record = array();

	/**
	 * Write all nested fields into a manipulation
	 *
	 * @param array $manipulation
	 */
	public function writeToManipulation(&$manipulation) {
		foreach($this->compositeDatabaseFields() as $field => $spec) {
			// Write sub-manipulation
			$fieldObject = $this->dbObject($field);
			$fieldObject->writeToManipulation($manipulation);
		}
	}

	/**
	 * Add all columns which are defined through {@link requireField()}
	 * and {@link $composite_db}, or any additional SQL that is required
	 * to get to these columns. Will mostly just write to the {@link SQLSelect->select}
	 * array.
	 *
	 * @param SQLSelect $query
	 */
	public function addToQuery(&$query) {
		parent::addToQuery($query);

		foreach($this->compositeDatabaseFields() as $field => $spec) {
			$table = $this->getTable();
			$key = $this->getName() . $field;
			if($table) {
				$query->selectField("\"{$table}\".\"{$key}\"");
			} else {
				$query->selectField("\"{$key}\"");
			}
		}
	}

	/**
	 * Return array in the format of {@link $composite_db}.
	 * Used by {@link DataObject->hasOwnDatabaseField()}.
	 *
	 * @return array
	 */
	public function compositeDatabaseFields() {
		return $this->config()->composite_db;
	}


	public function isChanged() {
		// When unbound, use the local changed flag
		if(! ($this->record instanceof DataObject) ) {
			return $this->isChanged;
		}

		// Defer to parent record
		foreach($this->compositeDatabaseFields() as $field => $spec) {
			$key = $this->getName() . $field;
			if($this->record->isChanged($key)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Composite field defaults to exists only if all fields have values
	 *
	 * @return boolean
	 */
	public function exists() {
		// By default all fields
		foreach($this->compositeDatabaseFields() as $field => $spec) {
			$fieldObject = $this->dbObject($field);
			if(!$fieldObject->exists()) {
				return false;
			}
		}
		return true;
	}

	public function requireField() {
		foreach($this->compositeDatabaseFields() as $field => $spec){
			$key = $this->getName() . $field;
			DB::requireField($this->tableName, $key, $spec);
		}
	}

	/**
	 * Assign the given value.
	 * If $record is assigned to a dataobject, this field becomes a loose wrapper over
	 * the records on that object instead.
	 *
	 * {@see ViewableData::obj}
	 *
	 * @param mixed $value
	 * @param mixed $record Parent object to this field, which could be a DataObject, record array, or other
	 * @param bool $markChanged
	 */
	public function setValue($value, $record = null, $markChanged = true) {
		$this->isChanged = $markChanged;

		// When given a dataobject, bind this field to that
		if($record instanceof DataObject) {
			$this->bindTo($record);
			$record = null;
		}

		foreach($this->compositeDatabaseFields() as $field => $spec) {
			// Check value
			if($value instanceof DBComposite) {
				// Check if saving from another composite field
				$this->setField($field, $value->getField($field));

			} elseif(isset($value[$field])) {
				// Check if saving from an array
				$this->setField($field, $value[$field]);
			}

			// Load from $record
			$key = $this->getName() . $field;
			if(is_array($record) && isset($record[$key])) {
				$this->setField($field, $record[$key]);
			}
		}
	}

	/**
	 * Bind this field to the dataobject, and set the underlying table to that of the owner
	 *
	 * @param DataObject $dataObject
	 */
	public function bindTo($dataObject) {
		$this->record = $dataObject;
	}

	public function saveInto($dataObject) {
		foreach($this->compositeDatabaseFields() as $field => $spec) {
			// Save into record
			$key = $this->getName() . $field;
			$dataObject->setField($key, $this->getField($field));
		}
	}

	/**
	 * get value of a single composite field
	 *
	 * @param string $field
	 * @return mixed
	 */
	public function getField($field) {
		// Skip invalid fields
		$fields = $this->compositeDatabaseFields();
		if(!isset($fields[$field])) {
			return null;
		}

		// Check bound object
		if($this->record instanceof DataObject) {
			$key = $this->getName().$field;
			return $this->record->getField($key);
		}

		// Check local record
		if(isset($this->record[$field])) {
			return $this->record[$field];
		}
		return null;
	}

	public function hasField($field) {
		$fields = $this->compositeDatabaseFields();
		return isset($fields[$field]);
	}

	/**
	 * Set value of a single composite field
	 *
	 * @param string $field
	 * @param mixed $value
	 * @param bool $markChanged
	 */
	public function setField($field, $value, $markChanged = true) {
		// Skip non-db fields
		if(!$this->hasField($field)) {
			return;
		}

		// Set changed
		if($markChanged) {
			$this->isChanged = true;
		}

		// Set bound object
		if($this->record instanceof DataObject) {
			$key = $this->getName() . $field;
			return $this->record->setField($key, $value);
		}

		// Set local record
		$this->record[$field] = $value;
	}

	/**
	 * Get a db object for the named field
	 *
	 * @param string $field Field name
	 * @return DBField|null
	 */
	public function dbObject($field) {
		$fields = $this->compositeDatabaseFields();
		if(!isset($fields[$field])) {
			return null;
		}

		// Build nested field
		$key = $this->getName() . $field;
		$spec = $fields[$field];
		$fieldObject = Object::create_from_string($spec, $key);
		$fieldObject->setValue($this->getField($field), null, false);
		return $fieldObject;
	}

	public function castingHelper($field) {
		$fields = $this->compositeDatabaseFields();
		if(isset($fields[$field])) {
			return $fields[$field];
		}


		parent::castingHelper($field);
	}

}
