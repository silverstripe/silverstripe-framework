<?php

class PermissionCheckboxSetField extends CheckboxSetField {
	function __construct($name, $title, $managedClass, $filterField) {
		$this->filterField = $filterField;
		$this->managedClass = $managedClass;
		parent::__construct($name, $title, Permission::get_codes(true)); 
	}

	function Field() {
		Requirements::css(SAPPHIRE_DIR . '/css/CheckboxSetField.css');

		$source = $this->source;
		$values = array();
		
		// Get values from the join, if available
		if(is_object($this->form)) {
			$record = $this->form->getRecord();
			if ($record && $record->hasMethod($this->name)) {
				$funcName = $this->name;
				$join = $record->$funcName();

				if($join) {
					foreach($join as $joinItem) {
						$values[] = $joinItem->Code;
					}
				}
			}
		}

		$odd = 0;
		$options = '';

		if($source) {
			foreach($source as $categoryName => $permissions) {
				$options .= "<li><h5>$categoryName</h5></li>";
				foreach($permissions as $code => $permission) {
					$key = $code;
					$value = $permission['name'];
			
					$odd = ($odd + 1) % 2;
					$extraClass = $odd ? 'odd' : 'even';
					$extraClass .= ' val' . str_replace(' ', '', $key);
					$itemID = $this->id() . '_' . ereg_replace('[^a-zA-Z0-9]+', '', $key);
					$checked = '';
			
					
					$checked = in_array($key, $values) ? ' checked="checked"' : '';
					
					$title = $permission['help'] ? 'title="'.htmlentities($permission['help']).'" ' : '';
			
					$options .= "<li class=\"$extraClass\"><input id=\"$itemID\" name=\"$this->name[$key]\" type=\"checkbox\" value=\"$key\"$checked class=\"checkbox\" /> <label {$title}for=\"$itemID\">$value</label></li>\n"; 
				}
			}
		}
		
		return "<ul id=\"{$this->id()}\" class=\"optionset checkboxsetfield{$this->extraClass()}\">\n$options</ul>\n"; 
	}
	
	function saveInto(DataObject $record) {
		$fieldname = $this->name;
		$managedClass = $this->managedClass;
		$record->$fieldname()->removeAll();
		if($fieldname && $record && ($record->has_many($fieldname) || $record->many_many($fieldname))) {
			$idList = array();
			if($this->value) foreach($this->value as $id => $bool) {
			   if($bool) {
					$perm = new $managedClass();
					$perm->{$this->filterField} = $record->ID;
					$perm->Code = $id;
					$perm->write();
				}
			}
		}
	}
}