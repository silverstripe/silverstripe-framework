<?php
/**
 * Represents a permission assigned to a group.
 * @package sapphire
 * @subpackage security
 */
class Permission extends DataObject {

  // the (1) after Type specifies the DB default value which is needed for
	// upgrades from older SilverStripe versions
	static $db = array(
		"Code" => "Varchar",
		"Arg" => "Int",
		"Type" => "Int(1)"
	);
	static $has_one = array(
		"Group" => "Group"
	);
	static $indexes = array(
		"Code" => true
	);
	static $defaults = array(
		"Type" => 1
	);
	static $has_many = array();
	
	static $many_many = array();
	
	static $belongs_many_many = array();

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
	static $declared_permissions = null;

  /**
	 * Linear list of declared permissions in the system.
	 *
	 * @var array
	 */
	protected static $declared_permissions_list = null;

	/**
	 * @var $strict_checking Boolean Method to globally disable "strict" checking,
	 * which means a permission will be granted if the key does not exist at all.
	 */
	static $strict_checking = true;
	
	/**
	 * If this setting is set, then permissions can imply other permissions
	 *
	 * @var bool
	 */
	static $implied_permissions = false;

	/**
	 * Set to false to prevent the 'ADMIN' permission from implying all
	 * permissions in the system
	 *
	 * @var bool
	 */
	static $admin_implies_all = true;
	
	/**
	 * a list of permission codes which doesn't appear in the Permission list
	 * when make the {@link PermissionCheckboxSetField}
	 * @var array;
	 */
	static $hidden_permissions = array();

