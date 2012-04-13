<?php
/**
 * This class is a snapshot of the current status of a gridfield. 
 * 
 * It's main use is to be inserted into a Form as a HiddenField
 * 
 * @see GridField
 * 
 * @package framework
 * @subpackage fields-relational
 */
class GridState extends HiddenField {

	/** @var GridField */
	protected $grid;

	protected $gridStateData = null;
	
	/**
	 *
	 * @param type $d
	 * @return type 
	 */
	public static function array_to_object($d) {
		if(is_array($d)) {
			return (object) array_map(array('GridState', 'array_to_object'), $d);
		} else {
			return $d;
		}
	}

	/**
	 *
	 * @param GridField $name
	 * @param string $data - json encoded string
	 */
	public function __construct($grid, $value = null) {
		$this->grid = $grid;

		if ($value) $this->setValue($value);

		parent::__construct($grid->getName() . '[GridState]');
	}
	
	/**
	 *
	 * @param type $value 
	 */
	public function setValue($value) {
		if (is_string($value)) {
			$this->gridStateData = new GridState_Data(json_decode($value, true));
		}
		parent::setValue($value);
	}
	
	public function getData() {
		if(!$this->gridStateData) $this->gridStateData = new GridState_Data;
		return $this->gridStateData;
	}

	/**
	 *
	 * @return type 
	 */
	public function getList() {
		return $this->grid->getList();
	}

	/** @return string */
	public function Value() {
		if(!$this->gridStateData) {
			return json_encode(array());
		}
		return json_encode($this->gridStateData->toArray());
	}

	/**
	 *
	 * @return type 
	 */
	public function dataValue() {
		return $this->Value();
	}

	/**
	 *
	 * @return type 
	 */
	public function attrValue() {
		return Convert::raw2att($this->Value());
	}

	/**
	 *
	 * @return type 
	 */
	public function __toString() {
		return $this->Value();
	}
}

/**
 * Simple set of data, similar to stdClass, but without the notice-level errors 
 */
class GridState_Data {
	protected $data;
	
	function __construct($data = array()) {
		$this->data = $data;
	}
	
	function __get($name) {
		if(!isset($this->data[$name])) $this->data[$name] = new GridState_Data;
		if(is_array($this->data[$name])) $this->data[$name] = new GridState_Data($this->data[$name]);
		return $this->data[$name];
	}
	function __set($name, $value) {
		$this->data[$name] = $value;
	}
	function __isset($name) {
		return isset($this->data[$name]);
	}

	function __toString() {
		if(!$this->data) return "";
		else return json_encode($this->toArray());
	}

	function toArray() {
		$output = array();
		foreach($this->data as $k => $v) {
			$output[$k] = (is_object($v) && method_exists($v, 'toArray')) ? $v->toArray() : $v;
		}
		return $output;
	}
}


class GridState_Component implements GridField_HTMLProvider {
	
	public function getHTMLFragments($gridField) {
		
		$forTemplate = new ArrayData(array());
		$forTemplate->Fields = new ArrayList;
		
		return array(
			'before' => $gridField->getState(false)->Field()
		);
	}

}
