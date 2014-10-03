<?php

/**
 * Builds a SQL query string from a SQLExpression object
 *
 * @package framework
 * @subpackage model
 */
class MySQLQueryBuilder extends DBQueryBuilder {

	/**
	 * Max number of rows allowed in MySQL
	 * @var string
	 */
	const MAX_ROWS = '18446744073709551615';

	/**
	 * Return the LIMIT clause ready for inserting into a query.
	 *
	 * @param SQLSelect $query The expression object to build from
	 * @param array $parameters Out parameter for the resulting query parameters
	 * @return string The finalised limit SQL fragment
	 */
	public function buildLimitFragment(SQLSelect $query, array &$parameters) {
		$nl = $this->getSeparator();

		// Ensure limit is given
		$limit = $query->getLimit();
		if(empty($limit)) return '';

		// For literal values return this as the limit SQL
		if (!is_array($limit)) {
			return "{$nl}LIMIT $limit";
		}

		// Assert that the array version provides the 'limit' key
		if (!array_key_exists('limit', $limit) || ($limit['limit'] !== null && ! is_numeric($limit['limit']))) {
			throw new InvalidArgumentException(
				'MySQLQueryBuilder::buildLimitSQL(): Wrong format for $limit: '. var_export($limit, true)
			);
		}

		if($limit['limit'] === null) {
			$limit['limit'] = self::MAX_ROWS;
		}

		// Format the array limit, given an optional start key
		$clause = "{$nl}LIMIT {$limit['limit']}";
		if(isset($limit['start']) && is_numeric($limit['start']) && $limit['start'] !== 0) {
			$clause .= " OFFSET {$limit['start']}";
		}

		return $clause;
	}

}
