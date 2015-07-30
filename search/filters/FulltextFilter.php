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
 *   private static $indexes = array(
 *      'SearchFields' => 'fulltext(Name, Title, Description)'
 *   );
 * </code>
 *
 * @todo Add support for databases besides MySQL
 */
class FulltextFilter extends SearchFilter {

	protected function applyOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$predicate = sprintf("MATCH (%s) AGAINST (?)", $this->getDbName());
		return $query->where(array($predicate => $this->getValue()));
	}

	protected function excludeOne(DataQuery $query) {
		$this->model = $query->applyRelation($this->relation);
		$predicate = sprintf("NOT MATCH (%s) AGAINST (?)", $this->getDbName());
		return $query->where(array($predicate => $this->getValue()));
	}

	public function isEmpty() {
		return $this->getValue() === array() || $this->getValue() === null || $this->getValue() === '';
	}


	/**
	 * This implementation allows for a list of columns to be passed into MATCH() instead of just one.
	 *
	 * @example
	 * <code>
	 * 	MyDataObject::get()->filter('SearchFields:fulltext', 'search term')
	 * </code>
	 *
	 * @return string
	*/
	public function getDbName() {
		$indexes = Config::inst()->get($this->model, "indexes");
		if(is_array($indexes) && array_key_exists($this->getName(), $indexes)) {
			$index = $indexes[$this->getName()];
			if(is_array($index) && array_key_exists("value", $index)) {
				return $index['value'];
			} else {
				// Parse a fulltext string (eg. fulltext ("ColumnA", "ColumnB")) to figure out which columns
				// we need to search.
				if(preg_match('/^fulltext\s+\((.+)\)$/i', $index, $matches)) {
					return $matches[1];
				} else {
					throw new Exception("Invalid fulltext index format for '" . $this->getName()
						. "' on '" . $this->model . "'");
				}
			}
		}

		return parent::getDbName();
	}

}
