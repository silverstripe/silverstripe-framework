<?php

namespace SilverStripe\Security;

use InvalidArgumentException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\SS_List;
use Traversable;

/**
 * Shows a categorized list of available permissions (through {@link Permission::get_codes()}).
 * Permissions which are assigned to a given {@link Group} record
 * (either directly, inherited from parent groups, or through a {@link PermissionRole})
 * will be checked automatically. All checkboxes for "inherited" permissions will be readonly.
 *
 * The field can gets its assignment data either from {@link Group} or {@link PermissionRole} records.
 */
class PermissionCheckboxSetField extends FormField
{

    /**
     * @var array Filter certain permission codes from the output.
     * Useful to simplify the interface
     */
    protected $hiddenPermissions = [];

    /**
     * @var SS_List
     */
    protected $records = null;

    /**
     * @var array Array Nested array in same notation as {@link CheckboxSetField}.
     */
    protected $source = null;

    /**
     * @param string $name
     * @param string $title
     * @param string $managedClass
     * @param string $filterField
     * @param Group|SS_List $records One or more {@link Group} or {@link PermissionRole} records
     *  used to determine permission checkboxes.
     *  Caution: saveInto() can only be used with a single record, all inherited permissions will be marked readonly.
     *  Setting multiple groups only makes sense in a readonly context. (Optional)
     */
    public function __construct($name, $title, $managedClass, $filterField, $records = null)
    {
        $this->filterField = $filterField;
        $this->managedClass = $managedClass;

        if ($records instanceof SS_List) {
            $this->records = $records;
        } elseif ($records instanceof Group) {
            $this->records = new ArrayList([$records]);
        } elseif ($records) {
            throw new InvalidArgumentException(
                '$record should be either a Group record, or a SS_List of Group records'
            );
        }

        // Get all available codes in the system as a categorized nested array
        $this->source = Permission::get_codes(true);

        parent::__construct($name, $title);
    }

    /**
     * @param array $codes
     */
    public function setHiddenPermissions($codes)
    {
        $this->hiddenPermissions = $codes;
    }

    /**
     * @return array
     */
    public function getHiddenPermissions()
    {
        return $this->hiddenPermissions;
    }

