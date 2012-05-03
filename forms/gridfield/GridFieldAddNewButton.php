<?php
/**
 * This component provides a button for opening the add new form provided by {@link GridFieldDetailForm}.
 *
 * @package framework
 * @subpackage gridfield
 */
class GridFieldAddNewButton implements GridField_HTMLProvider {
	protected $targetFragment;
	
	public function __construct($targetFragment = 'before') {
		$this->targetFragment = $targetFragment;
	}
	
	public function getHTMLFragments($gridField) {
		$data = new ArrayData(array(
			'NewLink' => Controller::join_links($gridField->Link('item'), 'new'),
		));
		return array(
			$this->targetFragment => $data->renderWith('GridFieldAddNewbutton'),
		);
	}

}
