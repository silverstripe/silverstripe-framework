<?php

/** 
 * DataObjectInterface is an interface that other data systems in your application can implement in order to behave in a manner
 * similar to DataObject.
 *
 * In addition to the methods defined below, the data of the object should be directly accessible as fields.
 */
interface DataObjectInterface {
	/**
	 * Create a new data object, not yet in the database.  To load an object into the database, a null object should be constructed,
	 * its fields set, and the write() method called.
	 */
	function __construct();

	/**
	 * Perform a search query on this data source
	 *
	 * @param $filter A filter expression of some kind, in SQL format.
	 * @param $sort A sort expression, in SQL format.
	 * @param $join A join expression.  May or may not be relevant.
	 * @param $limit A limit expression, either "(count)", or "(start), (count)"
	 */
	function instance_get($filter = "", $sort = "", $join = "", $limit = "", $containerClass = "DataObjectSet");

	/**
	 * Retrieve a single record from this data source
	 *
	 * @param $filter A filter expression of some kind, in SQL format.
	 * @param $sort A sort expression, in SQL format.
	 * @param $join A join expression.  May or may not be relevant.
	 * @param $limit A limit expression, either "(count)", or "(start), (count)"
	 */
	function instance_get_one($filter, $sort = "");

	/**
	 * Write the current object back to the database.  It should know whether this is a new object, in which case this would
	 * be an insert command, or if this is an existing object queried from the database, in which case thes would be 
	 */
	function write();
	
	/**
	 * Remove this object from the database.  Doesn't do anything if this object isn't in the database.
	 */
	function delete();
	
}