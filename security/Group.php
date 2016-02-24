<?php
/**
 * A security group.
 *
 * @package framework
 * @subpackage security
 *
 * @property string Title Name of the group
 * @property string Description Description of the group
 * @property string Code Group code
 * @property string Locked Boolean indicating whether group is locked in security panel
 * @property int Sort
 * @property string HtmlEditorConfig
 *
 * @property int ParentID ID of parent group
 *
 * @method Group Parent() Return parent group
 * @method HasManyList Permissions() List of group permissions
 * @method HasManyList Groups() List of child groups
 * @method ManyManyList Roles() List of PermissionRoles
 */
class Group extends DataObject {

	private static $db = array(
		"Title" => "Varchar(255)",
		"Description" => "Text",
		"Code" => "Varchar(255)",
		"Locked" => "Boolean",
		"Sort" => "Int",
		"HtmlEditorConfig" => "Text"
	);

	private static $has_one = array(
		"Parent" => "Group",
	);

	private static $has_many = array(
		"Permissions" => "Permission",
		"Groups" => "Group"
	);

	private static $many_many = array(
		"Members" => "Member",
		"Roles" => "PermissionRole",
	);

	private static $extensions = array(
		"Hierarchy",
	);

	public function populateDefaults() {
		parent::populateDefaults();

		if(!$this->Title) $this->Title = _t('SecurityAdmin.NEWGROUP',"New Group");
	}

	public function getAllChildren() {
		$doSet = new ArrayList();

		$children = DataObject::get('Group')->filter("ParentID", $this->ID);
		foreach($children as $child) {
			$doSet->push($child);
			$doSet->merge($child->getAllChildren());
		}

		return $doSet;
	}

