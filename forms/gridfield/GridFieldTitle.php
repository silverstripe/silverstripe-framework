<?php

class GridFieldTitle implements GridField_HTMLProvider {
	function getHTMLFragments($gridField) {
		return array(
			'header' => $gridField->customise(array(
				'NewLink' => Controller::join_links($gridField->Link('item'), 'new')
			))->renderWith('GridFieldTitle')
		);
	}
}

?>