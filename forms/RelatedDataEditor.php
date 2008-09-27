<?php
/**
 * RelatedDataEditor puts a "sub-form" into a form that lets you edit a record on the other side of a
 * one-to-many relationship.  For example, you could be editing a workshop, and you want to provide fields
 * to edit the client contact for that workshop.
 *
 * RelatedDataEditor inserts a dropdown field and a number of developer-specified additional fields into the
 * system
 * @package forms
 * @subpackage fields-relational
 */
class RelatedDataEditor extends FormField {
	protected $children;
	protected $dropdownField;
	protected $isNested;
	protected $showkeydropdown;

	/**
	 * @param name The name of the relationship
	 * @param dropdown The values listed in the dropdown
	 * @param fields The fields to show
	 */
	function __construct($name, $dropdownField, $fields = null, $dropdownClass = 'relatedDataKey', $showKeyDropdown = true) {
		Requirements::css(SAPPHIRE_DIR . "/css/RelatedDataEditor.css");
		parent::__construct($name);
		$this->dropdownField = $dropdownField;
		$this->children = $fields;
		$this->dropdownField->extraClass = $dropdownClass;
		$this->showkeydropdown = $showKeyDropdown;
	}
				
	function transform(FormTransformation $trans){
		$this->dropdownField->transform($trans);
		if($this->children){
			$children=array();
			foreach($this->children as $child){
				$child = $child->transform($trans);
				$children[] = $child;
			}
			$this->children = $children;
		}
		return $this;
	}
	
	function IsNested(){
		return $this->isNested;
	}
	
	function FieldHolder() {
		$fieldName = $this->name . 'ID';
		$relationName = $this->name;

		Profiler::mark("RelatedDataEditor.FieldHolder", "get data");

		$record = $this->form->getRecord();
	
		$relatedObject = $record->$relationName();

		Profiler::unmark("RelatedDataEditor.FieldHolder", "get data");

		$this->dropdownField->Name = $this->name . '[ID]';
		$this->dropdownField->Value = $record->$fieldName;
		
		$extraclass = $this->IsNested()?"nested":"";
		$result .= "<div id=\"$this->name\" class=\"$this->class groupfield $extraclass\" >";

		$fieldholder = $this->dropdownField->FieldHolder();
		
		if($this->showkeydropdown){
			$result .= "<div id=\"{$this->name}_keyholder\" class=\"keyholder\">$fieldholder</div>";
			if($this->children){
				$result .= "<img id=\"{$this->name}_loading\" src=\"cms/images/network-save.gif\" style=\"display: none;\" />";
				$result .= "<img id=\"{$this->name}_loaded\" src=\"cms/images/alert-good.gif\" style=\"display: none;\" />";
			}
		}else{
			$result .= "<div id=\"{$this->name}_keyholder\" class=\"keyholder\" style=\"display: none\">$fieldholder</div>";
		}

		if($this->children){
			$result .= "<div id= \"{$this->name}_childrenholder\" class=\"children_holder\">";
	
			foreach($this->children as $child) {
				if(!$child->isComposite()){
					$childFieldName = $child->Name();
					$child->Name = $this->name . '[' . $child->Name() . ']';
					if($this->dropdownField->isSelected()) $child->Value = $relatedObject->$childFieldName;
					$child->setForm($this->form);
					$result .= $child->FieldHolder();
				}else{
					$fs = $child->FieldSet();
					foreach($fs as $subfield){
						$childFieldName = $subfield->Name();
						$subfield->Name = $this->name . '[' . $subfield->Name() . ']';
						if($this->dropdownField->isSelected()) $subfield->Value = $relatedObject->$childFieldName;
						$subfield->setForm($this->form);
					}
					$result .= $child->FieldHolder();
				}
			}
			
			$result .= "<div class=\"clear\">&nbsp;</div>";
			$result .= "</div>";
		}
		$result .= "</div>";
		return $result;		
	}
	
	function saveInto($record) {
		$fieldName = $this->name . 'ID';
		$relationName = $this->name;
		
		//If value[newID] exists, this is a newly added related data.
		if($this->value['newID'])
			$this->value['ID']=$this->value['newID'];
		
		//if value['ID'] == 0, nothing needs to be saved
		if($this->value['ID'] == 0){
			$record->$fieldName = 0;
			return;		
		}
		
		// Set the relation ID and look up the related object from the database
		$record->$fieldName = $this->value['ID'];
		$relatedObject = $record->$relationName();

		$this->compositeSaveInto($relatedObject);
		
		$relatedID = $relatedObject->write();

		if($relatedID != $this->value['ID']){
			$record->$fieldName = $relatedID;
		}
	}
	
	/**
	 * Smart, "form-like" saveInto, to allow for nested RelatedDataEditors
	 */
	function compositeSaveInto($dataObject) {
		if($this->children) foreach($this->children as $child){
			if($child->isComposite()){
				$dataFields = $child->FieldSet()->dataFields();
				foreach($dataFields as $field) {
					$fieldVal = $this->value[$field->Name()];
					$field->setValue($fieldVal);
					if($field->Name() != "ID") $field->saveInto($dataObject);
				}
			}else{
				$fieldVal = $this->value[$child->Name()];
				$child->setValue($fieldVal);
				if($child->Name() != "ID") $child->saveInto($dataObject);
			}
		}
	}
}
?>