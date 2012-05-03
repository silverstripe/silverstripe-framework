<?php
/**
 * Lets you wrap a bunch of array data, or object members, into a {@link ViewableData} object.
 *
 * <code>
 * new ArrayData(array(
 *    "ClassName" => "Page",
 *    "AddAction" => "Add a new Page page",
 * ));
 * </code>
 *
 * @package framework
 * @subpackage view
 */
class ArrayData extends ViewableData {

	/**
	 * @var array 
	 * @see ArrayData::_construct()
	 */
	protected $array;
	
	/**
	 * @param object|array $value An associative array, or an object with simple properties.
	 * Converts object properties to keys of an associative array.
	 */
	public function __construct($value) {
		if (is_object($value)) {
			$this->array = get_object_vars($value);
		} elseif (ArrayLib::is_associative($value)) {
			$this->array = $value;
		} elseif (is_array($value) && count($value) === 0) {
			$this->array = array();
		} else {
			$message = 'Parameter to ArrayData constructor needs to be an object or associative array';
			throw new InvalidArgumentException($message);
		}
		parent::__construct();
	}
	
	/**
	 * Get the source array
	 *
	 * @return array
	 */
	public function toMap() {
		return $this->array;
	}
	
	/**
	 * Gets a field from this object.
	 *
	 * @param string $field
	 *
	 * If the value is an object but not an instance of
	 * ViewableData, it will be converted recursively to an
	 * ArrayData.
	 *
	 * If the value is an associative array, it will likewise be
	 * converted recursively to an ArrayData.
	 */
	public function getField($f) {
		$value = $this->array[$f];
		if (is_object($value) && !$value instanceof ViewableData) {
			return new ArrayData($value);
		} elseif (ArrayLib::is_associative($value)) {
			return new ArrayData($value);
	    } else {
			return $value;
		}
	}
	/**
	* Add or set a field on this object.
	*
	* @param string $field
	* @param mixed $value
	*/
	public function setField($field, $value) {
		$this->array[$field] = $value;
	}
	
	/**
	 * Check array to see if field isset
	 *
	 * @param string Field Key
	 * @return bool
	 */
	public function hasField($f) {
		return isset($this->array[$f]);
	}
	
	/**
	 * Converts an associative array to a simple object
	 *
	 * @param array
	 * @return obj $obj
	 */
	public static function array_to_object($arr = null) {
		$obj = new stdClass();
		if ($arr) foreach($arr as $name => $value) $obj->$name = $value;
		return $obj;
	}
	
	/**
	 * @deprecated 3.0 Use {@link ArrayData::toMap()}.
	 */
	public function getArray() {
		Deprecation::notice('3.0', 'Use ArrayData::toMap() instead.');
		return $this->toMap();
	}

}
