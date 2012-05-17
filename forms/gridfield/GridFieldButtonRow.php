<?php
/**
 * Adding this class to a {@link GridFieldConfig} of a {@link GridField} adds a buttonrow to that field.
 * The button row provides a space for actions on this grid.
 * 
 * This row provides two new HTML fragment spaces: 'toolbar-header-left' and 'toolbar-header-right'.
 *
 * @package framework
 * @subpackage gridfield
 */
class GridFieldButtonRow implements GridField_HTMLProvider {
	public function getHTMLFragments( $gridField) {
		return array(
			'buttons' => $gridField->renderWith('GridFieldButtonRow')
		);
	}
}
