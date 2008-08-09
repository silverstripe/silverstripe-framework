<?php

/**
 * A DataFormatter object handles transformation of data from Sapphire model objects to a particular output format, and vice versa.
 * This is most commonly used in developing RESTful APIs.
 */

abstract class DataFormatter extends Object {
	
	/**
	 * Set priority from 0-100.
	 * If multiple formatters for the same extension exist,
	 * we select the one with highest priority.
	 *
	 * @var int
	 */
	public static $priority = 50;
	
	/**
	 * Follow relations for the {@link DataObject} instances
	 * ($has_one, $has_many, $many_many).
	 * Set to "0" to disable relation output.
	 * 
	 * @todo Support more than one nesting level
	 *
	 * @var int
	 */
	public $relationDepth = 1;
	
	/**
	 * Allows overriding of the fields which are rendered for the
	 * processed dataobjects. By default, this includes all
	 * fields in {@link DataObject::inheritedDatabaseFields()}.
	 *
	 * @var array
	 */
	protected $customFields = null;
	
	/**
	 * Get a DataFormatter object suitable for handling the given file extension
	 */
	static function for_extension($extension) {
		$classes = ClassInfo::subclassesFor("DataFormatter");
		array_shift($classes);
		$sortedClasses = array();
		foreach($classes as $class) {
			$sortedClasses[$class] = singleton($class)->stat('priority');
		}
		arsort($sortedClasses);
		foreach($sortedClasses as $className => $priority) {
			$formatter = singleton($className);
			if(in_array($extension, $formatter->supportedExtensions())) {
				return $formatter;
			}
		}
	}
	
	/**
	 * @param array $fields
	 */
	public function setCustomFields($fields) {
		$this->customFields = $fields;
	}

	/**
	 * @return array
	 */
	public function getCustomFields() {
		return $this->customFields;
	}
	
	/**
	 * Returns all fields on the object which should be shown
	 * in the output. Can be customised through {@link self::setCustomFields()}.
	 *
	 * @todo Allow for custom getters on the processed object (currently filtered through inheritedDatabaseFields)
	 * @todo Field level permission checks
	 * 
	 * @param DataObject $obj
	 * @return array
	 */
	protected function getFieldsForObj($obj) {
		$dbFields = array();
		
		// if custom fields are specified, only select these
		if($this->customFields) {
			foreach($this->customFields as $fieldName) {
				// @todo Possible security risk by making methods accessible - implement field-level security
				if($obj->hasField($fieldName) || $obj->hasMethod("get{$fieldName}")) $dbFields[$fieldName] = $fieldName; 
			}
		} else {
			// by default, all database fields are selected
			$dbFields = $obj->inheritedDatabaseFields();
		}
		
		// add default required fields
		$dbFields = array_merge($dbFields, array('ID'=>'Int'));
		
		return $dbFields;
	}
	
	/** 
	 * Return an array of the extensions that this data formatter supports
	 */
	abstract function supportedExtensions();
	
	
	/**
	 * Convert a single data object to this format.  Return a string.
	 */
	abstract function convertDataObject(DataObjectInterface $do);

	/**
	 * Convert a data object set to this format.  Return a string.
	 */
	abstract function convertDataObjectSet(DataObjectSet $set);
		
}