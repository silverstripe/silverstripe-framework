<?php

/**
 * Provides access to permission management within the framework
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class PermissionService {
	
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
	 * Permissions cache.  The format is a map, where the keys are member IDs, and the values are
	 * arrays of permission codes.
	 */
	private static $cache_permissions = array();
	
	/**
	 * Set to false to prevent the 'ADMIN' permission from implying all
	 * permissions in the system
	 *
	 * @var bool
	 */
	public $adminImpliesAll = true;
	
		/**
	 * @var $strict_checking Boolean Method to globally disable "strict" checking,
	 * which means a permission will be granted if the key does not exist at all.
	 */
	public $strictChecking = true;

	
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
	public function check($code, $arg = "any", $member = null, $strict = true) {
		if(!$member) {
			if(!Member::currentUserID()) {
				return false;
			}
			$member = Member::currentUserID();
		}

		return $this->checkMember($member, $code, $arg, $strict);
	}
	
	
	/**
	 * Flush the permission cache, for example if you have edited group membership or a permission record.
	 * @todo Call this whenever Group_Members is added to or removed from
	 */
	public function flushPermissionCache() {
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
	public function checkMember($member, $code, $arg = "any", $strict = true) {
		if(!$member) {
			$memberID = $member = Member::currentUserID();
		} else {
			$memberID = (is_object($member)) ? $member->ID : $member; 
		}
		
		if($arg == 'any') {
			// Cache the permissions in memory
			if(!isset(self::$cache_permissions[$memberID])) {
				self::$cache_permissions[$memberID] = $this->permissionsForMember($memberID);
			}
			
			// If $admin_implies_all was false then this would be inefficient, but that's an edge
			// case and this keeps the code simpler
			if(!is_array($code)) $code = array($code);
			if($this->adminImpliesAll) $code[] = "ADMIN";

			// Multiple $code values - return true if at least one matches, ie, intersection exists
			return (bool)array_intersect($code, self::$cache_permissions[$memberID]);
		} 

		// The following code should only be used if you're not using the "any" arg.  This is kind
		// of obselete functionality and could possibly be deprecated.

		$groupList = $this->groupList($memberID);
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
		
		$adminFilter = ($this->adminImpliesAll) ?  ",'ADMIN'" : '';

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
		if(!$this->strictChecking || !$strict) {
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
	public function permissionsForMember($memberID) {
		$groupList = $this->groupList($memberID);
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
	public function groupList($memberID = null) {
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
	public function grant($groupID, $code, $arg = "any") {
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
	public function deny($groupID, $code, $arg = "any") {
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
	public function getMembersByPermission($code) {
		$toplevelGroups = $this->getGroupsByPermission($code);
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
	public function getGroupsByPermission($codes) {
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
	public function getCodes($grouped = true) {
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
		if(!$this->check("ADMIN")) unset($allCodes['ADMIN']);
		
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
}
