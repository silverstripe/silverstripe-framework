<?php
/**
 * Represents a permission assigned to a group.
 * @package framework
 * @subpackage security
 *
 * @property string Code
 * @property int Arg
 * @property int Type
 *
 * @property int GroupID
 *
 * @method Group Group()
 */
class Permission extends DataObject implements TemplateGlobalProvider {

	// the (1) after Type specifies the DB default value which is needed for
	// upgrades from older SilverStripe versions
	private static $db = array(
		"Code" => "Varchar",
		"Arg" => "Int",
		"Type" => "Int(1)"
	);
	private static $has_one = array(
		"Group" => "Group"
	);
	private static $indexes = array(
		"Code" => true
	);
	private static $defaults = array(
		"Type" => 1
	);
	private static $has_many = array();

	private static $many_many = array();

	private static $belongs_many_many = array();

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
	 * @var bool
	 */
	private static $declared_permissions = null;

	/**
	 * Linear list of declared permissions in the system.
	 *
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
	private static $hidden_permissions = array();

	/**
	 * @config These permissions can only be applied by ADMIN users, to prevent
	 * privilege escalation on group assignments and inheritance.
	 * @var array
	 */
	private static $privileged_permissions = array(
		'ADMIN',
		'APPLY_ROLES',
		'EDIT_PERMISSIONS'
	);

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
	public static function check($code, $arg = "any", $member = null, $strict = true) {
		if(!$member) {
			if(!Member::currentUserID()) {
				return false;
			}
			$member = Member::currentUserID();
		}

		return self::checkMember($member, $code, $arg, $strict);
	}

	/**
	 * Permissions cache.  The format is a map, where the keys are member IDs, and the values are
	 * arrays of permission codes.
	 */
	private static $cache_permissions = array();

