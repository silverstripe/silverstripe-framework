<?php

class GridFieldTitle implements GridField_HTMLProvider {
	function getHTMLFragments($gridField) {
		return array(
			'header' => $gridField->renderWith('GridFieldTitle')
		);
	}
}

?>