<?php
/**
 * Shows a categorized list of available permissions (through {@link Permission::get_codes()}).
 * Permissions which are assigned to a given {@link Group} record
 * (either directly, inherited from parent groups, or through a {@link PermissionRole})
 * will be checked automatically. All checkboxes for "inherited" permissions will be readonly.
 * 
 * @package sapphire
 * @subpackage security
 */
class PermissionCheckboxSetField extends FormField {
	
	/**
	 * @var Array Filter certain permission codes from the output.
	 * Useful to simplify the interface
	 */
	protected $hiddenPermissions = array();
	
	/**
	 * @var DataObjectSet
	 */
	protected $groups = null;
	
	/**
	 * @var array Array Nested array in same notation as {@link CheckboxSetField}.
	 */
	protected $source = null;
	
	/**
	 * @param String $name
	 * @param String $title
	 * @param String $managedClass
	 * @param String $filterField
	 * @param Group|DataObjectSet $groups One or more {@link Group} records used to determine permission checkboxes.
	 *  Caution: saveInto() can only be used with a single group, all inherited permissions will be marked readonly.
	 *  Setting multiple groups only makes sense in a readonly context. (Optional)
	 */
	function __construct($name, $title, $managedClass, $filterField, $groups = null) {
		$this->filterField = $filterField;
		$this->managedClass = $managedClass;

		if(is_a($groups, 'DataObjectSet')) {
			$this->groups = $groups;
		} elseif(is_a($groups, 'Group')) {
			$this->groups = new DataObjectSet($groups);
		} elseif($groups) {
			throw new InvalidArgumentException('$group should be either a Group record, or a DataObjectSet of Group records');
		}
		
		// Get all available codes in the system as a categorized nested array
		$this->source = Permission::get_codes(true);
		
		parent::__construct($name, $title);
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
		
		// Get existing values from the form record (assuming the formfield name is a join field on the record)
		$uninheritedCodes = array();
		if(is_object($this->form)) {
			$record = $this->form->getRecord();
			if ($record && $record->hasMethod($this->name)) {
				$funcName = $this->name;
				$join = $record->$funcName();
				if($join) foreach($join as $joinItem) {
					$uninheritedCodes[$joinItem->Code] = $joinItem->Code;
				}
			}
		}

		// Get all 'inherited' codes not directly assigned to the group (which is stored in $values)
		$inheritedCodes = array();
		if($this->groups) foreach($this->groups as $group) {
			// Get all uninherited permissions
			foreach($group->Permissions() as $permission) {
				$uninheritedCodes[$permission->Code] = $permission->Code;
			}
			
			// Get all permissions from roles
			if ($group->Roles()->Count()) {
				foreach($group->Roles() as $role) {
					foreach($role->Codes() as $code) {
						if (!isset($inheritedCodes[$code->Code])) $inheritedCodes[$code->Code] = array();
						// TODO i18n
						$inheritedCodes[$code->Code][] = 'from role '.$role->Title;
					}
				}
			}

			// Get from parent groups
			$parentGroups = $group->getAncestors();
			if ($parentGroups) {
				foreach ($parentGroups as $parent) {
					if (!$parent->Roles()->Count()) continue;
					foreach($parent->Roles() as $role) {
						if ($role->Codes()) {
							foreach($role->Codes() as $code) {
								if (!isset($inheritedCodes[$code->Code])) $inheritedCodes[$code->Code] = array();
								// TODO i18n
								$inheritedCodes[$code->Code][] = 'role '.$role->Title.' on group '.$parent->Title;
							}
						}
					}
					if ($parent->Permissions()->Count()) {
						foreach($parent->Permissions() as $permission) {
							if (!isset($inheritedCodes[$permission->Code])) $inheritedCodes[$permission->Code] = array();
							// TODO i18n
							$inheritedCodes[$permission->Code][] = 'group '.$parent->Title;
						}
					}
				}
			}
		}
		 
		$odd = 0;
		$options = '';
		if($this->source) {
			// loop through all available categorized permissions and see if they're assigned for the given groups
			foreach($this->source as $categoryName => $permissions) {
				$options .= "<li><h5>$categoryName</h5></li>";
				foreach($permissions as $code => $permission) {
					if(in_array($code, $this->hiddenPermissions)) continue;
					
					$value = $permission['name'];
			
					$odd = ($odd + 1) % 2;
					$extraClass = $odd ? 'odd' : 'even';
					$extraClass .= ' val' . str_replace(' ', '', $code);
					$itemID = $this->id() . '_' . ereg_replace('[^a-zA-Z0-9]+', '', $code);
					$checked = $disabled = $inheritMessage = '';
					$checked = in_array($code, $uninheritedCodes) ? ' checked="checked"' : '';
					$title = $permission['help'] ? 'title="' . htmlentities($permission['help']) . '" ' : '';
					
					if (isset($inheritedCodes[$code])) {
						// disable inherited codes, as any saving logic would be too complicate to express in this interface
						$disabled = ' disabled="true"';
						// TODO i18n
						$inheritMessage = ' inherited from ' . join(', ', $inheritedCodes[$code]) . '';
						$options .= "<li class=\"$extraClass\"><label {$title}for=\"$itemID\">$value is $inheritMessage</label></li>\n"; 
					} else {
						// uninherited (and hence editable) code checkbox
						$options .= "<li class=\"$extraClass\"><input id=\"$itemID\"$disabled name=\"$this->name[$code]\" type=\"checkbox\" value=\"$code\"$checked class=\"checkbox\" /> <label {$title}for=\"$itemID\">$value$inheritMessage</label></li>\n"; 
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