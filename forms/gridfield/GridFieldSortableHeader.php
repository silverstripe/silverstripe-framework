<?php
/**
 * GridFieldSortableHeader adds column headers to a gridfield that can also sort the columns
 * 
 * @see GridField
 * 
 * @package framework
 * @subpackage fields-relational
 */
class GridFieldSortableHeader implements GridField_HTMLProvider, GridField_DataManipulator, GridField_ActionProvider {

	/**
	 * See {@link setThrowExceptionOnBadDataType()}
	 */
	protected $throwExceptionOnBadDataType = true;

	/** @var array */
	public $fieldSorting = array();
	
	/**
	 * Determine what happens when this component is used with a list that isn't {@link SS_Filterable}.
	 * 
	 *  - true:  An exception is thrown
	 *  - false: This component will be ignored - it won't make any changes to the GridField.
	 * 
	 * By default, this is set to true so that it's clearer what's happening, but the predefined
	 * {@link GridFieldConfig} subclasses set this to false for flexibility.
	 */
	public function setThrowExceptionOnBadDataType($throwExceptionOnBadDataType) {
		$this->throwExceptionOnBadDataType = $throwExceptionOnBadDataType; 
	}

	/**
	 * See {@link setThrowExceptionOnBadDataType()}
	 */
	public function getThrowExceptionOnBadDataType() {
		return $this->throwExceptionOnBadDataType;
	}
	
	/**
	 * Check that this dataList is of the right data type.
	 * Returns false if it's a bad data type, and if appropriate, throws an exception.
	 */
	protected function checkDataType($dataList) {
		if($dataList instanceof SS_Sortable) {
			return true;
		} else {
			if($this->throwExceptionOnBadDataType) {
				throw new LogicException(get_class($this) . " expects an SS_Sortable list to be passed to the GridField.");
			}
			return false;
		}
	}

	/**
	 * Specify sortings with fieldname as the key, and actual fieldname to sort as value.
	 * Example: array("MyCustomTitle"=>"Title", "MyCustomBooleanField" => "ActualBooleanField")
	 *
	 * @param array $casting
	 */
	public function setFieldSorting($sorting) {
		$this->fieldSorting = $sorting;
		return $this;
	}
	
	/**
	 * @return array
	 */
	public function getFieldSorting() {
		return $this->fieldSorting;
	}
	
	/**
	 * Returns the header row providing titles with sort buttons 
	 */
	public function getHTMLFragments($gridField) {
		if(!$this->checkDataType($gridField->getList())) return;

		$forTemplate = new ArrayData(array());
		$forTemplate->Fields = new ArrayList;

		$state = $gridField->State->GridFieldSortableHeader;
		$columns = $gridField->getColumns();
		$currentColumn = 0;
		foreach($columns as $columnField) {
			$currentColumn++;
			$metadata = $gridField->getColumnMetadata($columnField);
			$title = $metadata['title'];
			if(isset($this->fieldSorting[$columnField]) && $this->fieldSorting[$columnField]) $columnField = $this->fieldSorting[$columnField];
			if($title && $gridField->getList()->canSortBy($columnField)) {
				$dir = 'asc';
				if($state->SortColumn == $columnField && $state->SortDirection == 'asc') {
					$dir = 'desc';
				}
				
				$field = Object::create(
					'GridField_FormAction', $gridField, 'SetOrder'.$columnField, $title, 
					"sort$dir", array('SortColumn' => $columnField)
				)->addExtraClass('ss-gridfield-sort');

				if($state->SortColumn == $columnField){
					$field->addExtraClass('ss-gridfield-sorted');

					if($state->SortDirection == 'asc')
						$field->addExtraClass('ss-gridfield-sorted-asc');
					else
						$field->addExtraClass('ss-gridfield-sorted-desc');
				}
			} else {
				if($currentColumn == count($columns) && $gridField->getConfig()->getComponentByType('GridFieldFilterHeader')){
					$field = new LiteralField($columnField, '<button name="showFilter" class="ss-gridfield-button-filter trigger"></button>');				
				}else{
					$field = new LiteralField($columnField, '<span class="non-sortable">' . $title . '</span>');
				}
			}
			$forTemplate->Fields->push($field);
		}
	
		return array(
			'header' => $forTemplate->renderWith('GridFieldSortableHeader_Row'),
		);
	}

	/**
	 *
	 * @param GridField $gridField
	 * @return array
	 */
	public function getActions($gridField) {
		if(!$this->checkDataType($gridField->getList())) return;

		return array('sortasc', 'sortdesc');
	}
	
	function handleAction(GridField $gridField, $actionName, $arguments, $data) {
		if(!$this->checkDataType($gridField->getList())) return;

		$state = $gridField->State->GridFieldSortableHeader;
		switch($actionName) {
			case 'sortasc':
				$state->SortColumn = $arguments['SortColumn'];
				$state->SortDirection = 'asc';
				break;

			case 'sortdesc':
				$state->SortColumn = $arguments['SortColumn'];
				$state->SortDirection = 'desc';
				break;
		}
	}
	
	public function getManipulatedData(GridField $gridField, SS_List $dataList) {
		if(!$this->checkDataType($dataList)) return $dataList;

		$state = $gridField->State->GridFieldSortableHeader;
		if ($state->SortColumn == "") {
			return $dataList;
		}
		return $dataList->sort($state->SortColumn, $state->SortDirection);
	}
}
