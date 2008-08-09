<?php
/**
 * @todo documentation
 *
 * @package sapphire
 * @subpackage search
 */
abstract class SearchFilter extends Object {
	
	protected $name;
	protected $value;
	
	function __construct($name, $value = false) {
		$this->name = $name;
		$this->value = $value;
	}
	
	public function setValue($value) {
		$this->value = $value;
	}
	
	/**
	 * Apply filter criteria to a SQL query.
	 *
	 * @param SQLQuery $query
	 */
	abstract public function apply(SQLQuery $query);
	
}
?>