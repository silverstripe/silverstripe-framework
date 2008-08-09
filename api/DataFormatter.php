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
	 * Return an array of the extensions that this data formatter supports
	 */
	abstract function supportedExtensions();
	
	
	/**
	 * Convert a single data object to this format.  Return a string.
	 * @todo Add parameters for things like selecting output columns
	 */
	abstract function convertDataObject(DataObjectInterface $do);

	/**
	 * Convert a data object set to this format.  Return a string.
	 * @todo Add parameters for things like selecting output columns
	 */
	abstract function convertDataObjectSet(DataObjectSet $set);
		
}