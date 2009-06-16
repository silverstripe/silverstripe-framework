<?php
/**
 * @todo Add localization support, see http://open.silverstripe.com/ticket/2931
 *
 * @package forms
 * @subpackage fields-datetime
 */
class TimeField extends TextField {

	/**
	 * @var string $timeformat Time description compatible with date() syntax.
	 */
	protected $timeformat = "g:ia";
	
	/**
	 * Constructor saves the format difference. Timefields shouldn't 
	 * have a problem with length as times can only be represented in on way.
	 * 
	 * @param $name string The name of the field
	 * @param $title string The Title of the field
	 * @param $value string the value for the field
	 * @param $timeformat string The Time format in date php format e.g. G:ia
	 */
	function __construct($name, $title = null, $value = "",$timeformat = null){
		parent::__construct($name,$title,$value);
		
		if($timeformat) $this->timeformat = $timeformat;
	}
	
	function dataValue() {
		if($this->value) {
			return date($this->timeformat,strtotime($this->value));
		} else {
			return $this->value;
		}
	}
	
	function setValue($val) {
		if($val) {
			$this->value = date($this->timeformat,strtotime($val));
		} else {
			$this->value = $val;
		}
		
	}
	
	/**
	 * Creates a new readonly field specified below
	 */
	function performReadonlyTransformation() {
		return new TimeField_Readonly( $this->name, $this->title, $this->dataValue(),$this->timeformat);
	}
	
}

/**
 * The readonly class for our {@link TimeField}.
 * @package forms
 * @subpackage fields-datetime
 */
class TimeField_Readonly extends TimeField {
	
	protected $readonly = true;
	
	function Field() {
		if( $this->value )
			$val = $this->attrValue();
		else
			$val = '<i>(not set)</i>';
		
		return "<span class=\"readonly\" id=\"" . $this->id() . "\">$val</span>";
	}
}