	/**
	 * Check that the current member has the given permission.
	 * 
	 * @param string $code Code of the permission to check (case-sensitive)
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
		
		if($arg == 'any') {
			// Cache the permissions in memory
			if(!isset(self::$cache_permissions[$memberID])) {
				self::$cache_permissions[$memberID] = self::permissions_for_member($memberID);
			}
			
			// If $admin_implies_all was false then this would be inefficient, but that's an edge
			// case and this keeps the code simpler
			if(!is_array($code)) $code = array($code);
			if(self::$admin_implies_all) $code[] = "ADMIN";

			// Multiple $code values - return true if at least one matches, ie, intersection exists
			return (bool)array_intersect($code, self::$cache_permissions[$memberID]);
		} 

		// The following code should only be used if you're not using the "any" arg.  This is kind
		// of obselete functionality and could possibly be deprecated.

		$groupList = self::groupList($memberID);
		if(!$groupList) return false;
		
		$groupCSV = implode(", ", $groupList);
		
		// Arg component
		switch($arg) {
			case "any":
				$argClause = "";
				break;
			case "all":
				$argClause = " AND \"Arg\" = -1";
				break;
			default:
				if(is_numeric($arg)) {
					$argClause = "AND \"Arg\" IN (-1, $arg) ";
				} else {
					user_error("Permission::checkMember: bad arg '$arg'", E_USER_ERROR);
				}
		}
		
		if(is_array($code)) {
			$SQL_codeList = "'" . implode("', '", Convert::raw2sql($code)) . "'";
		} else {
			$SQL_codeList = "'" . Convert::raw2sql($code) . "'";
		}
		
		$SQL_code = Convert::raw2sql($code);
		
		$adminFilter = (self::$admin_implies_all) ?  ",'ADMIN'" : '';

		// Raw SQL for efficiency
		$permission = DB::query("
			SELECT \"ID\"
			FROM \"Permission\"
			WHERE (
				\"Code\" IN ($SQL_codeList $adminFilter)
				AND \"Type\" = " . self::GRANT_PERMISSION . "
				AND \"GroupID\" IN ($groupCSV)
				$argClause
			)
		")->value();

		if($permission) return $permission;

		// Strict checking disabled?
		if(!self::$strict_checking || !$strict) {
			$hasPermission = DB::query("
				SELECT COUNT(*) 
				FROM \"Permission\"
				WHERE (
					(\"Code\" IN '$SQL_code')' 
					AND (\"Type\" = " . self::GRANT_PERMISSION . ")
				)
			")->value();

			if(!$hasPermission) return;
		}
		
		return false;
	}

	/**
	 * Get all the 'any' permission codes available to the given member.
	 * @return array();
	 */
	public static function permissions_for_member($memberID) {
		$groupList = self::groupList($memberID);
		if($groupList) {
			$groupCSV = implode(", ", $groupList);

			// Raw SQL for efficiency
			return array_unique(DB::query("
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

		} else {
			return array();
		}
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
	 * @return DataObjectSet Returns a set of member that have the specified
	 *                       permission.
	 */
	public static function get_members_by_permission($code) {
		$toplevelGroups = self::get_groups_by_permission($code);
		if (!$toplevelGroups) return false;

		$groupIDs = array();
		foreach($toplevelGroups as $group) {
			$familyIDs = $group->collateFamilyIDs();
			if(is_array($familyIDs)) {
				$groupIDs = array_merge($groupIDs, array_values($familyIDs));
			}
		}

		if(!count($groupIDs))
			return false;
			
		$members = DataObject::get(
			Object::getCustomClass('Member'),
			$_filter = "\"Group\".\"ID\" IN (" . implode(",",$groupIDs) . ")",
			$_sort = "",
			$_join = "LEFT JOIN \"Group_Members\" ON \"Member\".\"ID\" = \"Group_Members\".\"MemberID\" " . 
				"LEFT JOIN \"Group\" ON \"Group_Members\".\"GroupID\" = \"Group\".\"ID\" "
		);
		
		return $members;
	}

	/**
	 * Return all of the groups that have one of the given permission codes
	 * @param $codes array|string Either a single permission code, or an array of permission codes
	 * @return DataObjectSet The matching group objects
	 */
	static function get_groups_by_permission($codes) {
		if(!is_array($codes)) $codes = array($codes);
		
		$SQLa_codes = Convert::raw2sql($codes);
		$SQL_codes = join("','", $SQLa_codes);
		
		// Via Roles are groups that have the permission via a role
		return DataObject::get('Group',
			"\"PermissionRoleCode\".\"Code\" IN ('$SQL_codes') OR \"Permission\".\"Code\" IN ('$SQL_codes')",
			"",
			"LEFT JOIN \"Permission\" ON \"Permission\".\"GroupID\" = \"Group\".\"ID\"
			LEFT JOIN \"Group_Roles\" ON \"Group_Roles\".\"GroupID\" = \"Group\".\"ID\"
			LEFT JOIN \"PermissionRole\" ON \"Group_Roles\".\"PermissionRoleID\" = \"PermissionRole\".\"ID\"
			LEFT JOIN \"PermissionRoleCode\" ON \"PermissionRoleCode\".\"RoleID\" = \"PermissionRole\".\"ID\"");
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
						if (!isset($v['category'])) user_error("The permission $k must have a category key", E_USER_WARNING);
						if (!isset($v['name'])) user_error("The permission $k must have a name key", E_USER_WARNING);
						
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
	static function sort_permissions($a, $b) {
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
	 * @param $code string - the permissions code
	 * @return void
	 */
	static function add_to_hidden_permissions($code){
		self::$hidden_permissions[] = $code;
	}
	
	/**
	 * remove a permission represented by the $code from the {@link slef::$hidden_permissions} list
	 *
	 * @param $code string - the permissions code
	 * @return void
	 */
	static function remove_from_hidden_permissions($code){
		self::$hidden_permissions = array_diff(self::$hidden_permissions, array($code));
	}

	/**
	 * Declare an array of permissions for the system.
	 *
	 * Permissions can be grouped by nesting arrays. Scalar values are always
	 * treated as permissions.
	 *
	 * @param array $permArray A (possibly nested) array of permissions to
	 *                         declare for the system.
	 */
	static function declare_permissions($permArray) {
		if(is_array(self::$declared_permissions)) {
			self::$declared_permissions =
				array_merge_recursive(self::$declared_permissions, $permArray);
		}
		else {
			self::$declared_permissions = $permArray;
		}
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
	protected static function traverse_declared_permissions($declared,
																													&$list) {
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
}


/**
 * Permission_Group class
 *
 * This class is used to group permissions together for showing on an
 * interface.
 * @package sapphire
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

?>
