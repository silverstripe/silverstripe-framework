<?php

namespace SilverStripe\Security;

use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\CompositeValidator;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldGroupDeleteAction;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\UnsavedRelationList;

/**
 * A security group.
 *
 * @property string $Title Name of the group
 * @property string $Description Description of the group
 * @property string $Code Group code
 * @property string $Locked Boolean indicating whether group is locked in security panel
 * @property int $Sort
 * @property string HtmlEditorConfig
 *
 * @property int $ParentID ID of parent group
 *
 * @mixin Hierarchy
 * @method HasManyList<Group> Groups()
 * @method Group Parent()
 * @method HasManyList<Permission> Permissions()
 * @method ManyManyList<PermissionRole> Roles()
 */
class Group extends DataObject
{

    private static $db = [
        "Title" => "Varchar(255)",
        "Description" => "Text",
        "Code" => "Varchar(255)",
        "Locked" => "Boolean",
        "Sort" => "Int",
        "HtmlEditorConfig" => "Text"
    ];

    private static $has_one = [
        "Parent" => Group::class,
    ];

    private static $has_many = [
        "Permissions" => Permission::class,
        "Groups" => Group::class,
    ];

    private static $many_many = [
        "Members" => Member::class,
        "Roles" => PermissionRole::class,
    ];

    private static $extensions = [
        Hierarchy::class,
    ];

    private static $table_name = "Group";

    private static $indexes = [
        'Title' => true,
        'Code' => true,
        'Sort' => true,
    ];

    public function getAllChildren()
    {
        $doSet = new ArrayList();

        $children = Group::get()->filter("ParentID", $this->ID);
        foreach ($children as $child) {
            $doSet->push($child);
            $doSet->merge($child->getAllChildren());
        }

        return $doSet;
    }

    private function getDecodedBreadcrumbs()
    {
        $list = Group::get()->exclude('ID', $this->ID);
        $groups = ArrayList::create();
        foreach ($list as $group) {
            $groups->push(['ID' => $group->ID, 'Title' => $group->getBreadcrumbs(' » ')]);
        }
        return $groups;
    }