	/**
	 * Flush the permission cache, for example if you have edited group membership or a permission record.
	 * @todo Call this whenever Group_Members is added to or removed from
	 */
	public static function flush_permission_cache() {
		self::$cache_permissions = array();
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
	public static function checkMember($member, $code, $arg = "any", $strict = true) {
		if(!$member) {
			$memberID = $member = Member::currentUserID();
		} else {
			$memberID = (is_object($member)) ? $member->ID : $member;
		}

		// Turn the code into an array as we may need to add other permsissions to the set we check
		if(!is_array($code)) $code = array($code);

		if($arg == 'any') {
			$adminImpliesAll = (bool)Config::inst()->get('Permission', 'admin_implies_all');
			// Cache the permissions in memory
			if(!isset(self::$cache_permissions[$memberID])) {
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
				}
				elseif (substr($permCode, 0, 11) === 'CMS_ACCESS_') {
					//cms_access_leftandmain means access to all CMS areas
					$code[] = 'CMS_ACCESS_LeftAndMain';
					break;
				}
			}
			
			// if ADMIN has all privileges, then we need to push that code in
			if($adminImpliesAll) {
				$code[] = "ADMIN";
			}

			// Multiple $code values - return true if at least one matches, ie, intersection exists
			return (bool)array_intersect($code, self::$cache_permissions[$memberID]);
		}

		// Code filters
		$codeParams = is_array($code) ? $code : array($code);
		$codeClause = DB::placeholders($codeParams);
		$adminParams = (self::$admin_implies_all) ? array('ADMIN') : array();
		$adminClause = (self::$admin_implies_all) ?  ", ?" : '';

		// The following code should only be used if you're not using the "any" arg.  This is kind
		// of obselete functionality and could possibly be deprecated.
		$groupParams = self::groupList($memberID);
		if(empty($groupParams)) return false;
		$groupClause = DB::placeholders($groupParams);

		// Arg component
		$argClause = "";
		$argParams = array();
		switch($arg) {
			case "any":
				break;
			case "all":
				$argClause = " AND \"Arg\" = ?";
				$argParams = array(-1);
				break;
			default:
				if(is_numeric($arg)) {
					$argClause = "AND \"Arg\" IN (?, ?) ";
					$argParams = array(-1, $arg);
				} else {
					user_error("Permission::checkMember: bad arg '$arg'", E_USER_ERROR);
				}
		}
		$adminFilter = (Config::inst()->get('Permission', 'admin_implies_all')) ?  ",'ADMIN'" : '';

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
				array(self::GRANT_PERMISSION),
				$groupParams,
				$argParams
			)
		)->value();

		if($permission) return $permission;

		// Strict checking disabled?
		if(!Config::inst()->get('Permission', 'strict_checking') || !$strict) {
			$hasPermission = DB::prepared_query(
				"SELECT COUNT(*)
				FROM \"Permission\"
				WHERE (
					\"Code\" IN ($codeClause) AND
					\"Type\" = ?
				)",
				array_merge($codeParams, array(self::GRANT_PERMISSION))
			)->value();

			if(!$hasPermission) return;
		}

		return false;
	}

	/**
	 * Get all the 'any' permission codes available to the given member.
	 *
	 * @return array
	 */
	public static function permissions_for_member($memberID) {
		$groupList = self::groupList($memberID);

		if($groupList) {
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

		return array();
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
	public static function groupList($memberID = null) {
		// Default to current member, with session-caching
		if(!$memberID) {
			$member = Member::currentUser();
			if($member && isset($_SESSION['Permission_groupList'][$member->ID]))
				return $_SESSION['Permission_groupList'][$member->ID];
		} else {
			$member = DataObject::get_by_id("Member", $memberID);
		}

		if($member) {
			// Build a list of the IDs of the groups.  Most of the heavy lifting
			// is done by Member::Groups
			// NOTE: This isn't effecient; but it's called once per session so
			// it's a low priority to fix.
			$groups = $member->Groups();
			$groupList = array();

			if($groups) {
				foreach($groups as $group)
					$groupList[] = $group->ID;
			}


			// Session caching
			if(!$memberID) {
				$_SESSION['Permission_groupList'][$member->ID] = $groupList;
			}

			return isset($groupList) ? $groupList : null;
		}
	}


	/**
	 * Grant the given permission code/arg to the given group
	 *
	 * @param int $groupID The ID of the group
	 * @param string $code The permission code
	 * @param string Optional: The permission argument (e.g. a page ID).
	 * @returns Permission Returns the new permission object.
	 */
	public static function grant($groupID, $code, $arg = "any") {
		$perm = new Permission();
		$perm->GroupID = $groupID;
		$perm->Code = $code;
		$perm->Type = self::GRANT_PERMISSION;

		// Arg component
		switch($arg) {
			case "any":
				break;
			case "all":
				$perm->Arg = -1;
			default:
				if(is_numeric($arg)) {
					$perm->Arg = $arg;
				} else {
					user_error("Permission::checkMember: bad arg '$arg'",
										E_USER_ERROR);
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
	 * @param string Optional: The permission argument (e.g. a page ID).
	 * @returns Permission Returns the new permission object.
	 */
	public static function deny($groupID, $code, $arg = "any") {
		$perm = new Permission();
		$perm->GroupID = $groupID;
		$perm->Code = $code;
		$perm->Type = self::DENY_PERMISSION;

		// Arg component
		switch($arg) {
			case "any":
				break;
			case "all":
				$perm->Arg = -1;
			default:
				if(is_numeric($arg)) {
					$perm->Arg = $arg;
				} else {
					user_error("Permission::checkMember: bad arg '$arg'",
										E_USER_ERROR);
				}
		}

		$perm->write();
		return $perm;
	}

	/**
	 * Returns all members for a specific permission.
	 *
	 * @param $code String|array Either a single permission code, or a list of permission codes
	 * @return SS_List Returns a set of member that have the specified
	 *                       permission.
	 */
	public static function get_members_by_permission($code) {
		$toplevelGroups = self::get_groups_by_permission($code);
		if (!$toplevelGroups) return new ArrayList();

		$groupIDs = array();
		foreach($toplevelGroups as $group) {
			$familyIDs = $group->collateFamilyIDs();
			if(is_array($familyIDs)) {
				$groupIDs = array_merge($groupIDs, array_values($familyIDs));
			}
		}

		if(empty($groupIDs)) return new ArrayList();

		$groupClause = DB::placeholders($groupIDs);
		$members = Member::get()
			->where(array("\"Group\".\"ID\" IN ($groupClause)" => $groupIDs))
			->leftJoin("Group_Members", '"Member"."ID" = "Group_Members"."MemberID"')
			->leftJoin("Group", '"Group_Members"."GroupID" = "Group"."ID"');

		return $members;
	}

	/**
	 * Return all of the groups that have one of the given permission codes
	 * @param $codes array|string Either a single permission code, or an array of permission codes
	 * @return SS_List The matching group objects
	 */
	public static function get_groups_by_permission($codes) {
		$codeParams = is_array($codes) ? $codes : array($codes);
		$codeClause = DB::placeholders($codeParams);

		// Via Roles are groups that have the permission via a role
		return DataObject::get('Group')
			->where(array(
				"\"PermissionRoleCode\".\"Code\" IN ($codeClause) OR \"Permission\".\"Code\" IN ($codeClause)"
				=> array_merge($codeParams, $codeParams)
			))
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
	public static function get_codes($grouped = true) {
		$classes = ClassInfo::implementorsOf('PermissionProvider');

		$allCodes = array();
		$adminCategory = _t('Permission.AdminGroup', 'Administrator');
		$allCodes[$adminCategory]['ADMIN'] = array(
			'name' => _t('Permission.FULLADMINRIGHTS', 'Full administrative rights'),
			'help' => _t(
				'Permission.FULLADMINRIGHTS_HELP',
				'Implies and overrules all other assigned permissions.'
			),
			'sort' => 100000
		);

		if($classes) foreach($classes as $class) {
			$SNG = singleton($class);
			if($SNG instanceof TestOnly) continue;

			$someCodes = $SNG->providePermissions();
			if($someCodes) {
				foreach($someCodes as $k => $v) {
					if (is_array($v)) {
						// There must be a category and name key.
						if (!isset($v['category'])) user_error("The permission $k must have a category key",
							E_USER_WARNING);
						if (!isset($v['name'])) user_error("The permission $k must have a name key",
							E_USER_WARNING);

						if (!isset($allCodes[$v['category']])) $allCodes[$v['category']] = array();

						$allCodes[$v['category']][$k] = array(
							'name' => $v['name'],
							'help' => isset($v['help']) ? $v['help'] : null,
							'sort' => isset($v['sort']) ? $v['sort'] : 0
						);

					} else {
						$allCodes['Other'][$k] = array(
							'name' => $v,
							'help' => null,
							'sort' => 0
						);
					}
				}
			}
		}

		$flatCodeArray = array();
		foreach($allCodes as $category) foreach($category as $code => $permission) $flatCodeArray[] = $code;
		$otherPerms = DB::query("SELECT DISTINCT \"Code\" From \"Permission\" WHERE \"Code\" != ''")->column();

		if($otherPerms) foreach($otherPerms as $otherPerm) {
			if(!in_array($otherPerm, $flatCodeArray))
				$allCodes['Other'][$otherPerm] = array(
					'name' => $otherPerm,
					'help' => null,
					'sort' => 0
				);
		}

		// Don't let people hijack ADMIN rights
		if(!Permission::check("ADMIN")) unset($allCodes['ADMIN']);

		ksort($allCodes);

		$returnCodes = array();
		foreach($allCodes as $category => $permissions) {
			if($grouped) {
				uasort($permissions, array(__CLASS__, 'sort_permissions'));
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
	 */
	public static function sort_permissions($a, $b) {
		if ($a['sort'] == $b['sort']) {
			// Same sort value, do alpha instead
			return strcmp($a['name'], $b['name']);
		} else {
			// Just numeric.
			return $a['sort'] < $b['sort'] ? -1 : 1;
		}
	}

	/**
	 * add a permission represented by the $code to the {@link slef::$hidden_permissions} list
	 *
	 * @deprecated 4.0 Use "Permission.hidden_permissions" config setting instead
	 * @param $code string - the permissions code
	 * @return void
	 */
	public static function add_to_hidden_permissions($code){
		if(is_string($codes)) $codes = array($codes);
		Deprecation::notice('4.0', 'Use "Permission.hidden_permissions" config setting instead');
		Config::inst()->update('Permission', 'hidden_permissions', $codes);
	}

	/**
	 * remove a permission represented by the $code from the {@link slef::$hidden_permissions} list
	 *
	 * @deprecated 4.0 Use "Permission.hidden_permissions" config setting instead
	 * @param $code string - the permissions code
	 * @return void
	 */
	public static function remove_from_hidden_permissions($code){
		if(is_string($codes)) $codes = array($codes);
		Deprecation::notice('4.0', 'Use "Permission.hidden_permissions" config setting instead');
		Config::inst()->remove('Permission', 'hidden_permissions', $codes);
	}

	/**
	 * Declare an array of permissions for the system.
	 *
	 * Permissions can be grouped by nesting arrays. Scalar values are always
	 * treated as permissions.
	 *
	 * @deprecated 4.0 Use "Permission.declared_permissions" config setting instead
	 * @param array $permArray A (possibly nested) array of permissions to
	 *                         declare for the system.
	 */
	public static function declare_permissions($permArray) {
		Deprecation::notice('4.0', 'Use "Permission.declared_permissions" config setting instead');
		self::config()->declared_permissions = $permArray;
	}


	/**
	 * Get a linear list of the permissions in the system.
	 *
	 * @return array Linear list of declared permissions in the system.
	 */
	public static function get_declared_permissions_list() {
		if(!self::$declared_permissions)
			return null;

		if(self::$declared_permissions_list)
			return self::$declared_permissions_list;

		self::$declared_permissions_list = array();

		self::traverse_declared_permissions(self::$declared_permissions,
																				self::$declared_permissions_list);

		return self::$declared_permissions_list;
	}

	/**
	 * Look up the human-readable title for the permission as defined by <code>Permission::declare_permissions</code>
	 *
	 * @param $perm Permission code
	 * @return Label for the given permission, or the permission itself if the label doesn't exist
	 */
	public static function get_label_for_permission($perm) {
		$list = self::get_declared_permissions_list();
		if(array_key_exists($perm, $list)) return $list[$perm];
		return $perm;
	}

	/**
	 * Recursively traverse the nested list of declared permissions and create
	 * a linear list.
	 *
	 * @param aeeay $declared Nested structure of permissions.
	 * @param $list List of permissions in the structure. The result will be
	 *              written to this array.
	 */
	protected static function traverse_declared_permissions($declared, &$list) {
		if(!is_array($declared))
			return;

		foreach($declared as $perm => $value) {
			if($value instanceof Permission_Group) {
				$list[] = $value->getName();
				self::traverse_declared_permissions($value->getPermissions(), $list);
			} else {
				$list[$perm] = $value;
			}
		}
	}

	public function onBeforeWrite() {
		parent::onBeforeWrite();

		// Just in case we've altered someone's permissions
		Permission::flush_permission_cache();
	}

	public static function get_template_global_variables() {
		return array(
			'HasPerm' => 'check'
		);
	}
}


/**
 * Permission_Group class
 *
 * This class is used to group permissions together for showing on an
 * interface.
 * @package framework
 * @subpackage security
 */
class Permission_Group {

	/**
	 * Name of the permission group (can be used as label in an interface)
	 * @var string
	 */
	protected $name;

	/**
	 * Associative array of permissions in this permission group. The array
	 * indicies are the permission codes as used in
	 * {@link Permission::check()}. The value is suitable for using in an
	 * interface.
	 * @var string
	 */
	protected $permissions = array();


	/**
	 * Constructor
	 *
	 * @param string $name Text that could be used as label used in an
	 *                     interface
	 * @param array $permissions Associative array of permissions in this
	 *                           permission group. The array indicies are the
	 *                           permission codes as used in
	 *                           {@link Permission::check()}. The value is
	 *                           suitable for using in an interface.
	 */
	public function __construct($name, $permissions) {
		$this->name = $name;
		$this->permissions = $permissions;
	}

	/**
	 * Get the name of the permission group
	 *
	 * @return string Name (label) of the permission group
	 */
	public function getName() {
		return $this->name;
	}


	/**
	 * Get permissions
	 *
	 * @return array Associative array of permissions in this permission
	 *               group. The array indicies are the permission codes as
	 *               used in {@link Permission::check()}. The value is
	 *               suitable for using in an interface.
	 */
	public function getPermissions() {
		return $this->permissions;
	}
}


