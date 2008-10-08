<?php
/**
 * ComplexTableField with a radio button column, designed to edit a has_one join.
 * @package forms
 * @subpackage fields-relational
 */
class HasOneComplexTableField extends HasManyComplexTableField {
	
	public $itemClass = 'HasOneComplexTableField_Item';
	
	public $isOneToOne = false;
	
	function getParentIdName($parentClass, $childClass) {
		return $this->getParentIdNameRelation($parentClass, $childClass, 'has_one');
	}
			
	function getControllerJoinID() {
		return $this->controller->{$this->joinField};
	}
	
	function saveInto(DataObject $record) {
		$fieldName = $this->name;
		$fieldNameID = $fieldName . 'ID';
		
		$record->$fieldNameID = 0;
		if($val = $this->value[ $this->htmlListField ]) {
			if($val != 'undefined')
				$record->$fieldNameID = $val;
		}
		
		$record->write();
	}
	
	function setOneToOne() {
		$this->isOneToOne = true;
	}
	
	function isChildSet($childID) {
		return DataObject::get($this->controllerClass(), '`' . $this->joinField . "` = '$childID'");
	}
	
	function ExtraData() {
		$val = $this->getControllerJoinID() ? $this->getControllerJoinID() : '';
		$inputId = $this->id() . '_' . $this->htmlListEndName;
		return <<<HTML
		<input id="$inputId" name="{$this->name}[{$this->htmlListField}]" type="hidden" value="$val"/>
HTML;
	}
}

/**
 * Single record of a {@link HasOneComplexTableField} field.
 * @package forms
 * @subpackage fields-relational
 */
class HasOneComplexTableField_Item extends ComplexTableField_Item {
	
	function MarkingCheckbox() {
		$name = $this->parent->Name() . '[]';
		
		$isOneToOne = $this->parent->isOneToOne;
		$joinVal = $this->parent->getControllerJoinID();
		$childID = $this->item->ID;
						
		if($this->parent->IsReadOnly || ($isOneToOne && $joinVal != $childID && $this->parent->isChildSet($childID)))
			return "<input class=\"radio\" type=\"radio\" name=\"$name\" value=\"{$this->item->ID}\" disabled=\"disabled\"/>";
		else if($joinVal == $childID)
			return "<input class=\"radio\" type=\"radio\" name=\"$name\" value=\"{$this->item->ID}\" checked=\"checked\"/>";
		else
			return "<input class=\"radio\" type=\"radio\" name=\"$name\" value=\"{$this->item->ID}\"/>";
	}
}

?>