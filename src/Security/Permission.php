<?php

namespace SilverStripe\Security;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Resettable;
use SilverStripe\Dev\TestOnly;
use SilverStripe\i18n\i18nEntityProvider;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\TemplateGlobalProvider;

/**
 * Represents a permission assigned to a group.
 *
 * @property string Code
 * @property int Arg
 * @property int Type
 * @property int GroupID
 * @method Group Group()
 */
class Permission extends DataObject implements TemplateGlobalProvider, Resettable, i18nEntityProvider
{

    // the (1) after Type specifies the DB default value which is needed for
    // upgrades from older SilverStripe versions
    private static $db = [
        "Code" => "Varchar(255)",
        "Arg" => "Int",
        "Type" => "Int(1)"
    ];

    private static $has_one = [
        "Group" => Group::class,
    ];

    private static $indexes = [
        "Code" => true
    ];

    private static $defaults = [
        "Type" => 1
    ];

    private static $table_name = "Permission";

    /**
     * This is the value to use for the "Type" field if a permission should be
     * granted.
     */
    const GRANT_PERMISSION = 1;

    /**
     * This is the value to use for the "Type" field if a permission should be
     * denied.
     */
    const DENY_PERMISSION = -1;

    /**
     * This is the value to use for the "Type" field if a permission should be
     * inherited.
     */
    const INHERIT_PERMISSION = 0;


    /**
     * Method to globally disable "strict" checking, which means a permission
     * will be granted if the key does not exist at all.
     *
     * @deprecated 4.4.0
     * @var array
     */
    private static $declared_permissions = null;

    /**
     * Linear list of declared permissions in the system.
     *
     * @deprecated 4.4.0
     * @var array
     */
    private static $declared_permissions_list = null;

    /**
     * @config
     * @var $strict_checking Boolean Method to globally disable "strict" checking,
     * which means a permission will be granted if the key does not exist at all.
     */
    private static $strict_checking = true;

    /**
     * Set to false to prevent the 'ADMIN' permission from implying all
     * permissions in the system
     *
     * @config
     * @var bool
     */
    private static $admin_implies_all = true;

    /**
     * a list of permission codes which doesn't appear in the Permission list
     * when make the {@link PermissionCheckboxSetField}
     * @config
     * @var array;
     */
    private static $hidden_permissions = [];

    /**
     * @config These permissions can only be applied by ADMIN users, to prevent
     * privilege escalation on group assignments and inheritance.
     * @var array
     */
    private static $privileged_permissions = [
        'ADMIN',
        'APPLY_ROLES',
        'EDIT_PERMISSIONS'
    ];

    /**
     * Check that the current member has the given permission.
     *
     * @param string|array $code Code of the permission to check (case-sensitive)
     * @param string $arg Optional argument (e.g. a permissions for a specific page)
     * @param int|Member $member Optional member instance or ID. If set to NULL, the permssion
     *  will be checked for the current user
     * @param bool $strict Use "strict" checking (which means a permission
     *  will be granted if the key does not exist at all)?
     * @return int|bool The ID of the permission record if the permission
     *  exists; FALSE otherwise. If "strict" checking is
     *  disabled, TRUE will be returned if the permission does not exist at all.
     */
    public static function check($code, $arg = "any", $member = null, $strict = true)
    {
        if (!$member) {
            if (!Security::getCurrentUser()) {
                return false;
            }
            $member = Security::getCurrentUser();
        }

        return self::checkMember($member, $code, $arg, $strict);
    }

    /**
     * Permissions cache.  The format is a map, where the keys are member IDs, and the values are
     * arrays of permission codes.
     */
    private static $cache_permissions = [];

    /**
     * Flush the permission cache, for example if you have edited group membership or a permission record.
     * @todo Call this whenever Group_Members is added to or removed from
     */
    public static function reset()
    {
        self::$cache_permissions = [];
    }

