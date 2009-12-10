<?php

class PermissionCheckboxSetField extends CheckboxSetField {
	
	/**
	 * @var Array Filter certain permission codes from the output.
	 * Useful to simplify the interface
	 */
	protected $hiddenPermissions = array();
	
	function __construct($name, $title, $managedClass, $filterField, $record = null) {
		$this->filterField = $filterField;
		$this->managedClass = $managedClass;
		$this->record = $record;
		parent::__construct($name, $title, Permission::get_codes(true)); 
	}
	
	/**
	 * @param Array $codes
	 */
	function setHiddenPermissions($codes) {
		$this->hiddenPermissions = $codes;
	}
	
	/**
	 * @return Array
	 */
	function getHiddenPermissions() {
		return $this->hiddenPermissions;
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

		$inheritedItems = array();
		if ($this->record) {
			if ($this->record->Roles()->Count()) {
				foreach($this->record->Roles() as $role) {
					foreach($role->Codes() as $code) {
						if (!isset($inheritedItems[$code->Code])) $inheritedItems[$code->Code] = array();
						$inheritedItems[$code->Code][] = 'from role '.$role->Title;
					}
				}
			}
			
			$parentGroups = $this->record->getAllParents();
			if ($parentGroups) {
				foreach ($parentGroups as $parent) {
					if ($parent->Roles()->Count()) {
						foreach($parent->Roles() as $role) {
							if ($role->Codes()) {
								foreach($role->Codes() as $code) {
									if (!isset($inheritedItems[$code->Code])) $inheritedItems[$code->Code] = array();
									$inheritedItems[$code->Code][] = 'role '.$role->Title.' on group '.$parent->Title;
								}
							}
						}
					}
					if ($parent->Permissions()->Count()) {
						foreach($parent->Permissions() as $permission) {
							if (!isset($inheritedItems[$permission->Code])) $inheritedItems[$permission->Code] = array();
							$inheritedItems[$permission->Code][] = 'group '.$parent->Title;
						}
					}
				}
			}
		}
		
		if($source) {
			foreach($source as $categoryName => $permissions) {
				$options .= "<li><h5>$categoryName</h5></li>";
				foreach($permissions as $code => $permission) {
					if(in_array($code, $this->hiddenPermissions)) continue;
					$key = $code;
					$value = $permission['name'];
			
					$odd = ($odd + 1) % 2;
					$extraClass = $odd ? 'odd' : 'even';
					$extraClass .= ' val' . str_replace(' ', '', $key);
					$itemID = $this->id() . '_' . ereg_replace('[^a-zA-Z0-9]+', '', $key);
					$checked = $disabled = $inheritMessage = '';
			
					$checked = in_array($key, $values) ? ' checked="checked"' : '';
			
					$title = $permission['help'] ? 'title="'.htmlentities($permission['help']).'" ' : '';
					
					if (isset($inheritedItems[$code])) {
						$disabled = ' disabled="true"';
						$inheritMessage = ' inherited from '.join(', ', $inheritedItems[$code]).'';
						$options .= "<li class=\"$extraClass\"><label {$title}for=\"$itemID\">$value is $inheritMessage</label></li>\n"; 
					} else {
						$options .= "<li class=\"$extraClass\"><input id=\"$itemID\"$disabled name=\"$this->name[$key]\" type=\"checkbox\" value=\"$key\"$checked class=\"checkbox\" /> <label {$title}for=\"$itemID\">$value$inheritMessage</label></li>\n"; 
					}
				}
			}
		}
		
		return "<ul id=\"{$this->id()}\" class=\"optionset checkboxsetfield{$this->extraClass()}\">\n$options</ul>\n"; 
	}
	
	/**
	 * Update the permission set associated with $record DataObject
	 *
	 * @param DataObject $record
	 */
	function saveInto(DataObject $record) {
		$fieldname = $this->name;
		$managedClass = $this->managedClass;

		// remove all permissions and re-add them afterwards
		$permissions = $record->$fieldname();
		foreach ( $permissions as $permission ) {
			$permission->delete();
		}
		
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