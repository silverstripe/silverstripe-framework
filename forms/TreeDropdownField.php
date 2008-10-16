<?php

/**
 * @package forms
 * @subpackage fields-relational
 */

/**
 * Dropdown-like field that gives you a tree of items, using ajax.
 * @package forms
 * @subpackage fields-relational
 */
class TreeDropdownField extends FormField {
	protected $sourceObject, $keyField, $labelField, $filterFunc;
	protected $treeBaseID = 0;
	
	/**
	 * Create a new tree dropdown field.
	 * @param name The name of the field.
	 * @param title The label of the field.
	 * @param sourceObject The object-type to list in the tree.  Must be a 'hierachy' object.
	 * @param keyField The column of that object-type to return as the field value.  Defaults to ID
	 * @param labelField The column to show as the human-readable value in the tree.  Defaults to Title
	 */
	function __construct($name, $title, $sourceObject = "Group", $keyField = "ID", $labelField = "Title") {
		$this->sourceObject = $sourceObject;
		$this->keyField = $keyField;
		$this->labelField = $labelField;
		
		Requirements::css('sapphire/css/TreeDropdownField.css');
		
		parent::__construct($name, $title);
	}
	
	function setFilterFunction($filterFunc) {
		$this->filterFunc = $filterFunc;
	}
	/**
	 * Set the root node of the tree.  Defaults to 0, ie, the whole tree
	 */
	function setTreeBaseID($treeBaseID) {
		$this->treeBaseID = $treeBaseID;
	}
	
	function Field() {
		Requirements::javascript("jsparty/tree/tree.js");
		Requirements::css("jsparty/tree/tree.css");
		Requirements::javascript("sapphire/javascript/TreeSelectorField.js");
		
		if($this->value) {
			$record = DataObject::get_by_id($this->sourceObject, $this->value);
			$title = ($record) ? $record->Title : _t('DropdownField.CHOOSE', "(Choose)", PR_MEDIUM, 'Start-value of a dropdown');;
		} else {
			$title = _t('DropdownField.CHOOSE', "(Choose)", PR_MEDIUM, 'Start-value of a dropdown');
		}
		
		$id = $this->id();
		
		return <<<HTML
			<div  id="TreeDropdownField_$id" class="TreeDropdownField single"><input id="$id" type="hidden" name="$this->name" value="$this->value" /><span class="items">$title</span><a href="#" title="open" class="editLink">&nbsp;</a></div>		
HTML;
	}
	
	
	
	/**
	 * Return the site tree
	 */
	function gettree() {
		if($this->treeBaseID) $obj = DataObject::get_by_id($this->sourceObject, $this->treeBaseID);
		else $obj = singleton($this->sourceObject);
		
		
		if($this->filterFunc) $obj->setMarkingFilterFunction($this->filterFunc);
		else if($this->sourceObject == 'Folder') $obj->setMarkingFilter('ClassName', 'Folder');
		$obj->markPartialTree();

		// If we've already got values selected, make sure that we've got them in our tree
		if($_REQUEST['forceValues']) {
			$forceValues = split(" *, *", trim($_REQUEST['forceValues']));
			foreach($forceValues as $value) {
				$obj->markToExpose($this->getByKey($value));
			}			
		}
		
		$eval = '"<li id=\"selector-' . $this->name . '-$child->' . $this->keyField .  '\" class=\"$child->class" . $child->markingClasses() . "\"><a>" . $child->' . $this->labelField . ' . "</a>"';
		echo $obj->getChildrenAsUL("class=\"tree\"", $eval, null, true);
	}
	
	/**
	 * Return a subtree via Ajax
	 */
	public function getsubtree() {
		if($this->keyField == "ID") $obj = DataObject::get_by_id($this->sourceObject, $_REQUEST['SubtreeRootID']);
		else $obj = DataObject::get_one($this->sourceObject, "$this->keyField = '$_REQUEST[SubtreeRootID]'");

		if(!$obj) user_error("Can't find database record $this->sourceObject with $this->keyField = $_REQUEST[SubtreeRootID]", E_USER_ERROR);
		if($this->filterFunc) $obj->setMarkingFilterFunction($this->filterFunc);
		$obj->markPartialTree();

		$eval = '"<li id=\"selector-' . $this->name . '-$child->' . $this->keyField .  '\" class=\"$child->class" . $child->markingClasses() . "\"><a>" . $child->' . $this->labelField . ' . "</a>"';
		$tree = $obj->getChildrenAsUL("", $eval, null, true);
		echo substr(trim($tree), 4,-5);
	}
	
	public function getByKey($key) {
		if(!is_numeric($key)) {
			return false;
		}
		
		if($this->keyField == 'ID') {
			return DataObject::get_by_id($this->sourceObject, $key);
		} else {
			return DataObject::get_one($this->sourceObject, "$this->keyField = '$key'");
		}
	}
	
	/**
	 * Return the stack of values to be traversed to find the given key in the database
	 */
	public function getstack() {
		if($this->keyField == "ID") $page = DataObject::get_by_id($this->sourceObject, $_REQUEST['SubtreeRootID']);
		else $page = $this->getByKey($_REQUEST['SubtreeRootID']);
		
		while($page->ParentID) {
			echo $ids[] = $page->ID;
			$page = $page->Parent;
		}
		$ids[] = $page->ID;
		echo implode(",", array_reverse($ids));
	}

	function performReadonlyTransformation() {
		$fieldName = $this->labelField;
		$source = array(
			$this->value => $this->getByKey($this->value)->$fieldName
		);
		$field = new LookupField($this->name, $this->title, $source);
		$field->setValue($this->value);
		$field->setForm($this->form);
		return $field;
	}
	
	
}

?>
