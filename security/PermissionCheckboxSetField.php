<?php
/**
 * Shows a categorized list of available permissions (through {@link Permission::get_codes()}).
 * Permissions which are assigned to a given {@link Group} record
 * (either directly, inherited from parent groups, or through a {@link PermissionRole})
 * will be checked automatically. All checkboxes for "inherited" permissions will be readonly.
 * 
 * The field can gets its assignment data either from {@link Group} or {@link PermissionRole} records.
 * 
 * @package framework
 * @subpackage security
 */
class PermissionCheckboxSetField extends FormField {
	
	/**
	 * @var Array Filter certain permission codes from the output.
	 * Useful to simplify the interface
	 */
	protected $hiddenPermissions = array();
	
	/**
	 * @var SS_List
	 */
	protected $records = null;
	
	/**
	 * @var array Array Nested array in same notation as {@link CheckboxSetField}.
	 */
	protected $source = null;
	
	/**
	 * @param String $name
	 * @param String $title
	 * @param String $managedClass
	 * @param String $filterField
	 * @param Group|SS_List $records One or more {@link Group} or {@link PermissionRole} records 
	 *  used to determine permission checkboxes.
	 *  Caution: saveInto() can only be used with a single record, all inherited permissions will be marked readonly.
	 *  Setting multiple groups only makes sense in a readonly context. (Optional)
	 */
	public function __construct($name, $title, $managedClass, $filterField, $records = null) {
		$this->filterField = $filterField;
		$this->managedClass = $managedClass;

		if($records instanceof SS_List) {
			$this->records = $records;
		} elseif($records instanceof Group) {
			$this->records = new ArrayList(array($records));
		} elseif($records) {
			throw new InvalidArgumentException(
				'$record should be either a Group record, or a SS_List of Group records');
		}
		
		// Get all available codes in the system as a categorized nested array
		$this->source = Permission::get_codes(true);
		
		parent::__construct($name, $title);
	}
	
	/**
	 * @param Array $codes
	 */
	public function setHiddenPermissions($codes) {
		$this->hiddenPermissions = $codes;
	}
	
	/**
	 * @return Array
	 */
	public function getHiddenPermissions() {
		return $this->hiddenPermissions;
	}

