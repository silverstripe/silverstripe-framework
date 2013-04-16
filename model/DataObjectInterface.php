<?php
/** 
 * DataObjectInterface is an interface that other data systems in your application can implement in order to behave in
 * a manner similar to DataObject.
 *
 * In addition to the methods defined below, the data of the object should be directly accessible as fields.
 * @package framework
 * @subpackage model
 */
interface DataObjectInterface {
	/**
	 * Create a new data object, not yet in the database.  To load an object into the database, a null object should be
	 * constructed, its fields set, and the write() method called.
	 */
	public function __construct();

	/**
	 * Write the current object back to the database.  It should know whether this is a new object, in which case this
	 * would be an insert command, or if this is an existing object queried from the database, in which case thes would
	 * be 
	 */
	public function write();
	
	/**
	 * Remove this object from the database.  Doesn't do anything if this object isn't in the database.
	 */
	public function delete();
	
	/**
	 * Get the named field.
	 * This function is sometimes called explicitly by the form system, so you need to define it, even if you use the
	 * default field system.
	 */
	public function __get($fieldName);
	
	/**
	 * Save content from a form into a field on this data object.
	 * Since the data comes straight from a form it can't be trusted and will need to be validated / escaped.'
	 */
	public function setCastedField($fieldName, $val);
	
}