	/**
	 * Caution: Only call on instances, not through a singleton.
	 * The "root group" fields will be created through {@link SecurityAdmin->EditForm()}.
	 *
	 * @return FieldList
	 */
	public function getCMSFields() {
		Requirements::javascript(FRAMEWORK_DIR . '/javascript/PermissionCheckboxSetField.js');

		$fields = new FieldList(
			new TabSet("Root",
				new Tab('Members', _t('SecurityAdmin.MEMBERS', 'Members'),
					new TextField("Title", $this->fieldLabel('Title')),
					$parentidfield = DropdownField::create(						'ParentID',
						$this->fieldLabel('Parent'),
						Group::get()->exclude('ID', $this->ID)->map('ID', 'Breadcrumbs')
					)->setEmptyString(' '),
					new TextareaField('Description', $this->fieldLabel('Description'))
				),

				$permissionsTab = new Tab('Permissions', _t('SecurityAdmin.PERMISSIONS', 'Permissions'),
					$permissionsField = new PermissionCheckboxSetField(
						'Permissions',
						false,
						'Permission',
						'GroupID',
						$this
					)
				)
			)
		);

		$parentidfield->setDescription(
			_t('Group.GroupReminder', 'If you choose a parent group, this group will take all it\'s roles')
		);

		// Filter permissions
		// TODO SecurityAdmin coupling, not easy to get to the form fields through GridFieldDetailForm
		$permissionsField->setHiddenPermissions((array)Config::inst()->get('SecurityAdmin', 'hidden_permissions'));

		if($this->ID) {
			$group = $this;
			$config = GridFieldConfig_RelationEditor::create();
			$config->addComponent(new GridFieldButtonRow('after'));
			$config->addComponents(new GridFieldExportButton('buttons-after-left'));
			$config->addComponents(new GridFieldPrintButton('buttons-after-left'));
			$config->getComponentByType('GridFieldAddExistingAutocompleter')
				->setResultsFormat('$Title ($Email)')->setSearchFields(array('FirstName', 'Surname', 'Email'));
			$config->getComponentByType('GridFieldDetailForm')
				->setValidator(Member_Validator::create())
				->setItemEditFormCallback(function($form, $component) use($group) {
					$record = $form->getRecord();
					$groupsField = $form->Fields()->dataFieldByName('DirectGroups');
					if($groupsField) {
						// If new records are created in a group context,
						// set this group by default.
						if($record && !$record->ID) {
							$groupsField->setValue($group->ID);
						} elseif($record && $record->ID) {
							// TODO Mark disabled once chosen.js supports it
							// $groupsField->setDisabledItems(array($group->ID));
							$form->Fields()->replaceField('DirectGroups',
								$groupsField->performReadonlyTransformation());
						}
					}
				});
			$memberList = GridField::create('Members',false, $this->DirectMembers(), $config)
				->addExtraClass('members_grid');
			// @todo Implement permission checking on GridField
			//$memberList->setPermissions(array('edit', 'delete', 'export', 'add', 'inlineadd'));
			$fields->addFieldToTab('Root.Members', $memberList);
		}

		// Only add a dropdown for HTML editor configurations if more than one is available.
		// Otherwise Member->getHtmlEditorConfigForCMS() will default to the 'cms' configuration.
		$editorConfigMap = HtmlEditorConfig::get_available_configs_map();
		if(count($editorConfigMap) > 1) {
			$fields->addFieldToTab('Root.Permissions',
				new DropdownField(
					'HtmlEditorConfig',
					'HTML Editor Configuration',
					$editorConfigMap
				),
				'Permissions'
			);
		}

		if(!Permission::check('EDIT_PERMISSIONS')) {
			$fields->removeFieldFromTab('Root', 'Permissions');
		}

		// Only show the "Roles" tab if permissions are granted to edit them,
		// and at least one role exists
		if(Permission::check('APPLY_ROLES') && DataObject::get('PermissionRole')) {
			$fields->findOrMakeTab('Root.Roles', _t('SecurityAdmin.ROLES', 'Roles'));
			$fields->addFieldToTab('Root.Roles',
				new LiteralField(
					"",
					"<p>" .
					_t(
						'SecurityAdmin.ROLESDESCRIPTION',
						"Roles are predefined sets of permissions, and can be assigned to groups.<br />"
						. "They are inherited from parent groups if required."
					) . '<br />' .
					sprintf(
						'<a href="%s" class="add-role">%s</a>',
						singleton('SecurityAdmin')->Link('show/root#Root_Roles'),
						// TODO This should include #Root_Roles to switch directly to the tab,
						// but tabstrip.js doesn't display tabs when directly adressed through a URL pragma
						_t('Group.RolesAddEditLink', 'Manage roles')
					) .
					"</p>"
				)
			);

			// Add roles (and disable all checkboxes for inherited roles)
			$allRoles = PermissionRole::get();
			if(!Permission::check('ADMIN')) {
				$allRoles = $allRoles->filter("OnlyAdminCanApply", 0);
			}
			if($this->ID) {
				$groupRoles = $this->Roles();
				$inheritedRoles = new ArrayList();
				$ancestors = $this->getAncestors();
				foreach($ancestors as $ancestor) {
					$ancestorRoles = $ancestor->Roles();
					if($ancestorRoles) $inheritedRoles->merge($ancestorRoles);
				}
				$groupRoleIDs = $groupRoles->column('ID') + $inheritedRoles->column('ID');
				$inheritedRoleIDs = $inheritedRoles->column('ID');
			} else {
				$groupRoleIDs = array();
				$inheritedRoleIDs = array();
			}

			$rolesField = ListboxField::create('Roles', false, $allRoles->map()->toArray())
					->setMultiple(true)
					->setDefaultItems($groupRoleIDs)
					->setAttribute('data-placeholder', _t('Group.AddRole', 'Add a role for this group'))
					->setDisabledItems($inheritedRoleIDs);
			if(!$allRoles->Count()) {
				$rolesField->setAttribute('data-placeholder', _t('Group.NoRoles', 'No roles found'));
			}
			$fields->addFieldToTab('Root.Roles', $rolesField);
		}

		$fields->push($idField = new HiddenField("ID"));

		$this->extend('updateCMSFields', $fields);

		return $fields;
	}

	/**
	 *
	 * @param boolean $includerelations a boolean value to indicate if the labels returned include relation fields
	 *
	 */
	public function fieldLabels($includerelations = true) {
		$labels = parent::fieldLabels($includerelations);
		$labels['Title'] = _t('SecurityAdmin.GROUPNAME', 'Group name');
		$labels['Description'] = _t('Group.Description', 'Description');
		$labels['Code'] = _t('Group.Code', 'Group Code', 'Programmatical code identifying a group');
		$labels['Locked'] = _t('Group.Locked', 'Locked?', 'Group is locked in the security administration area');
		$labels['Sort'] = _t('Group.Sort', 'Sort Order');
		if($includerelations){
			$labels['Parent'] = _t('Group.Parent', 'Parent Group', 'One group has one parent group');
			$labels['Permissions'] = _t('Group.has_many_Permissions', 'Permissions', 'One group has many permissions');
			$labels['Members'] = _t('Group.many_many_Members', 'Members', 'One group has many members');
		}

		return $labels;
	}

