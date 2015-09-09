<?php

/**
 * @package framework
 * @subpackage model
 */

/**
 * Object representing a SQL SELECT query.
 * The various parts of the SQL query can be manipulated individually.
 *
 * @deprecated since version 4.0
 */
class SQLQuery extends SQLSelect {

	/**
	 * If this is true, this statement will delete rather than select.
	 * 
	 * @deprecated since version 4.0
	 * @var boolean
	 */
	protected $isDelete = false;

	/**
	 * @deprecated since version 4.0
	 */
	public function __construct($select = "*", $from = array(), $where = array(), $orderby = array(),
		$groupby = array(), $having = array(), $limit = array()
	) {
		parent::__construct($select, $from, $where, $orderby, $groupby, $having, $limit);
		Deprecation::notice('4.0', 'Use SQLSelect instead');
	}

	/**
	 * @deprecated since version 4.0
	 */
	public function setDelete($value) {
		Deprecation::notice('4.0', 'SQLQuery::setDelete is deprecated. Use toDelete instead');
		$this->isDelete = $value;
	}

	/**
	 * @deprecated since version 4.0
	 */
	public function getDelete() {
		Deprecation::notice('4.0', 'SQLQuery::getDelete is deprecated. Use SQLSelect or SQLDelete instead');
		return $this->isDelete;
	}

	public function sql(&$parameters = array()) {
		return $this->toAppropriateExpression()->sql($parameters);
	}
	
	/**
	 * Get helper class for flattening parameterised conditions
	 * 
	 * @return SQLQuery_ParameterInjector
	 */
	protected function getParameterInjector() {
		return Injector::inst()->get('SQLQuery_ParameterInjector');
	}

	/**
	 * Return a list of SQL where conditions (flattened as a list of strings)
	 * 
	 * @return array
	 */
	public function getWhere() {
		Deprecation::notice(
			'4.0',
			'SQLQuery::getWhere is non-parameterised for backwards compatibility. '.
			'Use ->toAppropriateExpression()->getWhere() instead'
		);
		$conditions = parent::getWhere();
		
		// This is where any benefits of parameterised queries die
		return $this
			->getParameterInjector()
			->injectConditions($conditions);
	}

	/**
	 * Convert this SQLQuery to a SQLExpression based on its 
	 * internal $delete state (Normally SQLSelect or SQLDelete)
	 * 
	 * @return SQLExpression
	 */
	public function toAppropriateExpression() {
		if($this->isDelete) {
			return parent::toDelete();
		} else {
			return parent::toSelect();
		}
	}

	public function toSelect() {
		if($this->isDelete) {
			user_error(
				'SQLQuery::toSelect called when $isDelete is true. Use ' .
				'toAppropriateExpression() instead',
				E_USER_WARNING
			);
		}
		return parent::toSelect();
	}

	public function toDelete() {
		if(!$this->isDelete) {
			user_error(
				'SQLQuery::toDelete called when $isDelete is false. Use ' .
				'toAppropriateExpression() instead',
				E_USER_WARNING
			);
		}
		parent::toDelete();
	}
}

/**
 * Provides conversion of parameterised SQL to flattened SQL strings
 * 
 * @deprecated since version 4.0
 */
class SQLQuery_ParameterInjector {
	
	public function __construct() {
		Deprecation::notice('4.0', "Use SQLSelect / SQLDelete instead of SQLQuery");
	}

	/**
	 * Given a list of parameterised conditions, return a flattened
	 * list of condition strings
	 * 
	 * @param array $conditions
	 * @return array
	 */
	public function injectConditions($conditions) {
		$result = array();
		foreach($conditions as $condition) {
			// Evaluate the result of SQLConditionGroup here
			if($condition instanceof SQLConditionGroup) {
				$predicate = $condition->conditionSQL($parameters);
				if(!empty($predicate)) {
					$result[] = $this->injectValues($predicate, $parameters);
				}
			} else {
				foreach($condition as $predicate => $parameters) {
					$result[] = $this->injectValues($predicate, $parameters);
				}
			}
		}
		return $result;
	}

	/**
	 * Merge parameters into a SQL prepared condition
	 * 
	 * @param string $sql
	 * @param array $parameters
	 * @return string
	 */
	protected function injectValues($sql, $parameters) {
		$segments = preg_split('/\?/', $sql);
		$joined = '';
		$inString = false;
		for($i = 0; $i < count($segments); $i++) {
			// Append next segment
			$joined .= $segments[$i];
			// Don't add placeholder after last segment
			if($i === count($segments) - 1) {
				break;
			}
			// check string escape on previous fragment
			if($this->checkStringTogglesLiteral($segments[$i])) {
				$inString = !$inString;
			}
			// Append placeholder replacement
			if($inString) {
				// Literal questionmark
				$joined .= '?';
				continue;
			}
			
			// Encode and insert next parameter
			$next = array_shift($parameters);
			if(is_array($next) && isset($next['value'])) {
				$next = $next['value'];
			}
			$joined .= "'".Convert::raw2sql($next)."'";
		}
		return $joined;
	}

	/**
	 * Determines if the SQL fragment either breaks into or out of a string literal
	 * by counting single quotes
	 * 
	 * Handles double-quote escaped quotes as well as slash escaped quotes
	 * 
	 * @param string $input The SQL fragment
	 * @return boolean True if the string breaks into or out of a string literal
	 */
	protected function checkStringTogglesLiteral($input) {
		// Remove escaped backslashes, count them!
		$input = preg_replace('/\\\\\\\\/', '', $input);
		// Count quotes
		$totalQuotes = substr_count($input, "'"); // Includes double quote escaped quotes
		$escapedQuotes = substr_count($input, "\\'");
		return (($totalQuotes - $escapedQuotes) % 2) !== 0;
	}
}
