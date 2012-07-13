<?php
/**
 * ComplexTableField designed to edit a has_many join.
 * 
 * This field allows you to show a 1-to-many relation with a group of DataObjects as a (readonly) tabular list. Its most useful when you want to manage the relationship itself thanks the **check boxes** present on each line of the table.
 * 
 * Moreover, you can not do any mistake anymore in the relation by checking a DataObject already linked with another of the parent class.
 * 
 * See {@link ComplexTableField} for more documentation on the base-class.
 * 
 * <b>Usage</b>
 * 
 * <code>
 * $tablefield = new HasManyComplexTableField(
 *     $this,
 *     'MyFruits',
 *     'Fruit',
 *     array(
 * 	'Name' => 'Name',
 * 	'Color' => 'Color'
 *     ),
 *     'getCMSFields_forPopup'
 * );
 * </code>
 * 
 * Notice: You still have different ways to customize the popup window as in the parent-class {@link ComplexTableField}.
 * 
 * @see http://doc.silverstripe.org/tutorial/5-dataobject-relationship-management
 * 
 * @deprecated 3.0 Use GridField with GridFieldConfig_RelationEditor
 * 
 * @package forms
 * @subpackage fields-relational
 */
class HasManyComplexTableField extends ComplexTableField {
		
	public $joinField;
	
	protected $addTitle;
	
	protected $htmlListEndName = 'CheckedList'; // If you change the value, do not forget to change it also in the JS file
	
	protected $htmlListField = 'selected'; // If you change the value, do not forget to change it also in the JS file
	
	public $template = 'RelationComplexTableField';
	
	public $itemClass = 'HasManyComplexTableField_Item';
	
	protected $relationAutoSetting = false;
	
	function __construct($controller, $name, $sourceClass, $fieldList = null, $detailFormFields = null, $sourceFilter = "", $sourceSort = "", $sourceJoin = "") {
		parent::__construct($controller, $name, $sourceClass, $fieldList, $detailFormFields, $sourceFilter, $sourceSort, $sourceJoin);

		Deprecation::notice('3.0', 'Use GridField with GridFieldConfig_RelationEditor', Deprecation::SCOPE_CLASS);
		
		$this->Markable = true;

		if($controllerClass = $this->controllerClass()) {
			$this->joinField = $this->getParentIdName($controllerClass, $this->sourceClass);
			if(!$this->joinField) user_error("Can't find a has_one relationship from '$this->sourceClass' to '$controllerClass'", E_USER_WARNING);
		} else {
			user_error("Can't figure out the data class of $controller", E_USER_WARNING);
		}
		
	}
	
	function FieldHolder($properties = array()) {
		$ret = parent::FieldHolder($properties);
		
		Requirements::add_i18n_javascript(FRAMEWORK_DIR . '/javascript/lang');
		Requirements::javascript(FRAMEWORK_DIR . "/javascript/HasManyFileField.js");
		Requirements::javascript(FRAMEWORK_DIR . '/javascript/RelationComplexTableField.js');
		Requirements::css(FRAMEWORK_DIR . '/css/HasManyFileField.css');
		
		return $ret;
	}
	
	/**
	 * Try to determine the DataObject that this field is built on top of
	 */
	function controllerClass() {
		if($this->controller instanceof DataObject) return $this->controller->class;
		elseif($this->controller instanceof Controller) return $this->controller->data()->class;
	}
		
	function getControllerID() {
		return $this->controller->ID;
	}
	
	function saveInto(DataObjectInterface $record) {
		$fieldName = $this->name;
		$saveDest = $record->$fieldName();
		
		if(! $saveDest)
			user_error("HasManyComplexTableField::saveInto() Field '$fieldName' not found on $record->class.$record->ID", E_USER_ERROR);
		
		$items = array();
		
		if($list = $this->value[ $this->htmlListField ]) {
			if($list != 'undefined')
				$items = explode(',', $list);
		}

		$saveDest->setByIDList($items);
	}
	
	function setAddTitle($addTitle) {
		if(is_string($addTitle))
			$this->addTitle = $addTitle;
	}
	
	function Title() {
		return $this->addTitle ? $this->addTitle : parent::Title();
	}
	
	/**
	 * Get the IDs of the selected items, in a has_many or many_many relation
	 */
	function selectedItemIDs() {
		$fieldName = $this->name;
		$selectedItems = $this->form->getRecord()->$fieldName();
		$itemIDs = array();
		foreach($selectedItems as $item) $itemIDs[] = $item->ID;
		return $itemIDs;
	}
	
	function ExtraData() {
		$items = array();
		
		$list = implode(',', $this->selectedItemIDs());
		$inputId = $this->id() . '_' . $this->htmlListEndName;
		return <<<HTML
		<input id="$inputId" name="{$this->name}[{$this->htmlListField}]" type="hidden" value="$list"/>
HTML;
	}
}

/**
 * Single record of a {@link HasManyComplexTableField} field.
 * @package forms
 * @subpackage fields-relational
 */
class HasManyComplexTableField_Item extends ComplexTableField_Item {
	
	function MarkingCheckbox() {
		$name = $this->parent->getName() . '[]';
		
		if(!$this->parent->joinField) {
			user_error("joinField not set in HasManyComplexTableField '{$this->parent->name}'", E_USER_WARNING);
			return null;
		}
		
		$joinVal = $this->item->{$this->parent->joinField};
		$parentID = $this->parent->getControllerID();
		
		if($this->parent->IsReadOnly || ($joinVal > 0 && $joinVal != $parentID))
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" disabled=\"disabled\"/>";
		else if($joinVal == $parentID)
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\" checked=\"checked\"/>";
		else
			return "<input class=\"checkbox\" type=\"checkbox\" name=\"$name\" value=\"{$this->item->ID}\"/>";
	}
}


