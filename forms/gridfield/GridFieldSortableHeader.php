<?php
/**
 * GridFieldSortableHeader adds column headers to a gridfield that can also sort the columns
 * 
 * @see GridField
 * 
 * @package sapphire
 * @subpackage fields-relational
 */
class GridFieldSortableHeader implements GridField_HTMLProvider, GridField_DataManipulator, GridField_ActionProvider {
	
	/**
	 * Returns the header row providing titles with sort buttons 
	 */
	public function getHTMLFragments($gridField) {
		$forTemplate = new ArrayData(array());
		$forTemplate->Fields = new ArrayList;

		$state = $gridField->State->GridFieldSortableHeader;
		$columns = $gridField->getColumns();

		foreach($columns as $columnField) {
			$metadata = $gridField->getColumnMetadata($columnField);
			$title = $metadata['title'];
			if($title && $gridField->getList()->canSortBy($columnField)) {
				$dir = 'asc';
				if($state->SortColumn == $columnField && $state->SortDirection == 'asc') {
					$dir = 'desc';
				}
				
				$field = new GridField_Action($gridField, 'SetOrder'.$columnField, $title, "sort$dir", array('SortColumn' => $columnField));

				$field->addExtraClass('ss-gridfield-sort');
				if($state->SortColumn == $columnField){
					$field->addExtraClass('ss-gridfield-sorted');
				}
			} else {
				$field = new LiteralField($columnField, $title);
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
		return array('sortasc', 'sortdesc');
	}
	
	function handleAction(GridField $gridField, $actionName, $arguments, $data) {
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
		$state = $gridField->State->GridFieldSortableHeader;
		if ($state->SortColumn == "") {
			return $dataList;
		}
		return $dataList->sort($state->SortColumn, $state->SortDirection);
	}
}