    /**
     * Check that the given member has the given permission.
     *
     * @param int|Member memberID The ID of the member to check. Leave blank for the current member.
     *  Alternatively you can use a member object.
     * @param string|array $code Code of the permission to check (case-sensitive)
     * @param string $arg Optional argument (e.g. a permissions for a specific page)
     * @param bool $strict Use "strict" checking (which means a permission
     *  will be granted if the key does not exist at all)?
     * @return int|bool The ID of the permission record if the permission
     *  exists; FALSE otherwise. If "strict" checking is
     *  disabled, TRUE will be returned if the permission does not exist at all.
     */
    public static function checkMember($member, $code, $arg = "any", $strict = true)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        $memberID = ($member instanceof Member) ? $member->ID : $member;

        if (!$memberID) {
            return false;
        }

        // Turn the code into an array as we may need to add other permsissions to the set we check
        if (!is_array($code)) {
            $code = [$code];
        }

        // Check if admin should be treated as holding all permissions
        $adminImpliesAll = (bool)static::config()->admin_implies_all;

        if ($arg == 'any') {
            // Cache the permissions in memory
            if (!isset(self::$cache_permissions[$memberID])) {
                self::$cache_permissions[$memberID] = self::permissions_for_member($memberID);
            }
            foreach ($code as $permCode) {
                if ($permCode === 'CMS_ACCESS') {
                    foreach (self::$cache_permissions[$memberID] as $perm) {
                        //if they have admin rights OR they have an explicit access to the CMS then give permission
                        if (($adminImpliesAll && $perm == 'ADMIN') || substr($perm, 0, 11) === 'CMS_ACCESS_') {
                            return true;
                        }
                    }
                } elseif (substr($permCode, 0, 11) === 'CMS_ACCESS_' && !in_array('CMS_ACCESS_LeftAndMain', $code)) {
                    //cms_access_leftandmain means access to all CMS areas
                    $code[] = 'CMS_ACCESS_LeftAndMain';
                }
            }

            // if ADMIN has all privileges, then we need to push that code in
            if ($adminImpliesAll) {
                $code[] = "ADMIN";
            }

            // Multiple $code values - return true if at least one matches, ie, intersection exists
            return (bool)array_intersect($code, self::$cache_permissions[$memberID]);
        }

        // Code filters
        $codeParams = is_array($code) ? $code : [$code];
        $codeClause = DB::placeholders($codeParams);
        $adminParams = $adminImpliesAll ? ['ADMIN'] : [];
        $adminClause = $adminImpliesAll ?  ", ?" : '';

        // The following code should only be used if you're not using the "any" arg.  This is kind
        // of obselete functionality and could possibly be deprecated.
        $groupParams = self::groupList($memberID);
        if (empty($groupParams)) {
            return false;
        }
        $groupClause = DB::placeholders($groupParams);

        // Arg component
        $argClause = "";
        $argParams = [];
        switch ($arg) {
            case "any":
                break;
            case "all":
                $argClause = " AND \"Arg\" = ?";
                $argParams = [-1];
                break;
            default:
                if (is_numeric($arg)) {
                    $argClause = "AND \"Arg\" IN (?, ?) ";
                    $argParams = [-1, $arg];
                } else {
                    user_error("Permission::checkMember: bad arg '$arg'", E_USER_ERROR);
                }
        }

        // Raw SQL for efficiency
        $permission = DB::prepared_query(
            "SELECT \"ID\"
			FROM \"Permission\"
			WHERE (
				\"Code\" IN ($codeClause $adminClause)
				AND \"Type\" = ?
				AND \"GroupID\" IN ($groupClause)
				$argClause
			)",
            array_merge(
                $codeParams,
                $adminParams,
                [self::GRANT_PERMISSION],
                $groupParams,
                $argParams
            )
        )->value();

        if ($permission) {
            return $permission;
        }

        // Strict checking disabled?
        if (!static::config()->strict_checking || !$strict) {
            $hasPermission = DB::prepared_query(
                "SELECT COUNT(*)
				FROM \"Permission\"
				WHERE (
					\"Code\" IN ($codeClause) AND
					\"Type\" = ?
				)",
                array_merge($codeParams, [self::GRANT_PERMISSION])
            )->value();

            if (!$hasPermission) {
                return false;
            }
        }

