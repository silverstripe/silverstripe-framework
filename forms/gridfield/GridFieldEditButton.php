<?php

/**
 * Provides the entry point to editing a single record presented by the
 * {@link GridField}.
 *
 * Doesn't show an edit view on its own or modifies the record, but rather
 * relies on routing conventions established in {@link getColumnContent()}.
 *
 * The default routing applies to the {@link GridFieldDetailForm} component,
 * which has to be added separately to the {@link GridField} configuration.
 *
 * @package forms
 * @subpackage fields-gridfield
 */
class GridFieldEditButton implements GridField_ColumnProvider {

	/**
	 * Add a column 'Delete'
	 *
	 * @param GridField $gridField
	 * @param array $columns
	 */
	public function augmentColumns($gridField, &$columns) {
		if(!in_array('Actions', $columns))
			$columns[] = 'Actions';
	}

	/**
	 * Return any special attributes that will be used for FormField::create_tag()
	 *
	 * @param GridField $gridField
	 * @param DataObject $record
	 * @param string $columnName
	 * @return array
	 */
	public function getColumnAttributes($gridField, $record, $columnName) {
		return array('class' => 'col-buttons');
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
	 * @param GridField $gridField
	 * @return array
	 */
	public function getColumnsHandled($gridField) {
		return array('Actions');
	}

	/**
	 * Which GridField actions are this component handling.
	 *
	 * @param GridField $gridField
	 * @return array
	 */
	public function getActions($gridField) {
		return array();
	}

	/**
	 * @param GridField $gridField
	 * @param DataObject $record
	 * @param string $columnName
	 *
	 * @return string - the HTML for the column
	 */
	public function getColumnContent($gridField, $record, $columnName) {
		// No permission checks, handled through GridFieldDetailForm,
		// which can make the form readonly if no edit permissions are available.

		$data = new ArrayData(array(
			'Link' => Controller::join_links($gridField->Link('item'), $record->ID, 'edit')
		));

		return $data->renderWith('GridFieldEditButton');
	}

	/**
	 * Handle the actions and apply any changes to the GridField.
	 *
	 * @param GridField $gridField
	 * @param string $actionName
	 * @param mixed $arguments
	 * @param array $data - form data
	 *
	 * @return void
	 */
	public function handleAction(GridField $gridField, $actionName, $arguments, $data) {

	}
}
