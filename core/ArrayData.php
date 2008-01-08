<?php

/**
 * @package sapphire
 * @subpackage view
 */

/**
 * Lets you wrap a bunch of array data into a ViewableData object.
 * This is useful when you want to pass data to a template in the "SilverStripe 1" way of giving a 
 * big data array.
 *
 * Usage:
 * new ArrayData(array(
 *    "ClassName" => "Page",
 *    "AddAction" => "Add a new Page page",
 * ));
 */
class ArrayData extends ViewableData {

	protected $array;
	
	public function __construct($array) {
		$this->array = $array;
	}
	
	public function getField($f) {
		if(is_array($this->array[$f])) {
			return new ArrayData($this->array[$f]);
		} else {
			return $this->array[$f];
		}
	}
	
	public function hasField($f) {
		return isset($this->array[$f]);
	}
	
}

?>