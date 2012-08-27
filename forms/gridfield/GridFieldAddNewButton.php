<?php
/**
 * This component provides a button for opening the add new form provided by {@link GridFieldDetailForm}.
 *
 * @package framework
 * @subpackage gridfield
 */
class GridFieldAddNewButton implements GridField_HTMLProvider {

	protected $targetFragment;

	protected $buttonName;

	public function setButtonName($name) {
		$this->buttonName = $name;
		return $this;
	}

	public function __construct($targetFragment = 'before') {
		$this->targetFragment = $targetFragment;
	}

	public function getHTMLFragments($gridField) {
		if(!$this->buttonName) {
			// provide a default button name, can be changed by calling {@link setButtonName()} on this component
			$this->buttonName = _t('GridField.Add', 'Add {name}', array('name' => singleton($gridField->getModelClass())->i18n_singular_name()));
		}

		$data = new ArrayData(array(
			'NewLink' => Controller::join_links($gridField->Link('item'), 'new'),
			'ButtonName' => $this->buttonName,
		));

		return array(
			$this->targetFragment => $data->renderWith('GridFieldAddNewbutton'),
		);
	}

}
