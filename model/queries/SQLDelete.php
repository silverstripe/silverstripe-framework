<?php

/**
 * Object representing a SQL DELETE query.
 * The various parts of the SQL query can be manipulated individually.
 *
 * @package framework
 * @subpackage model
 */
class SQLDelete extends SQLConditionalExpression {

	/**
	 * List of tables to limit the delete to, if multiple tables
	 * are specified in the condition clause
	 *
	 * @see http://dev.mysql.com/doc/refman/5.0/en/delete.html
	 *
	 * @var array
	 */
	protected $delete = array();

	/**
	 * Construct a new SQLDelete.
	 *
	 * @param array|string $from An array of Tables (FROM clauses). The first one should be just the table name.
	 * Each should be ANSI quoted.
	 * @param array $where An array of WHERE clauses.
	 * @param array|string $delete The table(s) to delete, if multiple tables are queried from
	 * @return self Self reference
	 */
	public static function create($from = array(), $where = array(), $delete = array()) {
		return Injector::inst()->createWithArgs(__CLASS__, func_get_args());
	}

	/**
	 * Construct a new SQLDelete.
	 *
	 * @param array|string $from An array of Tables (FROM clauses). The first one should be just the table name.
	 * Each should be ANSI quoted.
	 * @param array $where An array of WHERE clauses.
	 * @param array|string $delete The table(s) to delete, if multiple tables are queried from
	 */
	function __construct($from = array(), $where = array(), $delete = array()) {
		parent::__construct($from, $where);
		$this->setDelete($delete);
	}

	/**
	 * List of tables to limit the delete to, if multiple tables
	 * are specified in the condition clause
	 *
	 * @return array
	 */
	public function getDelete() {
		return $this->delete;
	}

	/**
	 * Sets the list of tables to limit the delete to, if multiple tables
	 * are specified in the condition clause
	 *
	 * @param string|array $tables Escaped SQL statement, usually an unquoted table name
	 * @return self Self reference
	 */
	public function setDelete($tables) {
		$this->delete = array();
		return $this->addDelete($tables);
	}

	/**
	 * Sets the list of tables to limit the delete to, if multiple tables
	 * are specified in the condition clause
	 *
	 * @param string|array $tables Escaped SQL statement, usually an unquoted table name
	 * @return self Self reference
	 */
	public function addDelete($tables) {
		if(is_array($tables)) {
			$this->delete = array_merge($this->delete, $tables);
		} elseif(!empty($tables)) {
			$this->delete[str_replace(array('"','`'), '', $tables)] = $tables;
		}
	}
}
