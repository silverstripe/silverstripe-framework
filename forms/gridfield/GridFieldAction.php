<?php
/**
 * Provides the entry point to editing a single record presented by the grid.
 * Doesn't show an edit view on its own or modifies the record, but rather relies on routing conventions
 * established in {@link getColumnContent()}. The default routing applies to
 * the {@link GridFieldPopupForms} component, which has to be added separately
 * to the grid field configuration.
 */
class GridFieldAction_Edit implements GridField_ColumnProvider {
	
	/**
	 * Add a column 'Delete'
	 * 
	 * @param type $gridField
	 * @param array $columns 
	 */
	public function augmentColumns($gridField, &$columns) {
		if(!in_array('Actions', $columns))
			$columns[] = 'Actions';
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
		if($columnName == 'Actions') {
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
		return array('Actions');
	}
	
	/**
	 * Which GridField actions are this component handling
	 *
	 * @param GridField $gridField
	 * @return array 
	 */
	public function getActions($gridField) {
		return array();
	}
	
	/**
	 *
	 * @param GridField $gridField
	 * @param DataObject $record
	 * @param string $columnName
	 * @return string - the HTML for the column 
	 */
	public function getColumnContent($gridField, $record, $columnName) {
		$data = new ArrayData(array(
			'Link' => Controller::join_links($gridField->Link('item'), $record->ID, 'edit')
		));

		return $data->renderWith('GridFieldAction_Edit');
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
		
	}
}

/**
 * This class is an GridField Component that add Delete action for Objects in the GridField.
 * See {@link GridFieldRelationDelete} for detaching an item from the current relationship instead.
 */
class GridFieldAction_Delete implements GridField_ColumnProvider, GridField_ActionProvider {
	
	/**
	 * Add a column 'Delete'
	 * 
	 * @param type $gridField
	 * @param array $columns 
	 */
	public function augmentColumns($gridField, &$columns) {
		if(!in_array('Actions', $columns))
			$columns[] = 'Actions';
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
		if($columnName == 'Actions') {
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
		return array('Actions');
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
		$field = Object::create('GridField_Action',
			$gridField, 
			'DeleteRecord'.$record->ID, 
			false, 
			"deleterecord", 
			array('RecordID' => $record->ID)
		)
			->addExtraClass('gridfield-button-delete')
			->setAttribute('title', _t('GridAction.Delete', "delete"))
			->setAttribute('data-icon', 'decline');
		return $field->Field();
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
		if($actionName == 'deleterecord') {
			$id = $arguments['RecordID'];
			// Always deletes a record. Use GridFieldRelationDelete to detach it from the current relationship.
			$item = $gridField->getList()->byID($id);
			if(!$item) return;
				$item->delete();
		}
	}
}