<?php

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

	function getNewEnabled() {
		return $this->newEnabled;
	}

	function setNewEnabled($enabled) {
		$this->newEnabled = $enabled;
	}
}

?>