<?php
/**
 * This class is an GridField Component that add Delete action for Objects in the GridField
 * 
 */
class GridFieldAction_Delete implements GridField_ColumnProvider, GridField_ActionProvider {
	
	/**
	 * Add a column 'Delete'
	 * 
	 * @param type $gridField
	 * @param array $columns 
	 */
	public function augmentColumns($gridField, &$columns) {
		$columns[] = 'DeleteAction';
	}
	
	/**
	 * Return any special attributes that will be used for FormField::createTag()
	 *
	 * @param GridField $gridField
	 * @param DataObject $record
	 * @param string $columnName
	 * @return array
	 */
	public function getColumnAttributes($gridField, $record, $columnName) {
		return array();
	}
	
	/**
	 * Add the title 
	 * 
	 * @param GridField $gridField
	 * @param string $columnName
	 * @return array
	 */
	public function getColumnMetadata($gridField, $columnName) {
		if($columnName == 'DeleteAction') {
			return array('title' => '');
		}
	}
	
	/**
	 * Which columns are handled by this component
	 * 
	 * @param type $gridField
	 * @return type 
	 */
	public function getColumnsHandled($gridField) {
		return array('DeleteAction');
	}
	
	/**
	 * Which GridField actions are this component handling
	 *
	 * @param GridField $gridField
	 * @return array 
	 */
	public function getActions($gridField) {
		return array('deleterecord');
	}
	
	/**
	 *
	 * @param GridField $gridField
	 * @param DataObject $record
	 * @param string $columnName
	 * @return string - the HTML for the column 
	 */
	public function getColumnContent($gridField, $record, $columnName) {
		$field = new GridField_Action(
			$gridField, 
			'DeleteRecord'.$record->ID, 
			_t('GridAction.Delete', "delete"), 
			"deleterecord", 
			array('RecordID' => $record->ID)
		);
		$output = $field->Field();
		return $output;
	}
	
	/**
	 * Handle the actions and apply any changes to the GridField
	 *
	 * @param GridField $gridField
	 * @param string $actionName
	 * @param mixed $arguments
	 * @param array $data - form data
	 * @return void
	 */
	public function handleAction(GridField $gridField, $actionName, $arguments, $data) {
		$id = $arguments['RecordID'];
		$item = $gridField->getList()->byID($id);
		if(!$item) return;

		if($actionName == 'deleterecord') {
			$item->delete();
		}
	}
}