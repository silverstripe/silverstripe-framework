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
	 * @return Array
	 */
	function getHTMLFragments($gridField);
}

/**
 * Add a new column to the table display body, or modify existing columns.
 * Used once per record/row.
 */
interface GridField_ColumnProvider extends GridFieldComponent {

	/**
	 * Modify the list of columns displayed in the table.
	 * See {@link GridField->getDisplayFields()} and {@link GridFieldDefaultColumns}.
	 * 
	 * @param  GridField
	 * @param  Array List reference of all column names.
	 */
	function augmentColumns($gridField, &$columns);

	/**
	 * Names of all columns which are affected by this component.
	 * 
	 * @param  GridField
	 * @return Array 
	 */
	function getColumnsHandled($gridField);

	/**
	 * HTML for the column, content of the <td> element.
	 * 
	 * @param  GridField
	 * @param  DataObject Record displayed in this row
	 * @param  String 
	 * @return String HTML for the column. Return NULL to skip.
	 */
	function getColumnContent($gridField, $record, $columnName);

	/**
	 * Attributes for the element containing the content returned by {@link getColumnContent()}.
	 * 
	 * @param  GridField
	 * @param  DataObject Record displayed in this row
	 * @param  String
	 * @return Array
	 */
	function getColumnAttributes($gridField, $record, $columnName);

	/**
	 * Additional metadata about the column which can be used by other components,
	 * e.g. to set a title for a search column header.
	 * 
	 * @param GridField
	 * @param  String
	 * @return Array Map of arbitrary metadata identifiers to their values.
	 */
	function getColumnMetadata($gridField, $columnName);
}

/**
 * An action is defined by two things: an action name, and zero or more named arguments.  
 * There is no built-in notion of a record-specific or column-specific action, 
 * but you may choose to define an argument such as ColumnName or RecordID in order to implement these.
 * Does not provide interface elements to call those actions, see {@link GridField_FormAction}.
 */
interface GridField_ActionProvider extends GridFieldComponent {
	/**
	 * Return a list of the actions handled by this action provider.
	 * Used to identify the action later on through the $actionName parameter in {@link handleAction}.
	 * There is no namespacing on these actions, so you need to ensure that they don't conflict with other components.
	 * 
	 * @param GridField
	 * @return Array with action identifier strings. 
	 */
	function getActions($gridField);
	
	/**
	 * Handle an action on the given grid field.
	 * Calls ALL components for every action handled, so the component
	 * needs to ensure it only accepts actions it is actually supposed to handle.
	 * 
	 * @param GridField
	 * @param String Action identifier, see {@link getActions()}.
	 * @param Array Arguments relevant for this 
	 * @param Array All form data
	 */
	function handleAction(GridField $gridField, $actionName, $arguments, $data);
}

/**
 * Can modify the data list.  
 * For example, a paginating component can apply a limit, or a sorting component can apply a sort.  
 * Generally, the data manipulator will make use of to `GridState` variables to decide 
 * how to modify the data list (see {@link GridState}).
 */
interface GridField_DataManipulator extends GridFieldComponent {
	/**
	 * Manipulate the datalist as needed by this grid modifier.
	 * 
	 * @param GridField
	 * @param SS_List
	 * @return DataList
	 */
	function getManipulatedData(GridField $gridField, SS_List $dataList);
}

/**
 * Sometimes an action isn't enough: you need to provide additional support URLs for the grid.  
 * These URLs may return user-visible content, for example a pop-up form for editing a record's details, 
 * or they may be support URLs for front-end functionality. 
 * For example a URL that will return JSON-formatted data for a javascript grid control.
 */
interface GridField_URLHandler extends GridFieldComponent {
	/**
	 * Return URLs to be handled by this grid field, in an array the same form as $url_handlers.
	 * Handler methods will be called on the component, rather than the grid field.
	 */
	function getURLHandlers($gridField);
}