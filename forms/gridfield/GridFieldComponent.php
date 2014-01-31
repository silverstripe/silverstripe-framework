<?php

/**
 * Base interface for all components that can be added to GridField.
 *
 * @package forms
 * @subpackage fields-gridfield
 */
interface GridFieldComponent {	
}

/**
 * A GridField manipulator that provides HTML for the header/footer rows, or f
 * or before/after the template.
 *
 * @package forms
 * @subpackage fields-gridfield
 */
interface GridField_HTMLProvider extends GridFieldComponent {
	
	/**
	 * Returns a map where the keys are fragment names and the values are 
	 * pieces of HTML to add to these fragments.
	 *
	 * Here are 4 built-in fragments: 'header', 'footer', 'before', and 
	 * 'after', but components may also specify fragments of their own.
	 * 
	 * To specify a new fragment, specify a new fragment by including the 
	 * text "$DefineFragment(fragmentname)" in the HTML that you return. 
	 *
	 * Fragment names should only contain alphanumerics, -, and _.
	 *
	 * If you attempt to return HTML for a fragment that doesn't exist, an 
	 * exception will be thrown when the {@link GridField} is rendered.
	 *
	 * @return array
	 */
	public function getHTMLFragments($gridField);
}

/**
 * Add a new column to the table display body, or modify existing columns.
 *
 * Used once per record/row.
 *
 * @package forms
 * @subpackage fields-gridfield
 */
interface GridField_ColumnProvider extends GridFieldComponent {

	/**
	 * Modify the list of columns displayed in the table.
	 *
	 * @see {@link GridFieldDataColumns->getDisplayFields()}
	 * @see {@link GridFieldDataColumns}.
	 * 
	 * @param GridField $gridField
	 * @param array - List reference of all column names.
	 */
	public function augmentColumns($gridField, &$columns);

	/**
	 * Names of all columns which are affected by this component.
	 * 
	 * @param GridField $gridField
	 * @return array 
	 */
	public function getColumnsHandled($gridField);

	/**
	 * HTML for the column, content of the <td> element.
	 * 
	 * @param  GridField $gridField
	 * @param  DataObject $record - Record displayed in this row
	 * @param  string $columnName
	 * @return string - HTML for the column. Return NULL to skip.
	 */
	public function getColumnContent($gridField, $record, $columnName);

	/**
	 * Attributes for the element containing the content returned by {@link getColumnContent()}.
	 * 
	 * @param  GridField $gridField
	 * @param  DataObject $record displayed in this row
	 * @param  string $columnName
	 * @return array
	 */
	public function getColumnAttributes($gridField, $record, $columnName);

	/**
	 * Additional metadata about the column which can be used by other components,
	 * e.g. to set a title for a search column header.
	 * 
	 * @param GridField $gridField
	 * @param string $columnName
	 * @return array - Map of arbitrary metadata identifiers to their values.
	 */
	public function getColumnMetadata($gridField, $columnName);
}

/**
 * An action is defined by two things: an action name, and zero or more named 
 * arguments.  
 *
 * There is no built-in notion of a record-specific or column-specific action, 
 * but you may choose to define an argument such as ColumnName or RecordID in 
 * order to implement these.
 *
 * Does not provide interface elements to call those actions.
 *
 * @see {@link GridField_FormAction}.
 *
 * @package forms
 * @subpackage fields-gridfield
 */
interface GridField_ActionProvider extends GridFieldComponent {
	/**
	 * Return a list of the actions handled by this action provider.
	 *
	 * Used to identify the action later on through the $actionName parameter 
	 * in {@link handleAction}.
	 *
	 * There is no namespacing on these actions, so you need to ensure that 
	 * they don't conflict with other components.
	 * 
	 * @param GridField
	 * @return Array with action identifier strings. 
	 */
	public function getActions($gridField);
	
	/**
	 * Handle an action on the given {@link GridField}.
	 *
	 * Calls ALL components for every action handled, so the component needs 
	 * to ensure it only accepts actions it is actually supposed to handle.
	 * 
	 * @param GridField
	 * @param String Action identifier, see {@link getActions()}.
	 * @param Array Arguments relevant for this 
	 * @param Array All form data
	 */
	public function handleAction(GridField $gridField, $actionName, $arguments, $data);
}

/**
 * Can modify the data list. 
 *
 * For example, a paginating component can apply a limit, or a sorting 
 * component can apply a sort.  
 *
 * Generally, the data manipulator will make use of to {@link GridState} 
 * variables to decide how to modify the {@link DataList}.
 *
 * @package forms
 * @subpackage fields-gridfield
 */
interface GridField_DataManipulator extends GridFieldComponent {

	/**
	 * Manipulate the {@link DataList} as needed by this grid modifier.
	 * 
	 * @param GridField
	 * @param SS_List
	 * @return DataList
	 */
	public function getManipulatedData(GridField $gridField, SS_List $dataList);
}

/**
 * Sometimes an action isn't enough: you need to provide additional support 
 * URLs for the {@link GridField}.
 *
 * These URLs may return user-visible content, for example a pop-up form for 
 * editing a record's details, or they may be support URLs for front-end 
 * functionality. 
 *
 * For example a URL that will return JSON-formatted data for a javascript 
 * grid control.
 *
 * @package forms
 * @subpackage fields-gridfield
 */
interface GridField_URLHandler extends GridFieldComponent {

	/**
	 * Return URLs to be handled by this grid field, in an array the same form 
	 * as $url_handlers.
	 *
	 * Handler methods will be called on the component, rather than the 
	 * {@link GridField}.
	 */
	public function getURLHandlers($gridField);
}

/**
 * A component which is used to handle when a {@link GridField} is saved into 
 * a record.
 *
 * @package forms
 * @subpackage fields-gridfield
 */
interface GridField_SaveHandler extends GridFieldComponent {

	/**
	 * Called when a grid field is saved - i.e. the form is submitted.
	 *
	 * @param GridField $field
	 * @param DataObjectInterface $record
	 */
	public function handleSave(GridField $grid, DataObjectInterface $record);

}
