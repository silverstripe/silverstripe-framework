<?php

/**
 * @package forms
 * @subpackage fields-structural
 */

/**
 * Base class for all fields that contain other fields.
 * Implements sequentialisation - so that when we're saving / loading data, we can populate
 * a tabbed form properly.  All of the children are stored in $this->children
 * @package forms
 * @subpackage fields-structural
 */
class CompositeField extends FormField {
	protected $children;
	/**
	 * Set to true when this field is a readonly field
	 */
	protected $readonly;
	
	/**
	 * @var $columnCount int Toggle different css-rendering for multiple columns 
	 * ("onecolumn", "twocolumns", "threecolumns"). The content is determined
	 * by the $children-array, so wrap all items you want to have grouped in a
	 * column inside a CompositeField.
	 * Caution: Please make sure that this variable actually matches the 
	 * count of your $children.
	 */
	protected $columnCount = null;
	
	public function __construct($children = null) {
		if(is_a($children, 'FieldSet')) {
			$this->children = $children;
		} elseif(is_array($children)) {
			$this->children = new FieldSet($children); 
		} else {
			$children = is_array(func_get_args()) ? func_get_args() : array();
			$this->children = new FieldSet($children); 
		}
				
		Object::__construct();
	}

	/**
	 * Returns all the sub-fields, suitable for <% control FieldSet %>
	 */
	public function FieldSet() {
		return $this->children;
	}
	
	public function setID($id) {
		$this->id = $id;
	}
	
	public function Field() {
		return $this->FieldHolder();
	}

	/**
	 * Returns the fields nested inside another DIV
	 */
	function FieldHolder() {
		$fs = $this->FieldSet();
		$idAtt = isset($this->id) ? " id=\"{$this->id}\"" : '';
		$className = ($this->columnCount) ? "field CompositeField {$this->extraClass()} multicolumn" : "field CompositeField {$this->extraClass()}";
		$content = "<div class=\"$className\"$idAtt>\n";
		
		foreach($fs as $subfield) {
			if($this->columnCount) {
				$className = "column{$this->columnCount}";
				if(!next($fs)) $className .= " lastcolumn";
				$content .= "\n<div class=\"{$className}\">\n" . $subfield->FieldHolder() . "\n</div>\n";
			} else if($subfield){
				$content .= "\n" . $subfield->FieldHolder() . "\n";
			}
		}
		$content .= "</div>\n";
				
		return $content;
	}
		
	/**
	 * Returns the fields in the restricted field holder inside a DIV.
	 */
	function SmallFieldHolder() {//return $this->FieldHolder();
		$fs = $this->FieldSet();
		$idAtt = isset($this->id) ? " id=\"{$this->id}\"" : '';
		$className = ($this->columnCount) ? "field CompositeField {$this->extraClass()} multicolumn" : "field CompositeField {$this->extraClass()}";
		$content = "<div class=\"$className\"$idAtt>";
		
		foreach($fs as $subfield) {//echo ' subf'.$subfield->Name();
			if($this->columnCount) {
				$className = "column{$this->columnCount}";
				if(!next($fs)) $className .= " lastcolumn";
				$content .= "<div class=\"{$className}\">" . $subfield->FieldHolder() . "</div>";
			} else if($subfield){
				$content .= $subfield->SmallFieldHolder() . " ";
			}
		}	
		$content .= "</div>";
	
		return $content;
	}	
	/**
	 * Add all of the non-composite fields contained within this field to the list.
	 * Sequentialisation is used when connecting the form to its data source
	 */
	public function collateDataFields(&$list) {
		foreach($this->children as $field) {
			if(is_object($field)) {
				if($field->isComposite()) $field->collateDataFields($list);
				if($field->hasData()) {
					$name = $field->Name();
					if($name) {
						$formName = (isset($this->form)) ? $this->form->FormName() : '(unknown form)';
						if(isset($list[$name])) user_error("collateDataFields() I noticed that a field called '$name' appears twice in your form: '{$formName}'.  One is a '{$field->class}' and the other is a '{$list[$name]->class}'", E_USER_ERROR);
						$list[$name] = $field;
					}
				}
			}
		}
	}

	function setForm($form) {
		foreach($this->children as $f) if(is_object($f)) $f->setForm($form);
		parent::setForm($form);
	}
	
	function setColumnCount($columnCount) {
		$this->columnCount = $columnCount;
	}
	
	function isComposite() { return true; }
	function hasData() { return false; }

	public function fieldByName($name) {
		return $this->children->fieldByName($name);
	}
	/**
	 * Add a new child field to the end of the set.
	 */
	public function push(FormField $field) {
		$this->children->push($field);
	}
	public function insertBefore($field, $insertBefore) {
		return $this->children->insertBefore($field, $insertBefore);
	}

	public function insertBeforeRecursive($field, $insertBefore, $level = 0) {
		return $this->children->insertBeforeRecursive($field, $insertBefore, $level+1);
	}
	public function removeByName($fieldName) {
		$this->children->removeByName($fieldName);
	}

	public function replaceField($fieldName, $newField) {
		return $this->children->replaceField($fieldName, $newField);
	}
	
	/**
	 * Return a readonly version of this field.  Keeps the composition but returns readonly
	 * versions of all the children
	 */
	public function performReadonlyTransformation() {
		$newChildren = new FieldSet();
		foreach($this->children as $idx => $child) {
			if(is_object($child)) $child = $child->transform(new ReadonlyTransformation());
			$newChildren->push($child, $idx);
		}

		$this->children = $newChildren;
		$this->readonly = true;
		return $this;
	}

	/**
	 * Return a readonly version of this field.  Keeps the composition but returns readonly
	 * versions of all the children
	 */
	public function performDisabledTransformation($trans) {
		$newChildren = new FieldSet();
		if($this->children) foreach($this->children as $idx => $child) {
			if(is_object($child)) {
				$child = $child->transform($trans);
			}
			$newChildren->push($child, $idx);
		}

		$this->children = $newChildren;
		$this->readonly = true;
		
		return $this;
	}

	function IsReadonly() {
		return $this->readonly;
	}

	function debug() {
		$result = "$this->class ($this->name) <ul>";
		foreach($this->children as $child) {
			$result .= "<li>" . Debug::text($child) . "&nbsp;</li>";
		}
		$result .= "</ul>";
		return $result;
	}
	
	function validate($validator){
		
		$valid = true;
		foreach($this->children as $idx => $child){
			$valid = ($child->validate($validator) && $valid);
		}
		
		return $valid;
	}
}

?>