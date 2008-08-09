<?php

/**
 * A DataFormatter object handles transformation of data from Sapphire model objects to a particular output format, and vice versa.
 * This is most commonly used in developing RESTful APIs.
 */

abstract class DataFormatter extends Object {
	/**
	 * Get a DataFormatter object suitable for handling the given file extension
	 */
	static function for_extension($extension) {
		$classes = ClassInfo::subclassesFor("DataFormatter");
		array_shift($classes);
		
		foreach($classes as $class) {
			$formatter = singleton($class);
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