        return false;
    }

    /**
     * Get all the 'any' permission codes available to the given member.
     *
     * @param int $memberID
     * @return array
     */
    public static function permissions_for_member($memberID)
    {
        $groupList = self::groupList($memberID);

        if ($groupList) {
            $groupCSV = implode(", ", $groupList);

            $allowed = array_unique(DB::query("
				SELECT \"Code\"
				FROM \"Permission\"
				WHERE \"Type\" = " . self::GRANT_PERMISSION . " AND \"GroupID\" IN ($groupCSV)

				UNION

				SELECT \"Code\"
				FROM \"PermissionRoleCode\" PRC
				INNER JOIN \"PermissionRole\" PR ON PRC.\"RoleID\" = PR.\"ID\"
				INNER JOIN \"Group_Roles\" GR ON GR.\"PermissionRoleID\" = PR.\"ID\"
				WHERE \"GroupID\" IN ($groupCSV)
			")->column());

            $denied = array_unique(DB::query("
				SELECT \"Code\"
				FROM \"Permission\"
				WHERE \"Type\" = " . self::DENY_PERMISSION . " AND \"GroupID\" IN ($groupCSV)
			")->column());

            return array_diff($allowed, $denied);
        }

        return [];
    }


    /**
     * Get the list of groups that the given member belongs to.
     *
     * Call without an argument to get the groups that the current member
     * belongs to. In this case, the results will be session-cached.
     *
     * @param int $memberID The ID of the member. Leave blank for the current
     *                      member.
     * @return array Returns a list of group IDs to which the member belongs
     *               to or NULL.
     */
    public static function groupList($memberID = null)
    {
        // Default to current member, with session-caching
        if (!$memberID) {
            $member = Security::getCurrentUser();
            if ($member && isset($_SESSION['Permission_groupList'][$member->ID])) {
                return $_SESSION['Permission_groupList'][$member->ID];
            }
        } else {
            $member = DataObject::get_by_id("SilverStripe\\Security\\Member", $memberID);
        }

        if ($member) {
            // Build a list of the IDs of the groups.  Most of the heavy lifting
            // is done by Member::Groups
            // NOTE: This isn't effecient; but it's called once per session so
            // it's a low priority to fix.
            $groups = $member->Groups();
            $groupList = [];

            if ($groups) {
                foreach ($groups as $group) {
                    $groupList[] = $group->ID;
                }
            }


            // Session caching
            if (!$memberID) {
                $_SESSION['Permission_groupList'][$member->ID] = $groupList;
            }

            return isset($groupList) ? $groupList : null;
        }
        return null;
    }


    /**
     * Grant the given permission code/arg to the given group
     *
     * @param int $groupID The ID of the group
     * @param string $code The permission code
     * @param string $arg Optional: The permission argument (e.g. a page ID).
     * @returns Permission Returns the new permission object.
     */
    public static function grant($groupID, $code, $arg = "any")
    {
        $perm = new Permission();
        $perm->GroupID = $groupID;
        $perm->Code = $code;
        $perm->Type = self::GRANT_PERMISSION;

        // Arg component
        switch ($arg) {
            case "any":
                break;
            case "all":
                $perm->Arg = -1;
                break;
            default:
                if (is_numeric($arg)) {
                    $perm->Arg = $arg;
                } else {
                    user_error(
                        "Permission::checkMember: bad arg '$arg'",
                        E_USER_ERROR
                    );
                }
        }

        $perm->write();
        return $perm;
    }


    /**
     * Deny the given permission code/arg to the given group
     *
     * @param int $groupID The ID of the group
     * @param string $code The permission code
     * @param string $arg Optional: The permission argument (e.g. a page ID).
     * @returns Permission Returns the new permission object.
     */
    public static function deny($groupID, $code, $arg = "any")
    {
        $perm = new Permission();
        $perm->GroupID = $groupID;
        $perm->Code = $code;
        $perm->Type = self::DENY_PERMISSION;

        // Arg component
        switch ($arg) {
            case "any":
                break;
            case "all":
                $perm->Arg = -1;
                break;
            default:
                if (is_numeric($arg)) {
                    $perm->Arg = $arg;
                } else {
                    user_error(
                        "Permission::checkMember: bad arg '$arg'",
                        E_USER_ERROR
                    );
                }
        }

        $perm->write();
        return $perm;
    }

    /**
     * Returns all members for a specific permission.
     *
     * @param string|array $code Either a single permission code, or a list of permission codes
     * @return SS_List Returns a set of member that have the specified
     *                       permission.
     */
    public static function get_members_by_permission($code)
    {
        $toplevelGroups = self::get_groups_by_permission($code);
        if (!$toplevelGroups) {
            return new ArrayList();
        }

        $groupIDs = [];
        foreach ($toplevelGroups as $group) {
            $familyIDs = $group->collateFamilyIDs();
            if (is_array($familyIDs)) {
                $groupIDs = array_merge($groupIDs, array_values($familyIDs));
            }
        }

        if (empty($groupIDs)) {
            return new ArrayList();
        }

        $groupClause = DB::placeholders($groupIDs);
        /** @skipUpgrade */
        $members = Member::get()
            ->where(["\"Group\".\"ID\" IN ($groupClause)" => $groupIDs])
            ->leftJoin("Group_Members", '"Member"."ID" = "Group_Members"."MemberID"')
            ->leftJoin("Group", '"Group_Members"."GroupID" = "Group"."ID"');

        return $members;
    }

    /**
     * Return all of the groups that have one of the given permission codes
     * @param array|string $codes Either a single permission code, or an array of permission codes
     * @return SS_List The matching group objects
     */
    public static function get_groups_by_permission($codes)
    {
        $codeParams = is_array($codes) ? $codes : [$codes];
        $codeClause = DB::placeholders($codeParams);

        // Via Roles are groups that have the permission via a role
        /** @skipUpgrade */
        return Group::get()
            ->where([
                "\"PermissionRoleCode\".\"Code\" IN ($codeClause) OR \"Permission\".\"Code\" IN ($codeClause)"
                => array_merge($codeParams, $codeParams)
            ])
            ->leftJoin('Permission', "\"Permission\".\"GroupID\" = \"Group\".\"ID\"")
            ->leftJoin('Group_Roles', "\"Group_Roles\".\"GroupID\" = \"Group\".\"ID\"")
            ->leftJoin('PermissionRole', "\"Group_Roles\".\"PermissionRoleID\" = \"PermissionRole\".\"ID\"")
            ->leftJoin('PermissionRoleCode', "\"PermissionRoleCode\".\"RoleID\" = \"PermissionRole\".\"ID\"");
    }


    /**
     * Get a list of all available permission codes, both defined through the
     * {@link PermissionProvider} interface, and all not explicitly defined codes existing
     * as a {@link Permission} database record. By default, the results are
     * grouped as denoted by {@link Permission_Group}.
     *
     * @param bool $grouped Group results into an array of permission groups.
     * @return array Returns an array of all available permission codes. The
     *  array indicies are the permission codes as used in
     *  {@link Permission::check()}. The value is a description
     *  suitable for using in an interface.
     */
    public static function get_codes($grouped = true)
    {
        $classes = ClassInfo::implementorsOf('SilverStripe\\Security\\PermissionProvider');

        $allCodes = [];
        $adminCategory = _t(__CLASS__ . '.AdminGroup', 'Administrator');
        $allCodes[$adminCategory]['ADMIN'] = [
            'name' => _t(__CLASS__ . '.FULLADMINRIGHTS', 'Full administrative rights'),
            'help' => _t(
                'SilverStripe\\Security\\Permission.FULLADMINRIGHTS_HELP',
                'Implies and overrules all other assigned permissions.'
            ),
            'sort' => 100000
        ];

        if ($classes) {
            foreach ($classes as $class) {
                $SNG = singleton($class);
                if ($SNG instanceof TestOnly) {
                    continue;
                }

                $someCodes = $SNG->providePermissions();
                if ($someCodes) {
                    foreach ($someCodes as $k => $v) {
                        if (is_array($v)) {
                            // There must be a category and name key.
                            if (!isset($v['category'])) {
                                user_error(
                                    "The permission $k must have a category key",
                                    E_USER_WARNING
                                );
                            }
                            if (!isset($v['name'])) {
                                user_error(
                                    "The permission $k must have a name key",
                                    E_USER_WARNING
                                );
                            }

                            if (!isset($allCodes[$v['category']])) {
                                $allCodes[$v['category']] = [];
                            }

                            $allCodes[$v['category']][$k] = [
                            'name' => $v['name'],
                            'help' => isset($v['help']) ? $v['help'] : null,
                            'sort' => isset($v['sort']) ? $v['sort'] : 0
                            ];
                        } else {
                            $allCodes['Other'][$k] = [
                            'name' => $v,
                            'help' => null,
                            'sort' => 0
                            ];
                        }
                    }
                }
            }
        }

        $flatCodeArray = [];
        foreach ($allCodes as $category) {
            foreach ($category as $code => $permission) {
                $flatCodeArray[] = $code;
            }
        }
        $otherPerms = DB::query("SELECT DISTINCT \"Code\" From \"Permission\" WHERE \"Code\" != ''")->column();

        if ($otherPerms) {
            foreach ($otherPerms as $otherPerm) {
                if (!in_array($otherPerm, $flatCodeArray)) {
                    $allCodes['Other'][$otherPerm] = [
                    'name' => $otherPerm,
                    'help' => null,
                    'sort' => 0
                    ];
                }
            }
        }

        // Don't let people hijack ADMIN rights
        if (!Permission::check("ADMIN")) {
            unset($allCodes['ADMIN']);
        }

        ksort($allCodes);

        $returnCodes = [];
        foreach ($allCodes as $category => $permissions) {
            if ($grouped) {
                uasort($permissions, [__CLASS__, 'sort_permissions']);
                $returnCodes[$category] = $permissions;
            } else {
                $returnCodes = array_merge($returnCodes, $permissions);
            }
        }

        return $returnCodes;
    }

    /**
     * Sort permissions based on their sort value, or name
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    public static function sort_permissions($a, $b)
    {
        if ($a['sort'] == $b['sort']) {
            // Same sort value, do alpha instead
            return strcmp($a['name'], $b['name']);
        } else {
            // Just numeric.
            return $a['sort'] < $b['sort'] ? -1 : 1;
        }
    }

    /**
     * Get a linear list of the permissions in the system.
     *
     * @return array Linear list of declared permissions in the system.
     * @deprecated 4.4.0
     */
    public static function get_declared_permissions_list()
    {
        if (!self::$declared_permissions) {
            return null;
        }

        if (self::$declared_permissions_list) {
            return self::$declared_permissions_list;
        }

        self::$declared_permissions_list = [];

        self::traverse_declared_permissions(self::$declared_permissions, self::$declared_permissions_list);

        return self::$declared_permissions_list;
    }

    /**
     * Look up the human-readable title for the permission as defined by <code>Permission::declare_permissions</code>
     *
     * @param string $perm Permission code
     * @return string Label for the given permission, or the permission itself if the label doesn't exist
     * @deprecated 4.4.0
     */
    public static function get_label_for_permission($perm)
    {
        $list = self::get_declared_permissions_list();
        if (array_key_exists($perm, $list)) {
            return $list[$perm];
        }
        return $perm;
    }

    /**
     * Recursively traverse the nested list of declared permissions and create
     * a linear list.
     *
     * @param array $declared Nested structure of permissions.
     * @param array $list List of permissions in the structure. The result will be
     *              written to this array.
     * @deprecated 4.4.0
     */
    protected static function traverse_declared_permissions($declared, &$list)
    {
        if (!is_array($declared)) {
            return;
        }

        foreach ($declared as $perm => $value) {
            if ($value instanceof Permission_Group) {
                $list[] = $value->getName();
                self::traverse_declared_permissions($value->getPermissions(), $list);
            } else {
                $list[$perm] = $value;
            }
        }
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Just in case we've altered someone's permissions
        Permission::reset();
    }

    public static function get_template_global_variables()
    {
        return [
            'HasPerm' => 'check'
        ];
    }

    public function provideI18nEntities()
    {
        $keys = parent::provideI18nEntities();

        // Localise all permission categories
        $keys[__CLASS__ . '.AdminGroup'] = 'Administrator';
        $keys[__CLASS__ . '.CMS_ACCESS_CATEGORY'] = 'CMS Access';
        $keys[__CLASS__ . '.CONTENT_CATEGORY'] = 'Content permissions';
        $keys[__CLASS__ . '.PERMISSIONS_CATEGORY'] = 'Roles and access permissions';
        return $keys;
    }
}
