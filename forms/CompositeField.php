<?php
/**
 * Base class for all fields that contain other fields.
 * Implements sequentialisation - so that when we're saving / loading data, we can populate
 * a tabbed form properly.  All of the children are stored in $this->children
 * @package forms
 * @subpackage fields-structural
 */
class CompositeField extends FormField {
	
	/**
	 * @var FieldSet
	 */
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
		if($children instanceof FieldSet) {
			$this->children = $children;
		} elseif(is_array($children)) {
			$this->children = new FieldSet($children); 
		} else {
			$children = is_array(func_get_args()) ? func_get_args() : array();
			$this->children = new FieldSet($children); 
		}
		$this->children->setContainerField($this);
		
		// Skipping FormField::__construct(), but we have to make sure this
		// doesn't count as a broken constructor
		$this->brokenOnConstruct = false;
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
	 * Accessor method for $this->children
	 * @return FieldSet
	 */
	public function getChildren() {
		return $this->children;
	}
	
	/**
	 * @param FieldSet $children
	 */
	public function setChildren($children) {
		$this->children = $children;
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
	public function collateDataFields(&$list, $saveableOnly = false) {
		foreach($this->children as $field) {
			if(is_object($field)) {
				if($field->isComposite()) $field->collateDataFields($list, $saveableOnly);
				if($saveableOnly) {
					$isIncluded =  ($field->hasData() && !$field->isReadonly() && !$field->isDisabled());
				} else {
					$isIncluded =  ($field->hasData());
				}
				if($isIncluded) {
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
	
	/**
	 * @uses FieldSet->insertBefore()
	 */
	public function insertBefore($field, $insertBefore) {
		$ret = $this->children->insertBefore($field, $insertBefore);
		$this->sequentialSet = null;
		return $ret;
	}

	public function insertAfter($field, $insertAfter) {
		$ret = $this->children->insertAfter($field, $insertAfter);
		$this->sequentialSet = null;
		return $ret;
	}

	/**
	 * Remove a field from this CompositeField by Name.
	 * The field could also be inside a CompositeField.
	 * 
	 * @param string $fieldName The name of the field
	 * @param boolean $dataFieldOnly If this is true, then a field will only
	 * be removed if it's a data field.  Dataless fields, such as tabs, will
	 * be left as-is.
	 */
	public function removeByName($fieldName, $dataFieldOnly = false) {
		$this->children->removeByName($fieldName, $dataFieldOnly);
	}

	public function replaceField($fieldName, $newField) {
		return $this->children->replaceField($fieldName, $newField);
	}

	function rootFieldSet() {
		if(is_object($this->containerFieldSet)) return $this->containerFieldSet->rootFieldSet();
		else return $this->children;
	}
	
	/**
	 * Return a readonly version of this field.  Keeps the composition but returns readonly
	 * versions of all the children
	 */
	public function performReadonlyTransformation() {
		$newChildren = new FieldSet();
		$clone = clone $this;
		foreach($clone->getChildren() as $idx => $child) {
			if(is_object($child)) $child = $child->transform(new ReadonlyTransformation());
			$newChildren->push($child, $idx);
		}

		$clone->children = $newChildren;
		$clone->readonly = true;
		return $clone;
	}

	/**
	 * Return a readonly version of this field.  Keeps the composition but returns readonly
	 * versions of all the children
	 */
	public function performDisabledTransformation($trans) {
		$newChildren = new FieldSet();
		$clone = clone $this;
		if($clone->getChildren()) foreach($clone->getChildren() as $idx => $child) {
			if(is_object($child)) {
				$child = $child->transform($trans);
			}
			$newChildren->push($child, $idx);
		}

		$clone->children = $newChildren;
		$clone->readonly = true;
		
		return $clone;
	}

	function IsReadonly() {
		return $this->readonly;
	}
	
	/**
	 * Find the numerical position of a field within
	 * the children collection. Doesn't work recursively.
	 * 
	 * @param string|FormField
	 * @return Position in children collection (first position starts with 0). Returns FALSE if the field can't be found.
	 */
	function fieldPosition($field) {
		if(is_string($field)) $field = $this->fieldByName($field);
		if(!$field) return false;
		
		$i = 0;
		foreach($this->children as $child) {
			if($child->Name() == $field->Name()) return $i;
			$i++;
		}
		
		return false;
	}
	
	/**
	 * Transform the named field into a readonly feld.
	 * 
	 * @param string|FormField
	 */
	function makeFieldReadonly($field) {
		$fieldName = ($field instanceof FormField) ? $field->Name() : $field;
		
		// Iterate on items, looking for the applicable field
		foreach($this->children as $i => $item) {
			if($item->isComposite()) {
				$item->makeFieldReadonly($fieldName);
			} else {
				// Once it's found, use FormField::transform to turn the field into a readonly version of itself.
				if($item->Name() == $fieldName) {
					$this->children->replaceField($fieldName, $item->transform(new ReadonlyTransformation()));

					// Clear an internal cache
					$this->sequentialSet = null;

					// A true results indicates that the field was foudn
					return true;
				}
			}
		}
		return false;
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
			$valid = ($child && $child->validate($validator) && $valid);
		}
		
		return $valid;
	}
}

?>