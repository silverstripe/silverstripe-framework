<?php
/**
 * Adds a "level up" link to a GridField table, which is useful when viewing 
 * hierarchical data. Requires the managed record to have a "getParent()" 
 * method or has_one relationship called "Parent".
 *
 * @package forms
 * @subpackage fields-gridfield
 */
class GridFieldLevelup extends Object implements GridField_HTMLProvider {
	
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
	 * @var array Extra attributes for the link
	 */
	protected $attributes = array();

	/**
	 *
	 * @param integer $currentID - The ID of the current item; this button will find that item's parent
	 */
	public function __construct($currentID) {
		if($currentID && is_numeric($currentID)) $this->currentID = $currentID;
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

			if($parent) $parentID = $parent->ID;
			
			// Attributes
			$attrs = array_merge($this->attributes, array(
				'href' => sprintf($this->linkSpec, $parentID),
				'class' => 'cms-panel-link list-parent-link'
			));
			$attrsStr = '';
			foreach($attrs as $k => $v) $attrsStr .= " $k=\"" . Convert::raw2att($v) . "\"";

			$forTemplate = new ArrayData(array(
				'UpLink' => sprintf('<a%s>%s</a>', $attrsStr, _t('GridField.LEVELUP', 'Level up'))
			));

			return array(
				'before' => $forTemplate->renderWith('GridFieldLevelup'),
			);
		}
	}

	public function setAttributes($attrs) {
		$this->attributes = $attrs;
		return $this;
	}

	public function getAttributes() {
		return $this->attributes;
	}

	public function setLinkSpec($link) {
		$this->linkSpec = $link;
		return $this;
	}

	public function getLinkSpec() {
		return $this->linkSpec;
	}
} 
