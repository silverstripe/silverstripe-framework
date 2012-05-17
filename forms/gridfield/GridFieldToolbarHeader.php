<?php
/**
 * Adding this class to a {@link GridFieldConfig} of a {@link GridField} adds a header title to that field.
 * The header serves to display the name of the data the GridField is showing.
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
