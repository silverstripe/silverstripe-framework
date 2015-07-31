<?php

/**
 * Abstract base class for an object representing an SQL query.
 * The various parts of the SQL query can be manipulated individually.
 *
 * @package framework
 * @subpackage model
 */
abstract class SQLExpression {

	/**
	 * Keep an internal register of find/replace pairs to execute when it's time to actually get the
	 * query SQL.
	 * @var array
	 */
	protected $replacementsOld = array();

	/**
	 * Keep an internal register of find/replace pairs to execute when it's time to actually get the
	 * query SQL.
	 * @var array
	 */
	protected $replacementsNew = array();

	/**
	 * @deprecated since version 4.0
	 */
	public function __get($field) {
		Deprecation::notice('4.0', 'use get{Field} to get the necessary protected field\'s value');
		return $this->$field;
	}

	/**
	 * @deprecated since version 4.0
	 */
	public function __set($field, $value) {
		Deprecation::notice('4.0', 'use set{Field} to set the necessary protected field\'s value');
		return $this->$field = $value;
	}



	/**
	 * Swap some text in the SQL query with another.
	 *
	 * Note that values in parameters will not be replaced
	 *
	 * @param string $old The old text (escaped)
	 * @param string $new The new text (escaped)
	 */
	public function replaceText($old, $new) {
		$this->replacementsOld[] = $old;
		$this->replacementsNew[] = $new;
	}

	/**
	 * Return the generated SQL string for this query
	 *
	 * @todo Is it ok for this to consider parameters? Test cases here!
	 *
	 * @return string
	 */
	public function __toString() {
		try {
			$sql = $this->sql($parameters);
			if(!empty($parameters)) {
				$sql .= " <" . var_export($parameters, true) . ">";
			}
			return $sql;
		} catch(Exception $e) {
			return "<sql query>";
		}
	}

	/**
	 * Swap the use of one table with another.
	 *
	 * @param string $old Name of the old table (unquoted, escaped)
	 * @param string $new Name of the new table (unquoted, escaped)
	 */
	public function renameTable($old, $new) {
		$this->replaceText("`$old`", "`$new`");
		$this->replaceText("\"$old\"", "\"$new\"");
		$this->replaceText(Convert::symbol2sql($old), Convert::symbol2sql($new));
	}

	/**
	 * Determine if this query is empty, and thus cannot be executed
	 *
	 * @return bool Flag indicating that this query is empty
	 */
	abstract public function isEmpty();

	/**
	 * Generate the SQL statement for this query.
	 *
	 * @param array $parameters Out variable for parameters required for this query
	 * @return string The completed SQL query
	 */
	public function sql(&$parameters = array()) {
		// Build each component as needed
		$sql = DB::build_sql($this, $parameters);

		if(empty($sql)) return null;

		if($this->replacementsOld) {
			$sql = str_replace($this->replacementsOld, $this->replacementsNew, $sql);
		}

		return $sql;
	}

	/**
	 * Execute this query.
	 *
	 * @return SS_Query
	 */
	public function execute() {
		$sql = $this->sql($parameters);
		return DB::prepared_query($sql, $parameters);
	}

	/**
	 * Copies the query parameters contained in this object to another
	 * SQLExpression
	 *
	 * @param SQLExpression $expression The object to copy properties to
	 */
	protected function copyTo(SQLExpression $object) {
		$target = array_keys(get_object_vars($object));
		foreach(get_object_vars($this) as $variable => $value) {
			if(in_array($variable, $target)) {
				$object->$variable = $value;
			}
		}
	}
}
