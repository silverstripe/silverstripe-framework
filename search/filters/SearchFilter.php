<?php
/**
 * @todo documentation
 *
 * @package sapphire
 * @subpackage search
 */
abstract class SearchFilter extends Object {
	
	protected $name;
	
	function __construct($name) {
		$this->name = $name;
	}
	
	abstract public function apply($value);
	
}
?>