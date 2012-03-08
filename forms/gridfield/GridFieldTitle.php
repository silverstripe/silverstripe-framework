<?php
/**
 * Adding this class to a {@link GridFieldConfig} of a {@link GridField} adds a header title to that field.
 * The header serves double duty of displaying the name of the data the GridField is showing and
 * providing an "add new" button to create new object instances.
 *
 * The reason for making "add new" part of the title component is to make it easier for the user to tell
 * which "add new" button belongs to which datagrid, in the case where multiple datagrids are on a single
 * page. It is also a more efficient use of screen space.
 *
 * The default DataGrid includes the add button. You can hide the button by setting a boolean using the
 * setNewEnabled() method
 *
 * @package sapphire
 * @subpackage gridfield
 */
class GridFieldTitle implements GridField_HTMLProvider {

	protected $newEnabled = true;

	function getHTMLFragments($gridField) {
		return array(
			'header' => $gridField->customise(array(
				'NewLink' => Controller::join_links($gridField->Link('item'), 'new'),
				'NewEnabled' => $this->getNewEnabled()
			))->renderWith('GridFieldTitle')
		);
	}

	/**
	 * Returns whether or not the "add new" button will appear when rendering this DataGrid title
	 * @return bool
	 */
	function getNewEnabled() {
		return $this->newEnabled;
	}

	/**
	 * Enable or disable the "add new" button to add new DataGrid object instances
	 * @param $enabled
	 */
	function setNewEnabled($enabled) {
		$this->newEnabled = $enabled;
	}
}

?>