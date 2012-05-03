<?php
/**
 * Adding this class to a {@link GridFieldConfig} of a {@link GridField} adds a header title to that field.
 * The header serves double duty of displaying the name of the data the GridField is showing and
 * providing a space for actions on this grid.
 * 
 * This header provides two new HTML fragment spaces: 'toolbar-header-left' and 'toolbar-header-right'.
 *
 * @package framework
 * @subpackage gridfield
 */
class GridFieldToolbarHeader implements GridField_HTMLProvider {
	public function getHTMLFragments( $gridField) {
		return array(
			'header' => $gridField->renderWith('GridFieldToolbarHeader')
		);
	}
}
