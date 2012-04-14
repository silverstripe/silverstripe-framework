<?php
/**
 * Lets you include a nested group of fields inside a template.
 * This control gives you more flexibility over form layout.
 * 
 * Note: the child fields within a field group aren't rendered using FieldHolder().  Instead,
 * SmallFieldHolder() is called, which just prefixes $Field with a <label> tag, if the Title is set.
 * 
 * <b>Usage</b>
 * 
 * <code>
 * new FieldGroup(
 * 	new FieldGroup(
 * 		new HeaderField('FieldGroup 1'),
 * 		new TextField('Firstname')
 * 	),
 * 	new FieldGroup(
 * 		new HeaderField('FieldGroup 2'),
 * 		new TextField('Surname')
 * 	)
 * )
 * </code>
 * 
 * <b>Adding to existing FieldGroup instances</b>
 * 
 * <code>
 * function getCMSFields() {
 * 	$fields = parent::getCMSFields();
 * 	
 * 	$fields->addFieldToTab(
 * 		'Root.Content', 
 * 		new FieldGroup(
 * 			new TimeField("StartTime","What's the start time?"),
 * 			new TimeField("EndTime","What's the end time?")
 * 		),
 * 		'Content'
 * 	);	
 * 	
 * 	return $fields;
 * 		
 * }
 * </code>
 * 
 * @package forms
 * @subpackage fields-structural
 */
class FieldGroup extends CompositeField {
	
	protected $zebra;
	
	function __construct($arg1 = null, $arg2 = null) {
		if(is_array($arg1) || is_a($arg1, 'FieldSet')) {
			$fields = $arg1;
		
		} else if(is_array($arg2) || is_a($arg2, 'FieldList')) {
			$this->title = $arg1;
			$fields = $arg2;
		
		} else {
			$fields = func_get_args();
			if(!is_object(reset($fields))) $this->title = array_shift($fields);
		}
			
		parent::__construct($fields);
	}
	
	/**
	 * Returns the name (ID) for the element.
	 * In some cases the FieldGroup doesn't have a title, but we still want 
	 * the ID / name to be set. This code, generates the ID from the nested children
	 */
	function Name(){
		if(!$this->title) {
			$fs = $this->FieldList();
			$compositeTitle = '';
			$count = 0;
			foreach($fs as $subfield){
				$compositeTitle .= $subfield->getName();
				if($subfield->getName()) $count++;
			}
			if($count == 1) $compositeTitle .= 'Group';
			return preg_replace("/[^a-zA-Z0-9]+/", "", $compositeTitle);
		}

		return preg_replace("/[^a-zA-Z0-9]+/", "", $this->title);
	}

	/**
	 * Set an odd/even class
	 *
	 * @param string $zebra one of odd or even.
	 */
  	function setZebra($zebra) {
	    if($zebra == 'odd' || $zebra == 'even') $this->zebra = $zebra;
	    else user_error("setZebra passed '$zebra'.  It should be passed 'odd' or 'even'", E_USER_WARNING);
	    return $this;
 	}
  
	/**
	 * @return string
	 */
	function getZebra() {
		return $this->zebra;
	}
	
	/**
	 * @return string
	 */
	function Message() {
		$fs = $this->FieldList();
		
		foreach($fs as $subfield) {
			if($m = $subfield->Message()) $message[] = $m;
		}
		
		return (isset($message)) ? implode(",  ", $message) . ". " : "";
	}	
	
	/**
	 * @return string
	 */
	function MessageType() {
		$fs = $this->FieldList();
		
		foreach($fs as $subfield) {
			if($m = $subfield->MessageType()) $MessageType[] = $m;
		}
		
		return (isset($MessageType)) ? implode(".  ", $MessageType) : "";
	}
	
	function php($data) {
		return;
	}	
}