	public function Field($properties = array()) {
		Requirements::css(FRAMEWORK_DIR . '/css/CheckboxSetField.css');
		Requirements::javascript(FRAMEWORK_DIR . '/javascript/PermissionCheckboxSetField.js');
		
		$uninheritedCodes = array();
		$inheritedCodes = array();
		$records = ($this->records) ? $this->records : new ArrayList();
		
		// Get existing values from the form record (assuming the formfield name is a join field on the record)
		if(is_object($this->form)) {
			$record = $this->form->getRecord();
			if(
				$record 
				&& (is_a($record, 'Group') || is_a($record, 'PermissionRole')) 
				&& !$records->find('ID', $record->ID)
			) {
				$records->push($record);
			}
		}

		// Get all 'inherited' codes not directly assigned to the group (which is stored in $values)
		foreach($records as $record) {
			// Get all uninherited permissions
			$relationMethod = $this->name;
			foreach($record->$relationMethod() as $permission) {
				if(!isset($uninheritedCodes[$permission->Code])) $uninheritedCodes[$permission->Code] = array();
				$uninheritedCodes[$permission->Code][] = _t(
					'PermissionCheckboxSetField.AssignedTo', 'assigned to "{title}"',
					array('title' => $record->Title)
				);
			}

			// Special case for Group records (not PermissionRole):
			// Determine inherited assignments
			if(is_a($record, 'Group')) {
				// Get all permissions from roles
				if ($record->Roles()->Count()) {
					foreach($record->Roles() as $role) {
						foreach($role->Codes() as $code) {
							if (!isset($inheritedCodes[$code->Code])) $inheritedCodes[$code->Code] = array();
							$inheritedCodes[$code->Code][] = _t(
								'PermissionCheckboxSetField.FromRole',
								'inherited from role "{title}"',
								'A permission inherited from a certain permission role',
								array('title' => $role->Title)
							);
						}
					}
				}

				// Get from parent groups
				$parentGroups = $record->getAncestors();
				if ($parentGroups) {
					foreach ($parentGroups as $parent) {
						if (!$parent->Roles()->Count()) continue;
						foreach($parent->Roles() as $role) {
							if ($role->Codes()) {
								foreach($role->Codes() as $code) {
									if (!isset($inheritedCodes[$code->Code])) $inheritedCodes[$code->Code] = array();
									$inheritedCodes[$code->Code][] = _t(
										'PermissionCheckboxSetField.FromRoleOnGroup',
										'inherited from role "%s" on group "%s"',
										'A permission inherited from a role on a certain group',
										array('roletitle' => $role->Title, 'grouptitle' => $parent->Title)
									);
								}
							}
						}
						if ($parent->Permissions()->Count()) {
							foreach($parent->Permissions() as $permission) {
								if (!isset($inheritedCodes[$permission->Code])) {
									$inheritedCodes[$permission->Code] = array();
								}
								$inheritedCodes[$permission->Code][] = 
								_t(
									'PermissionCheckboxSetField.FromGroup',
									'inherited from group "{title}"',
									'A permission inherited from a certain group',
									array('title' => $parent->Title)
								);
							}
						}
					}
				}
			}
		}
		
		$odd = 0;
		$options = '';
		$globalHidden = (array)Config::inst()->get('Permission', 'hidden_permissions');
		if($this->source) {
			$privilegedPermissions = Permission::config()->privileged_permissions;

			// loop through all available categorized permissions and see if they're assigned for the given groups
			foreach($this->source as $categoryName => $permissions) {
				$options .= "<li><h5>$categoryName</h5></li>";
				foreach($permissions as $code => $permission) {
					if(in_array($code, $this->hiddenPermissions)) continue;
					if(in_array($code, $globalHidden)) continue;
					
					$value = $permission['name'];
			
					$odd = ($odd + 1) % 2;
					$extraClass = $odd ? 'odd' : 'even';
					$extraClass .= ' val' . str_replace(' ', '', $code);
					$itemID = $this->id() . '_' . preg_replace('/[^a-zA-Z0-9]+/', '', $code);
					$checked = $disabled = $inheritMessage = '';
					$checked = (isset($uninheritedCodes[$code]) || isset($inheritedCodes[$code])) 
						? ' checked="checked"' 
						: '';
					$title = $permission['help'] 
						? 'title="' . htmlentities($permission['help'], ENT_COMPAT, 'UTF-8') . '" ' 
						: '';
					
					if (isset($inheritedCodes[$code])) {
						// disable inherited codes, as any saving logic would be too complicate to express in this
						// interface
						$disabled = ' disabled="true"';
						$inheritMessage = ' (' . join(', ', $inheritedCodes[$code]) . ')';
					} elseif($this->records && $this->records->Count() > 1 && isset($uninheritedCodes[$code])) {
						// If code assignments are collected from more than one "source group",
						// show its origin automatically
						$inheritMessage = ' (' . join(', ', $uninheritedCodes[$code]).')';
					}

					// Disallow modification of "privileged" permissions unless currently logged-in user is an admin
					if(!Permission::check('ADMIN') && in_array($code, $privilegedPermissions)) {
						$disabled = ' disabled="true"';
					}

					// If the field is readonly, always mark as "disabled"
					if($this->readonly) $disabled = ' disabled="true"';
					
					$inheritMessage = '<small>' . $inheritMessage . '</small>';
					$icon = ($checked) ? 'accept' : 'decline';

					// If the field is readonly, add a span that will replace the disabled checkbox input
					if($this->readonly) {
						$options .= "<li class=\"$extraClass\">"
							. "<input id=\"$itemID\"$disabled name=\"$this->name[$code]\" type=\"checkbox\""
							. " value=\"$code\"$checked class=\"checkbox\" />"
							. "<label {$title}for=\"$itemID\">"
							. "<span class=\"ui-button-icon-primary ui-icon btn-icon-$icon\"></span>"
							. "$value$inheritMessage</label>"
							. "</li>\n";
					} else {
						$options .= "<li class=\"$extraClass\">"
							. "<input id=\"$itemID\"$disabled name=\"$this->name[$code]\" type=\"checkbox\""
							. " value=\"$code\"$checked class=\"checkbox\" />"
							. "<label {$title}for=\"$itemID\">$value$inheritMessage</label>"
							. "</li>\n";
					}
				}
			}
		}
		if($this->readonly) {
			return "<ul id=\"{$this->id()}\" class=\"optionset checkboxsetfield{$this->extraClass()}\">\n" .
				"<li class=\"help\">" .
				_t(
					'Permissions.UserPermissionsIntro',
					'Assigning groups to this user will adjust the permissions they have.'
					. ' See the groups section for details of permissions on individual groups.'
				) .
				"</li>" .
				$options .
				"</ul>\n";
		} else {
			return "<ul id=\"{$this->id()}\" class=\"optionset checkboxsetfield{$this->extraClass()}\">\n" .
				$options . 
				"</ul>\n";
		}
	}
	
	/**
	 * Update the permission set associated with $record DataObject
	 *
	 * @param DataObject $record
	 */
	public function saveInto(DataObjectInterface $record) {
		$fieldname = $this->name;
		$managedClass = $this->managedClass;

		// Remove all "privileged" permissions if the currently logged-in user is not an admin
		$privilegedPermissions = Permission::config()->privileged_permissions;
		if(!Permission::check('ADMIN')) {
			foreach($this->value as $id => $bool) {
				if(in_array($id, $privilegedPermissions)) {
					unset($this->value[$id]);
				}
			}	
		}

		// remove all permissions and re-add them afterwards
		$permissions = $record->$fieldname();
		foreach ( $permissions as $permission ) {
			$permission->delete();
		}
		
		if($fieldname && $record && ($record->has_many($fieldname) || $record->many_many($fieldname))) {
			
			if(!$record->ID) $record->write(); // We need a record ID to write permissions
			
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
	
	/**
	 * @return PermissionCheckboxSetField_Readonly
	 */
	public function performReadonlyTransformation() {
		$readonly = new PermissionCheckboxSetField_Readonly(
			$this->name,
			$this->title,
			$this->managedClass,
			$this->filterField,
			$this->records
		);
		
		return $readonly;
	}
	
	/**
	 * Retrieves all permission codes for the currently set records
	 * 
	 * @return array
	 */
	public function getAssignedPermissionCodes() {
		if(!$this->records) return false;
		
		// TODO

		return $codes;
	}
}

/**
 * Readonly version of a {@link PermissionCheckboxSetField} - 
 * uses the same structure, but has all checkboxes disabled.
 * 
 * @package framework
 * @subpackage security
 */
class PermissionCheckboxSetField_Readonly extends PermissionCheckboxSetField {

	protected $readonly = true;
	
	public function saveInto(DataObjectInterface $record) {
		return false;
	}
}
