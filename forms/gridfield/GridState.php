<?php
/**
 * This class is a snapshot of the current status of a {@link GridField}. 
 *
 * It's designed to be inserted into a Form as a HiddenField and passed through 
 * to actions such as the {@link GridField_FormAction}.
 * 
 * @see GridField
 * 
 * @package forms
 * @subpackage fields-gridfield
 */
class GridState extends HiddenField {

	/** 
	 * @var GridField 
	 */
	protected $grid;

	/**
	 * @var GridState_Data
	 */
	protected $data = null;
	
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
	 * @param mixed $d
	 * @return object 
	 */
	public static function array_to_object($d) {
		if(is_array($d)) {
			return (object) array_map(array('GridState', 'array_to_object'), $d);
		}
		
		return $d;
	}

	/**
	 * @param mixed $value 
	 */
	public function setValue($value) {
		if (is_string($value)) {
			$this->data = new GridState_Data(json_decode($value, true));
		}

		parent::setValue($value);
	}
	
	/**
	 * @var GridState_Data
	 */
	public function getData() {
		if(!$this->data) {
			$this->data = new GridState_Data();
		}

		return $this->data;
	}

	/**
	 * @return DataList 
	 */
	public function getList() {
		return $this->grid->getList();
	}

	/**
	 * Returns a json encoded string representation of this state.
	 *
	 * @return string 
	 */
	public function Value() {
		if(!$this->data) {
			return json_encode(array());
		}

		return json_encode($this->data->toArray());
	}

	/**
	 * Returns a json encoded string representation of this state.
	 *
	 * @return string 
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
 * Simple set of data, similar to stdClass, but without the notice-level 
 * errors.
 *
 * @see GridState
 * 
 * @package forms
 * @subpackage fields-gridfield
 */
class GridState_Data {

	/**
	 * @var array
	 */
	protected $data;
	
	public function __construct($data = array()) {
		$this->data = $data;
	}
	
	public function __get($name) {
		if(!isset($this->data[$name])) {
			$this->data[$name] = new GridState_Data();
		} else if(is_array($this->data[$name])) {
			$this->data[$name] = new GridState_Data($this->data[$name]);
		}

		return $this->data[$name];
	}

	public function __set($name, $value) {
		$this->data[$name] = $value;
	}
	
	public function __isset($name) {
		return isset($this->data[$name]);
	}

	public function __toString() {
		if(!$this->data) {
			return "";
		}
		
		return json_encode($this->toArray());
	}

	public function toArray() {
		$output = array();
		
		foreach($this->data as $k => $v) {
			$output[$k] = (is_object($v) && method_exists($v, 'toArray')) ? $v->toArray() : $v;
		}

		return $output;
	}
}

/**
 * @see GridState
 * 
 * @package forms
 * @subpackage fields-gridfield
 */
class GridState_Component implements GridField_HTMLProvider {
	
	public function getHTMLFragments($gridField) {
		return array(
			'before' => $gridField->getState(false)->Field()
		);
	}
}
