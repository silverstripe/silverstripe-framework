<?php
/**
 * ComplexTableField with a radio button column, designed to edit a has_one join.
 * 
 * This [RelationTable](RelationTable) allows you to show a **1-to-1** or **1-to-many** relation with a group of DataObjects as a (readonly) tabular list (similiar to [ComplexTableField](ComplexTableField)). Its most useful when you want to manage the relationship itself thanks the **radio buttons** present on each line of the table.
 * 
 * Moreover, you have the possibility to uncheck a radio button in order to make the relation as null.
 * 
 * <b>Usage</b>
 * 
 * <code>
 * $tablefield = new HasOneComplexTableField(
 *     $this,
 *     'MyOnlyFruit',
 *     'Fruit',
 *     array(
 * 	'Name' => 'Name',
 * 	'Color' => 'Color'
 *     ),
 *     'getCMSFields_forPopup'
 * );
 * </code>
 * 
 * **Notice** : You still have different ways to customize the popup window as in the parent-class [ComplexTableField](ComplexTableField).
 * 
 * This field is made to manage a **has_one** relation. In the SilverStripe relation between DataObjects, you can use this relation for **1-to-1** and **1-to-many** relations.
 * By default, a HasOneComplexTableField manages a **1-to-many** relation. If you want to specify that the relation that you manage is a **1-to-1** relation, add this code :
 * 
 * <code>
 * $tablefield->setOneToOne();
 * </code>
 * 
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
	
	function saveInto(DataObjectInterface $record) {
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
		return DataObject::get($this->controllerClass(), '"' . $this->joinField . "\" = '$childID'");
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
		$name = $this->parent->getName() . '[]';
		
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

