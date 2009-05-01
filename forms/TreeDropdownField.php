<?php
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
		Requirements::css(SAPPHIRE_DIR . '/css/TreeDropdownField.css');
		Requirements::javascript(THIRDPARTY_DIR . "/tree/tree.js");
		Requirements::css(THIRDPARTY_DIR . "/tree/tree.css");
		Requirements::add_i18n_javascript(SAPPHIRE_DIR . '/javascript/lang');
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/TreeSelectorField.js");
		
		if($this->value) {
			$record = $this->getByKey($this->value);
			$title = ($record) ? $record->Title : _t('DropdownField.CHOOSE', "(Choose)", PR_MEDIUM, 'Start-value of a dropdown');
		} else {
			$title = _t('DropdownField.CHOOSE', "(Choose)", PR_MEDIUM, 'Start-value of a dropdown');
		}
		
		$id = $this->id();
		
		$classes = "TreeDropdownField single";
		if($this->extraClass()) $classes .= ' ' . $this->extraClass();
		
		return <<<HTML
			<div  id="TreeDropdownField_$id" class="$classes"><input id="$id" type="hidden" name="$this->name" value="$this->value" /><span class="items">$title</span><a href="#" title="open" class="editLink">&nbsp;</a></div>		
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
		$obj = $this->getByKey($_REQUEST['SubtreeRootID']);
		if(!$obj) user_error("Can't find database record $this->sourceObject with $this->keyField = $_REQUEST[SubtreeRootID]", E_USER_ERROR);

		if($this->filterFunc) $obj->setMarkingFilterFunction($this->filterFunc);
		else if($this->sourceObject == 'Folder') $obj->setMarkingFilter('ClassName', 'Folder');
		$obj->markPartialTree();

		$eval = '"<li id=\"selector-' . $this->name . '-$child->' . $this->keyField .  '\" class=\"$child->class" . $child->markingClasses() . "\"><a>" . $child->' . $this->labelField . ' . "</a>"';
		$tree = $obj->getChildrenAsUL("", $eval, null, true);
		echo substr(trim($tree), 4,-5);
	}
	
	/**
	 * @return DataObject
	 */
	public function getByKey($key) {
		if($this->keyField == 'ID') {
			return DataObject::get_by_id($this->sourceObject, $key);
		} else {
			$SQL_key = Convert::raw2sql($key);
			return DataObject::get_one($this->sourceObject, "$this->keyField = '$SQL_key'");
		}
	}
	
	/**
	 * Return the stack of values to be traversed to find the given key in the database
	 */
	public function getstack() {
		$page = $this->getByKey($_REQUEST['SubtreeRootID']);
		
		while($page->ParentID) {
			echo $ids[] = $page->ID;
			$page = $page->Parent;
		}
		$ids[] = $page->ID;
		echo implode(",", array_reverse($ids));
	}

	function performReadonlyTransformation() {
		$fieldName = $this->labelField;
		if($this->value) {
			$obj = ($this->getByKey($this->value)) ? $this->getByKey($this->value)->$fieldName : '';
		} else {
			$obj = null;
		}
		$source = array(
			$this->value => $obj
		);
		$field = new LookupField($this->name, $this->title, $source);
		$field->setValue($this->value);
		$field->setForm($this->form);
		$field->setReadonly(true);
		return $field;
	}
	
	
}

?>