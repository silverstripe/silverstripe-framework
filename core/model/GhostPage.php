<?php
/**
 * Ghost pages are placeholder pages that are used to facilitate the apparent support for
 * multiple parents.
 * 
 * @deprecated 2.3 Use VirtualPage
 * 
 * @package cms
 */
class GhostPage extends SiteTree implements HiddenClass {
	static $has_one = array(
		"LinkedPage" => "SiteTree",
	);
	
	function getCMSFields($val) {
		if($this->getField('LinkedPageID')) {
			return $this->LinkedPage()->getCMSFields($val);
		} else {
			return new FieldSet(
				new LabelField('GhostPageNoLinkedLabel',_t('GhostPage.NOLINKED', "This ghost page has no linked page."))
			);
		}
	}
	
	function hasField($fieldName) {
		return parent::hasField($fieldName) || ($this->getField('LinkedPageID') && $this->LinkedPage()->hasField($fieldName));
	}

	function __get($fieldName) {
		// echo "<li>$fieldName = ";
		// echo $this->getComponent('LinkedPage')->__get($fieldName);
		switch($fieldName) {
			case "ID":
			case "ClassName":
			case "LinkedPageID":
			case "ParentID":
			case "URLSegment":
			case "Parent":
				return parent::__get($fieldName);
			
			default:
				if($this->getField('LinkedPageID')) {
					return $this->getComponent('LinkedPage')->__get($fieldName);
				} else {
					return parent::__get($fieldName);
				}
		}
	}
	
	function __call($funcName, $args) {
		switch($funcName) {
			case "LinkedPage":
				return parent::__call($funcName, $args);
			
			default:
				if($this->getField('LinkedPageID')) {
					return $this->getComponent('LinkedPage')->__call($funcName, $args);
				} else {
					return parent::__call($funcName, $args);
				}
		}
	}
	
	function __set($fieldName, $fieldVal) {
		switch($fieldName) {
			case "ClassName": 
			case "MultipleParents";
				break;
				
			case "ID":
			case "LinkedPageID":
				parent::__set($fieldName, $fieldVal);
				break;
			
			default:
				if($this->getField("LinkedPageID")) {
					$this->LinkedPage()->__set($fieldName, $fieldVal);
				}
				parent::__set($fieldName, $fieldVal);
		}
	}
	
	function write() {
		if($this->getField("LinkedPageID")) {
			$this->LinkedPage()->write();
		}
		parent::write();
	}
	
	function MultipleParents() {
		return $this->LinkedPage()->MultipleParents();
	}
}

/**
 * Controller for GhostPages
 * @package cms
 */
class GhostPage_Controller extends Page_Controller {
	function getViewer($action) {
		return $this->LinkedPage()->getViewer($action);
	}
}

/**
 * Special type of ComponentSet just for GhostPages
 * @package cms
 */
class GhostPage_ComponentSet extends ComponentSet {
	function setOwner($ownerObj) {
		$this->ownerObj = $ownerObj;
	}	
	
	function add($item) {
		$id = is_object($item) ? $item->ID : $item;
		
		$ghost = new GhostPage();
		$ghost->setField('ParentID', $id);
		$ghost->setField('LinkedPageID', $this->ownerObj->ID);
		$ghost->setField('URLSegment', $this->ownerObj->URLSegment);
		$ghost->write();
	}
	
	function remove($item) {
		$id = is_object($item) ? $item->ID : $item;

		$ghosts = DataObject::get("GhostPage","ParentID = $id AND LinkedPageID = {$this->ownerObj->ID}");
		if($ghosts) {
			foreach($ghosts as $ghost) {
				$ghost->delete();
			}
		}
	}
	
	function removeMany($itemList) {
		if($itemList) {
			foreach($itemList as $item) {
				$this->remove($item);
			}
		}
	}
}

?>