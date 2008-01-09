<?php

/**
 * @package forms
 * @subpackage fields-basic
 */

/**
 * Displays a set of checkboxes as a logical group.
 *
 * ASSUMPTION -> IF you pass your source as an array, you pass values as an array too.
 * 				Likewise objects are handled the same.
 * @package forms
 * @subpackage fields-basic
 */
class CheckboxSetField extends OptionsetField {
	
		
	protected $disabled = false;
	
	function __construct($name, $title = "", $source = array(), $value = "", $form = null) {
		parent::__construct($name, $title, $source, $value, $form);
		
		Requirements::css('sapphire/css/CheckboxSetField.css');
	}
  
  	/**
  	* Object handles arrays and dosets being passed by reference
  	*/
	function Field() {
		$values = $this->value;
		
		// Get values from the join, if available
		if(is_object($this->form)) {
			$record = $this->form->getRecord();
			if(!$values && $record && $record->hasMethod($this->name)) {
				$funcName = $this->name;
				$join = $record->$funcName();
				foreach($join as $joinItem) $values[] = $joinItem->ID;
			}
		}
		$source = $this->source;
		if(!is_array($source) && !is_a($source, 'SQLMap')){
			// Source and values are DataObject sets.
			if(is_array($values)) {
				$items = $values;
			} else {
				if($values&&is_a($values, "DataObjectSet")){
				   foreach($values as $object){	
					   	if( is_a( $object, 'DataObject' ) )
							$items[] = $object->ID;
				   }
				}elseif($values&&is_string($values)){
					$items = explode(',', $values);
					$items = str_replace('{comma}', ',', $items);
				}
			}
			
		} else {
			
			// Sometimes we pass a singluar default value
			// thats ! an array && !DataObjectSet
			if(is_a($values,'DataObjectSet') || is_array($values))
				$items = $values;
			else{
				$items = explode(',',$values);
				$items = str_replace('{comma}', ',', $items);
			}
		}
			
			
		if(is_array($source)){
			// Commented out to fix "'Specific newsletters' option in 'newsletter subscription form' page type does not work" bug
			// See: http://www.silverstripe.com/bugs/flat/1675
			// unset($source[0]);
			unset($source['']);
		}
		
		$odd = 0;
		$options = '';
		foreach($source as $index => $item) {
			if(is_a($item,'DataObject')) {
				$key = $item->ID;
				$value = $item->Title;
			} else {
				$key = $index;
				$value = $item;
			}
			
			$odd = ($odd + 1) % 2;
			$extraClass = $odd ? "odd" : "even";
			$extraClass .= " val" . str_replace(' ','',$key);
					
			$itemID = $this->id() . "_" . ereg_replace('[^a-zA-Z0-9]+','',$key);
			
			$checked ="";
			if(isset($items)){
				in_array($key,$items) ? $checked = " checked=\"checked\"" : $checked = "";
			}
			
			$this->disabled ? $disabled = " disabled=\"disabled\"" : $disabled = "";
			
			$options .= "<li class=\"$extraClass\"><input id=\"$itemID\" name=\"$this->name[]\" type=\"checkbox\" value=\"$key\"$checked $disabled /> <label for=\"$itemID\">$value</label></li>\n"; 
		}
		
		
		return "<ul id=\"{$this->id()}\" class=\"optionset\">\n$options</ul>\n"; 
	}
	
	function setDisabled($val) {
		$this->disabled = $val;
	}
	
	/**
	* @desc 
	*/
	function saveInto(DataObject $record) {
		$fieldname = $this->name ;
		
		if($fieldname && $record && ($record->has_many($fieldname) || $record->many_many($fieldname))) {
			$record->$fieldname()->setByIDList($this->value);
		} else if($fieldname && $record && $record->hasField($fieldname)) {
			if($this->value){
				$this->value = str_replace(",", "{comma}", $this->value);
				$record->$fieldname = implode(",", $this->value);
			} else {
				$record->$fieldname = '';
			}
		}
	}
	
	/**
	* Makes a pretty readonly field
	*/

	function performReadonlyTransformation() {
		$values = '';
		
		$items = $this->value;
		foreach($this->source as $source) {
			if(is_object($source)) {
				$sourceTitles[$source->ID] = $source->Title;
			}
		}
		
		if($items){
			// Items is a DO Set
			if(is_a($items,'DataObjectSet')){
				
				foreach($items as $item){
					$data[] = $item->Title;
				}
				if($data) {
					$values = implode(", ",$data);
				}
			
			// Items is an array or single piece of string (including comma seperated string)
			}else{
				if(!is_array($items)) {
					$items = split(" *, *", trim($items));
				}
				foreach($items as $item){
					if(is_array($item)) {
						$data[] = $item['Title'];
					} else if(is_array($this->source)&&$this->source[$item]) {
						$data[] = $this->source[$item];
					} else if(is_a($this->source, "ComponentSet")){
						//added for editable checkboxset. 
						$data[] = $sourceTitles[$item];
						
					} else {
						$data[] = $item;
					}
				}
				$values = implode(", ",$data);
			}
		}
		
		$field = new ReadonlyField($this->name,$this->title ? $this->title : "",$values);
		$field->setForm($this->form);
		return $field;
	}
	
	function ExtraOptions() {
		return FormField::ExtraOptions();
	}	
}
?>
