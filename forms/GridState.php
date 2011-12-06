<?php
/**
 * This class is a snapshot of the current status of a gridfield. It is behaving like a open 
 * container that can be parsed as json and recieve json.
 * 
 * It's main use is to be inserted into HTML as serialized json
 * 
 * @package forms
 */
class GridState extends HiddenField {

	/**
	 *
	 * @var array
	 */
	protected $box = array();
	
	/**
	 *
	 * @param string $data - json encoded string
	 * @param string $name
	 * @param string $title 
	 */
	public function __construct($data=null, $name=null, $title=null) {
		if(is_string($data)){
			foreach(json_decode($data) as $name => $value) {
				$this->box[$name] = $value;
			}
		}
		parent::__construct($name, $title);
	}
	
	/**
	 *
	 * @param string $name
	 * @param mixed $value 
	 */
	public function __set($name, $value) {
		$this->box[$name] = $value;
	}
	
	/**
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name) {
		return $this->box[$name];
	}
	
	/**
	 *
	 * @param boolean $name 
	 * @return void
	 */
	public function __unset($name) {
		unset($this->box[$name]);
	}
	
	/**
	 *
	 * @param string $name
	 * @return boolean 
	 */
	public function __isset($name) {
		return array_key_exists($name, $this->box);
	}
	
	/**
	 *
	 * @return string
	 */
	public function __toString() {
		return json_encode($this->box);
	}
	
	public function attrValue() {
		return Convert::raw2att($this->__toString());
	}
}