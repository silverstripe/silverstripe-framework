<?php
/**
 * Format a SQL Query for better readable output in HTML or Plaintext.
 * Its a simple string parser, not a full tokenizer - so formatting
 * is not aware of the SQL syntax. This means we have to be conservative
 * with modifying the SQL string.
 *
 * @package framework
 * @subpackage parsers
 * @author Ingo Schommer, Silverstripe Ltd. (<firstname>@silverstripe.com)
 */
class SQLFormatter extends Object {

	protected static $newline_before_tokens = array(
		'SELECT',
		'UPDATE',
		'INSERT',
		'DELETE',
		'FROM',
		'INNER JOIN',
		'FULL JOIN',
		'LEFT JOIN',
		'RIGHT JOIN',
		'WHERE',
		'ORDER BY',
		'GROUP BY',
		'LIMIT',
	);

	public function formatPlain($sql) {
		$sql = $this->addNewlines($sql, false);

		return $sql;
	}

	public function formatHTML($sql) {
		$sql = $this->addNewlines($sql, true);

		return $sql;
	}

	/**
	 * Newlines for tokens defined in $newline_before_tokens.
	 * Case-sensitive, only applies to uppercase SQL to avoid
	 * messing with possible content fragments in the query.
	 */
	protected function addNewlines($sql, $useHtmlFormatting = false) {
		$eol = PHP_EOL;
		foreach(self::$newline_before_tokens as $token) {
			$breakToken = ($useHtmlFormatting) ? "<br />$eol" : $eol;
			$sql = preg_replace('/[^\n](' . $token . ')/', $breakToken . '$1', $sql);
		}

		return $sql;
	}

}