	/**
	 * Get many-many relation to {@link Member},
	 * including all members which are "inherited" from children groups of this record.
	 * See {@link DirectMembers()} for retrieving members without any inheritance.
	 *
	 * @param string $filter
	 * @return ManyManyList
	 */
	public function Members($filter = "", $sort = "", $join = "", $limit = "") {
		if($sort || $join || $limit) {
			Deprecation::notice('4.0',
				"The sort, join, and limit arguments are deprecated, use sort(), join() and limit() on the resulting"
				. " DataList instead.");
		}

		if($join) {
			throw new \InvalidArgumentException(
				'The $join argument has been removed. Use leftJoin($table, $joinClause) instead.'
			);
		}

		// First get direct members as a base result
		$result = $this->DirectMembers();

		// Unsaved group cannot have child groups because its ID is still 0.
		if(!$this->exists()) return $result;

		// Remove the default foreign key filter in prep for re-applying a filter containing all children groups.
		// Filters are conjunctive in DataQuery by default, so this filter would otherwise overrule any less specific
		// ones.
		if(!($result instanceof UnsavedRelationList)) {
			$result = $result->alterDataQuery(function($query){
				$query->removeFilterOn('Group_Members');
			});
		}
		// Now set all children groups as a new foreign key
		$groups = Group::get()->byIDs($this->collateFamilyIDs());
		$result = $result->forForeignID($groups->column('ID'))->where($filter)->sort($sort)->limit($limit);

		return $result;
	}

	/**
	 * Return only the members directly added to this group
	 */
	public function DirectMembers() {
		return $this->getManyManyComponents('Members');
	}

	/**
	 * Return a set of this record's "family" of IDs - the IDs of
	 * this record and all its descendants.
	 *
	 * @return array
	 */
	public function collateFamilyIDs() {
		if (!$this->exists()) {
			throw new \InvalidArgumentException("Cannot call collateFamilyIDs on unsaved Group.");
		}

		$familyIDs = array();
		$chunkToAdd = array($this->ID);

		while($chunkToAdd) {
			$familyIDs = array_merge($familyIDs,$chunkToAdd);

			// Get the children of *all* the groups identified in the previous chunk.
			// This minimises the number of SQL queries necessary
			$chunkToAdd = Group::get()->filter("ParentID", $chunkToAdd)->column('ID');
		}

		return $familyIDs;
	}

	/**
	 * Returns an array of the IDs of this group and all its parents
	 */
	public function collateAncestorIDs() {
		$parent = $this;
		while(isset($parent) && $parent instanceof Group) {
			$items[] = $parent->ID;
			$parent = $parent->Parent;
		}
		return $items;
	}

	/**
	 * This isn't a decendant of SiteTree, but needs this in case
	 * the group is "reorganised";
	 */
	public function cmsCleanup_parentChanged() {
	}

	/**
	 * Override this so groups are ordered in the CMS
	 */
	public function stageChildren() {
		return Group::get()
			->filter("ParentID", $this->ID)
			->exclude("ID", $this->ID)
			->sort('"Sort"');
	}

	public function getTreeTitle() {
		if($this->hasMethod('alternateTreeTitle')) return $this->alternateTreeTitle();
		else return htmlspecialchars($this->Title, ENT_QUOTES);
	}

	/**
	 * Overloaded to ensure the code is always descent.
	 *
	 * @param string
	 */
	public function setCode($val){
		$this->setField("Code", Convert::raw2url($val));
	}

	protected function validate() {
		$result = parent::validate();

		// Check if the new group hierarchy would add certain "privileged permissions",
		// and require an admin to perform this change in case it does.
		// This prevents "sub-admin" users with group editing permissions to increase their privileges.
		if($this->Parent()->exists() && !Permission::check('ADMIN')) {
			$inheritedCodes = Permission::get()
				->filter('GroupID', $this->Parent()->collateAncestorIDs())
				->column('Code');
			$privilegedCodes = Config::inst()->get('Permission', 'privileged_permissions');
			if(array_intersect($inheritedCodes, $privilegedCodes)) {
				$result->error(sprintf(
					_t(
						'Group.HierarchyPermsError',
						'Can\'t assign parent group "%s" with privileged permissions (requires ADMIN access)'
					),
					$this->Parent()->Title
				));
			}
		}

		return $result;
	}