    /**
     * Caution: Only call on instances, not through a singleton.
     * The "root group" fields will be created through {@link SecurityAdmin->EditForm()}.
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = new FieldList(
            new TabSet(
                "Root",
                new Tab(
                    'Members',
                    _t(__CLASS__ . '.MEMBERS', 'Members'),
                    new TextField("Title", $this->fieldLabel('Title')),
                    $parentidfield = DropdownField::create(
                        'ParentID',
                        $this->fieldLabel('Parent'),
                        $this->getDecodedBreadcrumbs()
                    )->setEmptyString(' '),
                    new TextareaField('Description', $this->fieldLabel('Description'))
                ),
                $permissionsTab = new Tab(
                    'Permissions',
                    _t(__CLASS__ . '.PERMISSIONS', 'Permissions'),
                    $permissionsField = new PermissionCheckboxSetField(
                        'Permissions',
                        false,
                        Permission::class,
                        'GroupID',
                        $this
                    )
                )
            )
        );

        $parentidfield->setDescription(
            _t('SilverStripe\\Security\\Group.GroupReminder', 'If you choose a parent group, this group will take all it\'s roles')
        );

        if ($this->ID) {
            $group = $this;
            $config = GridFieldConfig_RelationEditor::create();
            $config->addComponent(GridFieldButtonRow::create('after'));
            $config->addComponents(GridFieldExportButton::create('buttons-after-left'));
            $config->addComponents(GridFieldPrintButton::create('buttons-after-left'));
            $config->removeComponentsByType(GridFieldDeleteAction::class);
            $config->addComponent(GridFieldGroupDeleteAction::create($this->ID), GridFieldPageCount::class);

            $autocompleter = $config->getComponentByType(GridFieldAddExistingAutocompleter::class);
            $autocompleter
                ->setResultsFormat('$Title ($Email)')
                ->setSearchFields(['FirstName', 'Surname', 'Email']);
            $detailForm = $config->getComponentByType(GridFieldDetailForm::class);
            $detailForm
                ->setItemEditFormCallback(function ($form) use ($group) {
                    /** @var Form $form */
                    $record = $form->getRecord();
                    $form->setValidator($record->getValidator());
                    $groupsField = $form->Fields()->dataFieldByName('DirectGroups');
                    if ($groupsField) {
                        // If new records are created in a group context,
                        // set this group by default.
                        if ($record && !$record->ID) {
                            $groupsField->setValue($group->ID);
                        } elseif ($record && $record->ID) {
                            $form->Fields()->replaceField(
                                'DirectGroups',
                                $groupsField->performReadonlyTransformation()
                            );
                        }
                    }
                });
            $memberList = GridField::create('Members', false, $this->DirectMembers(), $config)
                ->addExtraClass('members_grid');
            $fields->addFieldToTab('Root.Members', $memberList);
        }

        // Only add a dropdown for HTML editor configurations if more than one is available.
        // Otherwise Member->getHtmlEditorConfigForCMS() will default to the 'cms' configuration.
        $editorConfigMap = HTMLEditorConfig::get_available_configs_map();
        if (count($editorConfigMap ?? []) > 1) {
            $fields->addFieldToTab(
                'Root.Permissions',
                new DropdownField(
                    'HtmlEditorConfig',
                    'HTML Editor Configuration',
                    $editorConfigMap
                ),
                'Permissions'
            );
        }

        if (!Permission::check('EDIT_PERMISSIONS')) {
            $fields->removeFieldFromTab('Root', 'Permissions');
        }

        // Only show the "Roles" tab if permissions are granted to edit them,
        // and at least one role exists
        if (Permission::check('APPLY_ROLES') &&
            PermissionRole::get()->count() &&
            class_exists(SecurityAdmin::class)
        ) {
            $fields->findOrMakeTab('Root.Roles', _t(__CLASS__ . '.ROLES', 'Roles'));
            $fields->addFieldToTab(
                'Root.Roles',
                new LiteralField(
                    "",
                    "<p>" .
                    _t(
                        __CLASS__ . '.ROLESDESCRIPTION',
                        "Roles are predefined sets of permissions, and can be assigned to groups.<br />"
                        . "They are inherited from parent groups if required."
                    ) . '<br />' .
                    sprintf(
                        '<a href="%s" class="add-role">%s</a>',
                        SecurityAdmin::singleton()->Link('roles'),
                        _t(__CLASS__ . '.RolesAddEditLink', 'Manage roles')
                    ) .
                    "</p>"
                )
            );

            // Add roles (and disable all checkboxes for inherited roles)
            $allRoles = PermissionRole::get();
            if (!Permission::check('ADMIN')) {
                $allRoles = $allRoles->filter("OnlyAdminCanApply", 0);
            }
            if ($this->ID) {
                $groupRoles = $this->Roles();
                $inheritedRoles = new ArrayList();
                $ancestors = $this->getAncestors();
                foreach ($ancestors as $ancestor) {
                    $ancestorRoles = $ancestor->Roles();
                    if ($ancestorRoles) {
                        $inheritedRoles->merge($ancestorRoles);
                    }
                }
                $groupRoleIDs = $groupRoles->column('ID') + $inheritedRoles->column('ID');
                $inheritedRoleIDs = $inheritedRoles->column('ID');
            } else {
                $groupRoleIDs = [];
                $inheritedRoleIDs = [];
            }

            $rolesField = ListboxField::create('Roles', false, $allRoles->map()->toArray())
                    ->setDefaultItems($groupRoleIDs)
                    ->setAttribute('data-placeholder', _t('SilverStripe\\Security\\Group.AddRole', 'Add a role for this group'))
                    ->setDisabledItems($inheritedRoleIDs);
            if (!$allRoles->count()) {
                $rolesField->setAttribute('data-placeholder', _t('SilverStripe\\Security\\Group.NoRoles', 'No roles found'));
            }
            $fields->addFieldToTab('Root.Roles', $rolesField);
        }

        $fields->push($idField = new HiddenField("ID"));

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    /**
     * @param bool $includerelations Indicate if the labels returned include relation fields
     * @return array
     */
    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);
        $labels['Title'] = _t(__CLASS__ . '.GROUPNAME', 'Group name');
        $labels['Description'] = _t('SilverStripe\\Security\\Group.Description', 'Description');
        $labels['Code'] = _t('SilverStripe\\Security\\Group.Code', 'Group Code', 'Programmatical code identifying a group');
        $labels['Locked'] = _t('SilverStripe\\Security\\Group.Locked', 'Locked?', 'Group is locked in the security administration area');
        $labels['Sort'] = _t('SilverStripe\\Security\\Group.Sort', 'Sort Order');
        if ($includerelations) {
            $labels['Parent'] = _t('SilverStripe\\Security\\Group.Parent', 'Parent Group', 'One group has one parent group');
            $labels['Permissions'] = _t('SilverStripe\\Security\\Group.has_many_Permissions', 'Permissions', 'One group has many permissions');
            $labels['Members'] = _t('SilverStripe\\Security\\Group.many_many_Members', 'Members', 'One group has many members');
        }

        return $labels;
    }

    /**
     * Get many-many relation to {@link Member},
     * including all members which are "inherited" from children groups of this record.
     * See {@link DirectMembers()} for retrieving members without any inheritance.
     *
     * @param string $filter
     * @return ManyManyList<Member>
     */
    public function Members($filter = '')
    {
        // First get direct members as a base result
        $result = $this->DirectMembers();

        // Unsaved group cannot have child groups because its ID is still 0.
        if (!$this->exists()) {
            return $result;
        }

        // Remove the default foreign key filter in prep for re-applying a filter containing all children groups.
        // Filters are conjunctive in DataQuery by default, so this filter would otherwise overrule any less specific
        // ones.
        if (!($result instanceof UnsavedRelationList)) {
            $result = $result->alterDataQuery(function ($query) {
                /** @var DataQuery $query */
                $query->removeFilterOn('Group_Members');
            });
        }

        // Now set all children groups as a new foreign key
        $familyIDs = $this->collateFamilyIDs();
        $result = $result->forForeignID($familyIDs);

        return $result->where($filter);
    }

    /**
     * Return only the members directly added to this group
     * @return ManyManyList<Member>
     */
    public function DirectMembers()
    {
        return $this->getManyManyComponents('Members');
    }

    /**
     * Return a set of this record's "family" of IDs - the IDs of
     * this record and all its descendants.
     *
     * @return array
     */
    public function collateFamilyIDs()
    {
        if (!$this->exists()) {
            throw new \InvalidArgumentException("Cannot call collateFamilyIDs on unsaved Group.");
        }

        $familyIDs = [];
        $chunkToAdd = [$this->ID];

        while ($chunkToAdd) {
            $familyIDs = array_merge($familyIDs, $chunkToAdd);

            // Get the children of *all* the groups identified in the previous chunk.
            // This minimises the number of SQL queries necessary
            $chunkToAdd = Group::get()->filter("ParentID", $chunkToAdd)->column('ID');
        }

        return $familyIDs;
    }

    /**
     * Returns an array of the IDs of this group and all its parents
     *
     * @return array
     */
    public function collateAncestorIDs()
    {
        $parent = $this;
        $items = [];
        while ($parent instanceof Group) {
            $items[] = $parent->ID;
            $parent = $parent->getParent();
        }
        return $items;
    }

    /**
     * Check if the group is a child of the given group or any parent groups
     *
     * @param string|int|Group $group Group instance, Group Code or ID
     * @return bool Returns TRUE if the Group is a child of the given group, otherwise FALSE
     */
    public function inGroup($group)
    {
        return in_array($this->identifierToGroupID($group), $this->collateAncestorIDs() ?? []);
    }

    /**
     * Check if the group is a child of the given groups or any parent groups
     *
     * @param (string|int|Group)[] $groups
     * @param bool $requireAll set to TRUE if must be in ALL groups, or FALSE if must be in ANY
     * @return bool Returns TRUE if the Group is a child of any of the given groups, otherwise FALSE
     */
    public function inGroups($groups, $requireAll = false)
    {
        $ancestorIDs = $this->collateAncestorIDs();
        $candidateIDs = [];
        foreach ($groups as $group) {
            $groupID = $this->identifierToGroupID($group);
            if ($groupID) {
                $candidateIDs[] = $groupID;
            } elseif ($requireAll) {
                return false;
            }
        }
        if (empty($candidateIDs)) {
            return false;
        }
        $matches = array_intersect($candidateIDs ?? [], $ancestorIDs);
        if ($requireAll) {
            return count($candidateIDs ?? []) === count($matches ?? []);
        }
        return !empty($matches);
    }

    /**
     * Turn a string|int|Group into a GroupID
     *
     * @param string|int|Group $groupID Group instance, Group Code or ID
     * @return int|null the Group ID or NULL if not found
     */
    protected function identifierToGroupID($groupID)
    {
        if (is_numeric($groupID) && Group::get()->byID($groupID)) {
            return $groupID;
        } elseif (is_string($groupID) && $groupByCode = Group::get()->filter(['Code' => $groupID])->first()) {
            return $groupByCode->ID;
        } elseif ($groupID instanceof Group && $groupID->exists()) {
            return $groupID->ID;
        }
        return null;
    }

    /**
     * This isn't a descendant of SiteTree, but needs this in case
     * the group is "reorganised";
     */
    public function cmsCleanup_parentChanged()
    {
    }

    /**
     * Override this so groups are ordered in the CMS
     */
    public function stageChildren()
    {
        return Group::get()
            ->filter("ParentID", $this->ID)
            ->exclude("ID", $this->ID)
            ->sort('"Sort"');
    }

    /**
     * @return string
     */
    public function getTreeTitle()
    {
        $title = htmlspecialchars($this->Title ?? '', ENT_QUOTES);
        $this->extend('updateTreeTitle', $title);
        return $title;
    }

    /**
     * Overloaded to ensure the code is always descent.
     *
     * @param string $val
     */
    public function setCode($val)
    {
        $this->setField('Code', Convert::raw2url($val));
    }

    public function validate()
    {
        $result = parent::validate();

        // Check if the new group hierarchy would add certain "privileged permissions",
        // and require an admin to perform this change in case it does.
        // This prevents "sub-admin" users with group editing permissions to increase their privileges.
        if ($this->Parent()->exists() && !Permission::check('ADMIN')) {
            $inheritedCodes = Permission::get()
                ->filter('GroupID', $this->Parent()->collateAncestorIDs())
                ->column('Code');
            $privilegedCodes = Permission::config()->get('privileged_permissions');
            if (array_intersect($inheritedCodes ?? [], $privilegedCodes)) {
                $result->addError(
                    _t(
                        'SilverStripe\\Security\\Group.HierarchyPermsError',
                        'Can\'t assign parent group "{group}" with privileged permissions (requires ADMIN access)',
                        ['group' => $this->Parent()->Title]
                    )
                );
            }
        }

        $currentGroups = Group::get()
            ->filter('ID:not', $this->ID)
            ->map('Code', 'Title')
            ->toArray();

        if (in_array($this->Title, $currentGroups)) {
            $result->addError(
                _t(
                    'SilverStripe\\Security\\Group.ValidationIdentifierAlreadyExists',
                    'A Group ({group}) already exists with the same {identifier}',
                    ['group' => $this->Title, 'identifier' => 'Title']
                )
            );
        }

        return $result;
    }

    public function getCMSCompositeValidator(): CompositeValidator
    {
        $validator = parent::getCMSCompositeValidator();

        $validator->addValidator(RequiredFields::create([
            'Title'
        ]));

        return $validator;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Only set code property when the group has a custom title, and no code exists.
        // The "Code" attribute is usually treated as a more permanent identifier than database IDs
        // in custom application logic, so can't be changed after its first set.
        if (!$this->Code && $this->Title != _t(__CLASS__ . '.NEWGROUP', "New Group")) {
            $this->setCode($this->Title);
        }

        // Make sure the code for this group is unique.
        $this->dedupeCode();
    }

    public function onBeforeDelete()
    {
        parent::onBeforeDelete();

        // if deleting this group, delete it's children as well
        foreach ($this->Groups() as $group) {
            $group->delete();
        }

        // Delete associated permissions
        foreach ($this->Permissions() as $permission) {
            $permission->delete();
        }
    }

    /**
     * Checks for permission-code CMS_ACCESS_SecurityAdmin.
     * If the group has ADMIN permissions, it requires the user to have ADMIN permissions as well.
     *
     * @param Member $member Member
     * @return boolean
     */
    public function canEdit($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        // check for extensions, we do this first as they can overrule everything
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        if (// either we have an ADMIN
            (bool)Permission::checkMember($member, "ADMIN")
            || (
                // or a privileged CMS user and a group without ADMIN permissions.
                // without this check, a user would be able to add himself to an administrators group
                // with just access to the "Security" admin interface
                Permission::checkMember($member, "CMS_ACCESS_SecurityAdmin") &&
                !Permission::get()->filter(['GroupID' => $this->ID, 'Code' => 'ADMIN'])->exists()
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Checks for permission-code CMS_ACCESS_SecurityAdmin.
     *
     * @param Member $member
     * @return boolean
     */
    public function canView($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        // check for extensions, we do this first as they can overrule everything
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        // user needs access to CMS_ACCESS_SecurityAdmin
        if (Permission::checkMember($member, "CMS_ACCESS_SecurityAdmin")) {
            return true;
        }

        // if user can grant access for specific groups, they need to be able to see the groups
        if (Permission::checkMember($member, "SITETREE_GRANT_ACCESS")) {
            return true;
        }

        return false;
    }

    public function canDelete($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        // check for extensions, we do this first as they can overrule everything
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        return $this->canEdit($member);
    }

    /**
     * Returns all of the children for the CMS Tree.
     * Filters to only those groups that the current user can edit
     *
     * @return ArrayList<DataObject>
     */
    public function AllChildrenIncludingDeleted()
    {
        $children = parent::AllChildrenIncludingDeleted();

        $filteredChildren = new ArrayList();

        if ($children) {
            foreach ($children as $child) {
                /** @var DataObject $child */
                if ($child->canView()) {
                    $filteredChildren->push($child);
                }
            }
        }

        return $filteredChildren;
    }

    /**
     * Add default records to database.
     *
     * This function is called whenever the database is built, after the
     * database tables have all been created.
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        // Add default author group if no other group exists
        $allGroups = Group::get();
        if (!$allGroups->count()) {
            $authorGroup = new Group();
            $authorGroup->Code = 'content-authors';
            $authorGroup->Title = _t(__CLASS__ . '.DefaultGroupTitleContentAuthors', 'Content Authors');
            $authorGroup->Sort = 1;
            $authorGroup->write();
            Permission::grant($authorGroup->ID, 'CMS_ACCESS_CMSMain');
            Permission::grant($authorGroup->ID, 'CMS_ACCESS_AssetAdmin');
            Permission::grant($authorGroup->ID, 'CMS_ACCESS_ReportAdmin');
            Permission::grant($authorGroup->ID, 'SITETREE_REORGANISE');
        }

        // Add default admin group if none with permission code ADMIN exists
        $adminGroups = Permission::get_groups_by_permission('ADMIN');
        if (!$adminGroups->count()) {
            $adminGroup = new Group();
            $adminGroup->Code = 'administrators';
            $adminGroup->Title = _t(__CLASS__ . '.DefaultGroupTitleAdministrators', 'Administrators');
            $adminGroup->Sort = 0;
            $adminGroup->write();
            Permission::grant($adminGroup->ID, 'ADMIN');
        }

        // Members are populated through Member->requireDefaultRecords()
    }

    /**
     * Code needs to be unique as it is used to identify a specific group. Ensure no duplicate
     * codes are created.
     */
    private function dedupeCode(): void
    {
        $currentGroups = Group::get()
            ->exclude('ID', $this->ID)
            ->map('Code', 'Title')
            ->toArray();
        $code = $this->Code;
        $count = 2;
        while (isset($currentGroups[$code])) {
            $code = $this->Code . '-' . $count;
            $count++;
        }
        $this->setField('Code', $code);
    }
}
