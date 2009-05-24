<?php
/**
 * @package sapphire
 * @subpackage search
 */

/**
 * Filters by full-text matching on the given field.
 *
 * Full-text indexes are only available with MyISAM tables. The following column types are
 * supported:
 *   - Char
 *   - Varchar
 *   - Text
 *
 * To enable full-text matching on fields, you also need to add an index to the
 * database table, using the {$indexes} hash in your DataObject subclass:
 * 
 * <code>
 *   static $indexes = array(
 *      'SearchFields' => 'fulltext(Name, Title, Description)'
 *   );
 * </code>
 *
 * @package sapphire
 * @subpackage search
 */
class FulltextFilter extends SearchFilter {

	public function apply(SQLQuery $query) {
		$query->where(sprintf(
			"MATCH (%s) AGAINST ('%s')",
			$this->getDbName(),
			Convert::raw2sql($this->getValue())
		));
		return $query;
	}

	public function isEmpty() {
		return $this->getValue() == null || $this->getValue() == '';
	}
}
?>