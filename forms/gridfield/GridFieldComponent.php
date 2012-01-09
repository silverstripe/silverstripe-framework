<?php

/**
 * Base interface for all components that can be added to GridField. 
 */
interface GridFieldComponent {
	
}

/**
 * A GridField manipulator that provides HTML for the header/footer rows, or for before/after the template 
 */
interface GridField_HTMLProvider extends GridFieldComponent {
	/**
	 * Returns a map with 4 keys 'header', 'footer', 'before', 'after'.  Each of these can contain an
	 * HTML fragment and each of these are optional.
	 */
	function getHTMLFragments($gridField);
}
interface GridField_ColumnProvider extends GridFieldComponent {
	function augmentColumns($gridField, &$columns);
	function getColumnsHandled($gridField);
	function getColumnContent($gridField, $record, $columnName);
	function getColumnAttributes($gridField, $record, $columnName);
	function getColumnMetadata($gridField, $columnName);
}
interface GridField_ActionProvider extends GridFieldComponent {
	/**
	 * Return a list of the actions handled by this action provider
	 */
	function getActions($gridField);
	
	/**
	 * Handle an action on the given gridField.
	 */
	function handleAction(GridField $gridField, $actionName, $arguments, $data);
}
interface GridField_DataManipulator extends GridFieldComponent {
	/**
	 * Manipulate the datalist as needed by this grid modifier.
	 * Return the new DataList. 
	 */
	function getManipulatedData(GridField $gridField, SS_List $dataList);
}