    /**
     * @param array $properties
     * @return string
     */
    public function Field($properties = [])
    {
        $uninheritedCodes = [];
        $inheritedCodes = [];
        $records = ($this->records) ? $this->records : new ArrayList();

        // Get existing values from the form record (assuming the formfield name is a join field on the record)
        if (is_object($this->form)) {
            $record = $this->form->getRecord();
            if ($record
                && ($record instanceof Group || $record instanceof PermissionRole)
                && !$records->find('ID', $record->ID)
            ) {
                $records->push($record);
            }
        }

        // Get all 'inherited' codes not directly assigned to the group (which is stored in $values)
        foreach ($records as $record) {
            // Get all uninherited permissions
            $relationMethod = $this->name;
            foreach ($record->$relationMethod() as $permission) {
                if (!isset($uninheritedCodes[$permission->Code])) {
                    $uninheritedCodes[$permission->Code] = [];
                }
                $uninheritedCodes[$permission->Code][] = _t(
                    'SilverStripe\\Security\\PermissionCheckboxSetField.AssignedTo',
                    'assigned to "{title}"',
                    ['title' => $record->dbObject('Title')->forTemplate()]
                );
            }

            // Special case for Group records (not PermissionRole):
            // Determine inherited assignments
            if ($record instanceof Group) {
                // Get all permissions from roles
                if ($record->Roles()->count()) {
                    foreach ($record->Roles() as $role) {
                        foreach ($role->Codes() as $code) {
                            if (!isset($inheritedCodes[$code->Code])) {
                                $inheritedCodes[$code->Code] = [];
                            }
                            $inheritedCodes[$code->Code][] = _t(
                                'SilverStripe\\Security\\PermissionCheckboxSetField.FromRole',
                                'inherited from role "{title}"',
                                'A permission inherited from a certain permission role',
                                ['title' => $role->dbObject('Title')->forTemplate()]
                            );
                        }
                    }
                }

                // Get from parent groups
                $parentGroups = $record->getAncestors();
                if ($parentGroups) {
                    foreach ($parentGroups as $parent) {
                        if (!$parent->Roles()->Count()) {
                            continue;
                        }
                        foreach ($parent->Roles() as $role) {
                            if ($role->Codes()) {
                                foreach ($role->Codes() as $code) {
                                    if (!isset($inheritedCodes[$code->Code])) {
                                        $inheritedCodes[$code->Code] = [];
                                    }
                                    $inheritedCodes[$code->Code][] = _t(
                                        'SilverStripe\\Security\\PermissionCheckboxSetField.FromRoleOnGroup',
                                        'inherited from role "{roletitle}" on group "{grouptitle}"',
                                        'A permission inherited from a role on a certain group',
                                        [
                                            'roletitle' => $role->dbObject('Title')->forTemplate(),
                                            'grouptitle' => $parent->dbObject('Title')->forTemplate()
                                        ]
                                    );
                                }
                            }
                        }
                        if ($parent->Permissions()->Count()) {
                            foreach ($parent->Permissions() as $permission) {
                                if (!isset($inheritedCodes[$permission->Code])) {
                                    $inheritedCodes[$permission->Code] = [];
                                }
                                $inheritedCodes[$permission->Code][] =
                                _t(
                                    'SilverStripe\\Security\\PermissionCheckboxSetField.FromGroup',
                                    'inherited from group "{title}"',
                                    'A permission inherited from a certain group',
                                    ['title' => $parent->dbObject('Title')->forTemplate()]
                                );
                            }
                        }
                    }
                }
            }
        }

        $odd = 0;
        $options = '';
        $globalHidden = (array)Config::inst()->get('SilverStripe\\Security\\Permission', 'hidden_permissions');
        if ($this->source) {
            $privilegedPermissions = Permission::config()->privileged_permissions;

            // loop through all available categorized permissions and see if they're assigned for the given groups
            $hasMultipleRecords = $this->records?->count() > 1;
            foreach ($this->source as $categoryName => $permissions) {
                $options .= "<li><h5>$categoryName</h5></li>";
                foreach ($permissions as $code => $permission) {
                    if (in_array($code, $this->hiddenPermissions ?? [])) {
                        continue;
                    }
                    if (in_array($code, $globalHidden ?? [])) {
                        continue;
                    }

                    $value = $permission['name'];

                    $odd = ($odd + 1) % 2;
                    $extraClass = $odd ? 'odd' : 'even';
                    $extraClass .= ' val' . str_replace([' ', '\\'], ['', '-'], $code ?? '');
                    $itemID = $this->ID() . '_' . preg_replace('/[^a-zA-Z0-9]+/', '', $code ?? '');
                    $disabled = $inheritMessage = '';
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
                    } elseif ($hasMultipleRecords && isset($uninheritedCodes[$code])) {
                        // If code assignments are collected from more than one "source group",
                        // show its origin automatically
                        $inheritMessage = ' (' . join(', ', $uninheritedCodes[$code]) . ')';
                    }

                    // Disallow modification of "privileged" permissions unless currently logged-in user is an admin
                    if (!Permission::check('ADMIN') && in_array($code, $privilegedPermissions ?? [])) {
                        $disabled = ' disabled="true"';
                    }

                    // If the field is readonly, always mark as "disabled"
                    if ($this->readonly) {
                        $disabled = ' disabled="true"';
                    }

                    $inheritMessage = '<small>' . $inheritMessage . '</small>';

                    // If the field is readonly, add a span that will replace the disabled checkbox input
                    if ($this->readonly) {
                        $icon = ($checked) ? 'check-mark-circle' : 'cancel-circled';
                        $record = is_object($this->form) ? $this->form->getRecord() : false;
                        // Inherited codes are shown as a gray x
                        if ($record && $record instanceof Member &&
                            Permission::checkMember($record, 'ADMIN') && $code != 'ADMIN') {
                            $icon = 'plus-circled';
                        }

                        $options .= "<li class=\"$extraClass\">"
                            . "<input id=\"$itemID\"$disabled name=\"$this->name[$code]\" type=\"checkbox\""
                            . " value=\"$code\"$checked class=\"checkbox\" />"
                            . "<label {$title}for=\"$itemID\">"
                            . "<span class=\"font-icon-$icon\"></span>"
                            . "{$value}{$inheritMessage}</label>"
                            . "</li>\n";
                    } else {
                        $options .= "<li class=\"$extraClass\">"
                            . "<input id=\"$itemID\"$disabled name=\"$this->name[$code]\" type=\"checkbox\""
                            . " value=\"$code\"$checked class=\"checkbox\" />"
                            . "<label {$title}for=\"$itemID\">{$value}{$inheritMessage}</label>"
                            . "</li>\n";
                    }
                }
            }
        }
        if ($this->readonly) {
            $message = _t(
                'SilverStripe\\Security\\Permission.UserPermissionsIntro',
                'Assigning groups to this user will adjust the permissions they have.'
                . ' See the groups section for details of permissions on individual groups.'
            );

            return
                "<ul id=\"{$this->ID()}\" class=\"optionset checkboxsetfield{$this->extraClass()}\">\n" .
                "<li class=\"help\">" . $message . "</li>" .
                $options .
                "</ul>\n";
        } else {
            return
                "<ul id=\"{$this->ID()}\" class=\"optionset checkboxsetfield{$this->extraClass()}\">\n" .
                $options .
                "</ul>\n";
        }
    }

    /**
     * Update the permission set associated with $record DataObject
     *
     * @param DataObjectInterface $record
     */
    public function saveInto(DataObjectInterface $record)
    {
        $fieldname = $this->name;
        $managedClass = $this->managedClass;

        // Remove all "privileged" permissions if the currently logged-in user is not an admin
        $privilegedPermissions = Permission::config()->privileged_permissions;
        if ((is_array($this->value) || $this->value instanceof Traversable)
            && !Permission::check('ADMIN')
        ) {
            foreach ($this->value as $id => $bool) {
                if (in_array($id, $privilegedPermissions ?? [])) {
                    unset($this->value[$id]);
                }
            }
        }

        // remove all permissions and re-add them afterwards
        $permissions = $record->$fieldname();
        foreach ($permissions as $permission) {
            $permission->delete();
        }

        $schema = DataObject::getSchema();
        if ($fieldname && $record && (
            $schema->hasManyComponent(get_class($record), $fieldname)
            || $schema->manyManyComponent(get_class($record), $fieldname)
        )) {
            if (!$record->ID) {
                $record->write(); // We need a record ID to write permissions
            }

            if (is_array($this->value) || $this->value instanceof Traversable) {
                foreach ($this->value as $id => $bool) {
                    if ($bool) {
                        $perm = new $managedClass();
                        $perm->{$this->filterField} = $record->ID;
                        $perm->Code = $id;
                        $perm->write();
                    }
                }
            }
        }
    }

    /**
     * @return PermissionCheckboxSetField_Readonly
     */
    public function performReadonlyTransformation()
    {
        $readonly = new PermissionCheckboxSetField_Readonly(
            $this->name,
            $this->title,
            $this->managedClass,
            $this->filterField,
            $this->records
        );

        return $readonly;
    }
}
