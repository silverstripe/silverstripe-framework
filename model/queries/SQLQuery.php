<?php

/**
 * Object representing a SQL SELECT query.
 * The various parts of the SQL query can be manipulated individually.
 *
 * @package framework
 * @subpackage model
 * @deprecated since version 3.3
 */
class SQLQuery extends SQLSelect {

	/**
	 * @deprecated since version 3.3
	 */
	public function __construct($select = "*", $from = array(), $where = array(), $orderby = array(),
		$groupby = array(), $having = array(), $limit = array()
	) {
		parent::__construct($select, $from, $where, $orderby, $groupby, $having, $limit);
		Deprecation::notice('3.3', 'Use SQLSelect instead');
	}

	/**
	 * @deprecated since version 3.2
	 */
	public function setDelete($value) {
		$message = 'SQLQuery->setDelete no longer works. Create a SQLDelete object instead, or use toDelete()';
		Deprecation::notice('3.2', $message);
		throw new BadMethodCallException($message);
	}

	/**
	 * @deprecated since version 3.2
	 */
	public function getDelete() {
		Deprecation::notice('3.2', 'Use SQLDelete object instead');
		return false;
	}
}