	public function onBeforeWrite() {
		parent::onBeforeWrite();

		// Only set code property when the group has a custom title, and no code exists.
		// The "Code" attribute is usually treated as a more permanent identifier than database IDs
		// in custom application logic, so can't be changed after its first set.
		if(!$this->Code && $this->Title != _t('SecurityAdmin.NEWGROUP',"New Group")) {
			if(!$this->Code) $this->setCode($this->Title);
		}
	}

	public function onBeforeDelete() {
		parent::onBeforeDelete();

		// if deleting this group, delete it's children as well
		foreach($this->Groups() as $group) {
			$group->delete();
		}

		// Delete associated permissions
		foreach($this->Permissions() as $permission) {
			$permission->delete();
		}
	}

	/**
	 * Checks for permission-code CMS_ACCESS_SecurityAdmin.
	 * If the group has ADMIN permissions, it requires the user to have ADMIN permissions as well.
	 *
	 * @param $member Member
	 * @return boolean
	 */
	public function canEdit($member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) $member = Member::currentUser();

		// extended access checks
		$results = $this->extend('canEdit', $member);
		if($results && is_array($results)) if(!min($results)) return false;

		if(
			// either we have an ADMIN
			(bool)Permission::checkMember($member, "ADMIN")
			|| (
				// or a privileged CMS user and a group without ADMIN permissions.
				// without this check, a user would be able to add himself to an administrators group
				// with just access to the "Security" admin interface
				Permission::checkMember($member, "CMS_ACCESS_SecurityAdmin") &&
				!Permission::get()->filter(array('GroupID' => $this->ID, 'Code' => 'ADMIN'))->exists()
			)
		) {
			return true;
		}

		return false;
	}

	/**
	 * Checks for permission-code CMS_ACCESS_SecurityAdmin.
	 *
	 * @param $member Member
	 * @return boolean
	 */
	public function canView($member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) $member = Member::currentUser();

		// extended access checks
		$results = $this->extend('canView', $member);
		if($results && is_array($results)) if(!min($results)) return false;

		// user needs access to CMS_ACCESS_SecurityAdmin
		if(Permission::checkMember($member, "CMS_ACCESS_SecurityAdmin")) return true;

		return false;
	}

	public function canDelete($member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) $member = Member::currentUser();

		// extended access checks
		$results = $this->extend('canDelete', $member);
		if($results && is_array($results)) if(!min($results)) return false;

		return $this->canEdit($member);
	}

	/**
	 * Returns all of the children for the CMS Tree.
	 * Filters to only those groups that the current user can edit
	 */
	public function AllChildrenIncludingDeleted() {
		$extInstance = $this->getExtensionInstance('Hierarchy');
		$extInstance->setOwner($this);
		$children = $extInstance->AllChildrenIncludingDeleted();
		$extInstance->clearOwner();

		$filteredChildren = new ArrayList();

		if($children) foreach($children as $child) {
			if($child->canView()) $filteredChildren->push($child);
		}

		return $filteredChildren;
	}

	/**
	 * Add default records to database.
	 *
	 * This function is called whenever the database is built, after the
	 * database tables have all been created.
	 */
	public function requireDefaultRecords() {
		parent::requireDefaultRecords();

		// Add default author group if no other group exists
		$allGroups = DataObject::get('Group');
		if(!$allGroups->count()) {
			$authorGroup = new Group();
			$authorGroup->Code = 'content-authors';
			$authorGroup->Title = _t('Group.DefaultGroupTitleContentAuthors', 'Content Authors');
			$authorGroup->Sort = 1;
			$authorGroup->write();
			Permission::grant($authorGroup->ID, 'CMS_ACCESS_CMSMain');
			Permission::grant($authorGroup->ID, 'CMS_ACCESS_AssetAdmin');
			Permission::grant($authorGroup->ID, 'CMS_ACCESS_ReportAdmin');
			Permission::grant($authorGroup->ID, 'SITETREE_REORGANISE');
		}

		// Add default admin group if none with permission code ADMIN exists
		$adminGroups = Permission::get_groups_by_permission('ADMIN');
		if(!$adminGroups->count()) {
			$adminGroup = new Group();
			$adminGroup->Code = 'administrators';
			$adminGroup->Title = _t('Group.DefaultGroupTitleAdministrators', 'Administrators');
			$adminGroup->Sort = 0;
			$adminGroup->write();
			Permission::grant($adminGroup->ID, 'ADMIN');
		}

		// Members are populated through Member->requireDefaultRecords()
	}

}
