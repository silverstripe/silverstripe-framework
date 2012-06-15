<?php
/**
 * @package framework
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
 * @package framework
 * @subpackage search
 */
class FulltextFilter extends SearchFilter {

	public function apply(DataQuery $query) {
		return $query->where(sprintf(
			"MATCH (%s) AGAINST ('%s')",
			$this->getDbName(),
			Convert::raw2sql($this->getValue())
		));
	}

	public function isEmpty() {
		return $this->getValue() == null || $this->getValue() == '';
	}
}
