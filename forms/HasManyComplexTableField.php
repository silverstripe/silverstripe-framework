<?php

/**
 * @package forms
 * @subpackage fields-relational
 */

/**
 * ComplexTableField designed to edit a has_many join.
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
	
	function __construct($controller, $name, $sourceClass, $fieldList, $detailFormFields = null, $sourceFilter = "", $sourceSort = "", $sourceJoin = "") {

		parent::__construct($controller, $name, $sourceClass, $fieldList, $detailFormFields, $sourceFilter, $sourceSort, $sourceJoin);

		$this->Markable = true;
		
		$this->joinField = $this->getParentIdName($this->controller->ClassName, $this->sourceClass);
	}
	
	function getQuery($limitClause = null) {
		if($this->customQuery) {
			$query = $this->customQuery;
			$query->select[] = "{$this->sourceClass}.ID AS ID";
			$query->select[] = "{$this->sourceClass}.ClassName AS ClassName";
			$query->select[] = "{$this->sourceClass}.ClassName AS RecordClassName";
		}
		else {
			$query = singleton($this->sourceClass)->extendedSQL($this->sourceFilter, $this->sourceSort, $limitClause, $this->sourceJoin);
			
			// Add more selected fields if they are from joined table.

			$SNG = singleton($this->sourceClass);
			foreach($this->FieldList() as $k => $title) {
				if(! $SNG->hasField($k) && ! $SNG->hasMethod('get' . $k))
					$query->select[] = $k;
			}
		}
		return clone $query;
	}
	
	function sourceItems() {
		if($this->sourceItems)
			return $this->sourceItems;
		
		$limitClause = '';
		if(isset($_REQUEST[ 'ctf' ][ $this->Name() ][ 'start' ]) && is_numeric($_REQUEST[ 'ctf' ][ $this->Name() ][ 'start' ]))
			$limitClause = $_REQUEST[ 'ctf' ][ $this->Name() ][ 'start' ] . ", $this->pageSize";
		else
			$limitClause = "0, $this->pageSize";
		
		$dataQuery = $this->getQuery($limitClause);
		$records = $dataQuery->execute();
		$items = new DataObjectSet();
		foreach($records as $record) {
			if(! get_class($record))
				$record = new DataObject($record);
			$items->push($record);
		}
		
		$dataQuery = $this->getQuery();
		$records = $dataQuery->execute();
		$unpagedItems = new DataObjectSet();
		foreach($records as $record) {
			if(! get_class($record))
				$record = new DataObject($record);
			$unpagedItems->push($record);
		}
		$this->unpagedSourceItems = $unpagedItems;
		
		$this->totalCount = ($this->unpagedSourceItems) ? $this->unpagedSourceItems->TotalItems() : null;
		
		return $items;
	}
		
	function getControllerID() {
		return $this->controller->ID;
	}
	
	function saveInto(DataObject $record) {
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
	
	function ExtraData() {
		$items = array();
		foreach($this->unpagedSourceItems as $item) {
			if($item->{$this->joinField} == $this->controller->ID)
				$items[] = $item->ID;
		}
		$list = implode(',', $items);
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
		$name = $this->parent->Name() . '[]';
		
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

?>