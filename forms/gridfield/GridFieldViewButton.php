<?php
/**
 * A button that allows a user to view readonly details of a record. This is
 * disabled by default and intended for use in readonly grid fields.
 *
 * @package framework
 */
class GridFieldViewButton implements GridField_ColumnProvider {

	public function augmentColumns($field, &$cols) {
		if(!in_array('Actions', $cols)) $cols[] = 'Actions';
	}

	public function getColumnsHandled($field) {
		return array('Actions');
	}

	public function getColumnContent($field, $record, $col) {
		if($record->canView()) {
			$data = new ArrayData(array(
				'Link' => Controller::join_links($field->Link('item'), $record->ID, 'view')
			));
			return $data->renderWith('GridFieldViewButton');
		}
	}

	public function getColumnAttributes($field, $record, $col) {
		return array('class' => 'col-buttons');
	}

	public function getColumnMetadata($gridField, $col) {
		return array('title' => null);
	}

}
