<?php

/**
 * A result-set from a MySQL database.
 *
 * @package framework
 * @subpackage model
 */
class MySQLQuery extends SS_Query {

	/**
	 * The MySQLDatabase object that created this result set.
	 * @var MySQLDatabase
	 */
	protected $database;

	/**
	 * The internal MySQL handle that points to the result set.
	 * @var resource
	 */
	protected $handle;

	/**
	 * Hook the result-set given into a Query class, suitable for use by 
	 * SilverStripe.
	 *
	 * @param database $database The database object that created this query.
	 * @param handle $handle the internal mysql handle that is points to the resultset.
	 */
	public function __construct(MySQLDatabase $database, $handle) {
		$this->database = $database;
		$this->handle = $handle;
	}

	public function __destruct() {
		if(is_object($this->handle)) {
			$this->handle->free();
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function seek($row) {
		if(is_object($this->handle)) {
			return $this->handle->data_seek($row);
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function numRecords() {
		if(is_object($this->handle)) {
			return $this->handle->num_rows;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function nextRecord() {
		if(is_object($this->handle) && ($data = $this->handle->fetch_assoc())) {
			return $data;
		} else {
			return false;
		}
	}
}
