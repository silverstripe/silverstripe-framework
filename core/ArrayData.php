<?php
/**
 * Lets you wrap a bunch of array data into a {@link ViewableData} object.
 *
 * <code>
 * new ArrayData(array(
 *    "ClassName" => "Page",
 *    "AddAction" => "Add a new Page page",
 * ));
 * </code>
 *
 * @package sapphire
 * @subpackage view
 */
class ArrayData extends ViewableData {

	protected $array;
	
	/**
	 * @param object|array $array Either an object with simple properties or an associative array.
	 * Converts object-properties to indices of an associative array.
	 */
	public function __construct($array) {
		if(is_object($array)) {
			$this->array = self::object_to_array($array);
		} elseif(is_array($array) && (ArrayLib::is_associative($array) || count($array) === 0)) {
			$this->array = $array;
		} else {
			$this->array = $array;
			user_error(
				"ArrayData::__construct: Parameter needs to be an object or associative array", 
				E_USER_WARNING
			);
		}
		parent::__construct();
	}
	
	public function getField($f) {
		if((is_object($this->array[$f]) && !$this->array[$f] instanceof ViewableData) || (is_array($this->array[$f]) && ArrayLib::is_associative($this->array[$f]))) {
			return new ArrayData($this->array[$f]);
		} else {
			return $this->array[$f];
		}
	}
	
	public function hasField($f) {
		return isset($this->array[$f]);
	}
	
	/**
	 * Converts an object with simple properties to 
	 * an associative array.
	 *
	 * @param obj $obj
	 * @return array
	 */
	protected static function object_to_array($obj) {
		$arr = array();
		foreach($obj as $k=>$v) {
			$arr[$k] = $v;
		}
		
		return $arr;
	}
	
	/**
	 * This is pretty crude, but it helps diagnose error situations
	 */
	function forTemplate() {
		return var_export($this->array, true);
	}
	
}

?>