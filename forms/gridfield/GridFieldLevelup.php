<?php

class GridFieldLevelup implements GridField_HTMLProvider{
	/**
	 * @var integer - the record id of the level up to
	 */
	protected $currentID = null;

	/**
	 * sprintf() spec for link to link to parent.
	 * Only supports one variable replacement - the parent ID.
	 * @var string
	 */
	protected $linkSpec = '';

	/**
	 * Pjax targets entered into data attribute for this component
	 * A comma separated string of element IDs
	 * @var string
	 */
	protected $pjaxTargets = '';

	/**
	 *
	 * @param integer $currentID - The ID of the current item; this button will find that item's parent
	 * @param string $linkSpec - sprintf link spec, see variable above for more info
	 * @param string $pjaxTargets - pjax target elements set into the data-pjax-targets attr
	 */
	public function __construct($currentID, $linkSpec, $pjaxTargets = '') {
		if($currentID && is_numeric($currentID)) {
			$this->currentID = $currentID;
		}

		if($linkSpec) $this->linkSpec = $linkSpec;
		if($pjaxTargets) $this->pjaxTargets = $pjaxTargets;
	}
	
	public function getHTMLFragments($gridField) {
		$modelClass = $gridField->getModelClass();
		$parentID = 0;

		if($this->currentID) {
			$modelObj = DataObject::get_by_id($modelClass, $this->currentID);

			if($modelObj->hasMethod('getParent')) {
				$parent = $modelObj->getParent();
			} elseif($modelObj->ParentID) {
				$parent = $modelObj->Parent();
			}

			if($parent) {
				$parentID = $parent->ID;
			}

			$forTemplate = new ArrayData(array(
				'UpLink' => sprintf(
					'<a class="cms-panel-link list-parent-link" href="%s" data-pjax-target="%s">%s</a>',
					sprintf($this->linkSpec, $parentID),
					$this->pjaxTargets,
					_t('GridField.LEVELUP', 'Level up')
				),
			));

			return array(
				'before' => $forTemplate->renderWith('GridFieldLevelup'),
			);
		}
	}
} 
