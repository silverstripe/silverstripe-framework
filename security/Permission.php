<?php

class Permission extends DataObject {
	static $db = array(
		"Code" => "Varchar",
		"Arg" => "Int",	
	);
	static $has_one = array(
		"Group" => "Group",
	);
	static $indexes = array(
		"Code" => true,
	);
	
	/**
	 * @var $strict_checking Boolean Method to globally disable "strict" checking,
	 * which means a permission will be granted if the key does not exist at all.
	 */
	static $strict_checking = true;
	
	/**
	 * Check that the current member has the given permission
	 * 
	 * @param $code string|array Either a list of codes or a single code
	 * @param $arg string
	 * @param $memberID integer
	 * @param $strict Boolean
	 * @return Integer
	 */
	static function check($code, $arg = "any", $memberID = null, $strict = true) {
		if(!$memberID) {
			if(!Member::currentUser()) {
				return false;
			}
			$memberID = Member::currentUserID();
		}
		
		return self::checkMember($memberID, $code, $arg);
	}
	
	/**
	 * Check that the given member has the given permission
	 * 
	 * @param memberID The ID of the member to check.  Leave blank for the current member
	 * @param $code string
	 * @param $arg string
	 * @param $strict Boolean
	 * @return Integer The ID of the Permission record if the permission exists; null otherwise
	 */
	static function checkMember($memberID, $code, $arg = "any", $strict = true) {
		// Group component
		$groupList = self::groupList($memberID);
		if($groupList) {
			$groupCSV = implode(", ", $groupList);
			// Arg component
			switch($arg) {
				case "any": $argClause = "";break;
				case "all": $argClause = " AND Arg = -1"; break;
				default: 
					if(is_numeric($arg)) $argClause = "AND Arg IN (-1, $arg) ";
					else use_error("Permission::checkMember: bad arg '$arg'", E_USER_ERROR);
			}
			
			if(is_array($code)) $SQL_codeList = "'" . implode("', '", Convert::raw2sql($code)) . "'";
			else $SQL_codeList = "'" . Convert::raw2sql($code) . "'";
			
			if(!self::$strict_checking || !$strict) {
				$hasPermission = DB::query("
					SELECT COUNT(*) 
					FROM Permission 
					WHERE Code IN ('$SQL_codeList') 
				")->value();
				if(!$hasPermission) return true;
			}
			
			// Raw SQL for efficiency
			return DB::query("SELECT ID FROM Permission WHERE Code IN ($SQL_codeList, 'ADMIN') AND GroupID IN ($groupCSV) $argClause")->value();
		}
	}
	
	
	/**
	 * Get the list of groups that the given member belongs to.
	 * Call without an argument to get the groups that the current member belongs to.  In this case, the results will be session-cached
	 */
	static function groupList($memberID = null) {
		// Default to current member, with session-caching
		if(!$memberID) {
			$member = Member::currentUser();
			if($member && isset($_SESSION['Permission_groupList'][$member->ID])) return $_SESSION['Permission_groupList'][$member->ID];
		} else {
			$member = DataObject::get_by_id("Member", $memberID);
		}
		
		if($member) {
			// Build a list of the IDs of the groups.  Most of the heavy lifting is done by Member::Groups
			// NOTE: This isn't effecient; but it's called once per session so it's a low priority to fix.
			$groups = $member->Groups();
			if($groups) foreach($groups as $group) $groupList[] = $group->ID;
			
			// Session caching		
			if(!$memberID) {
				$_SESSION['Permission_groupList'][$member->ID] = $groupList;
			}
			
			return isset($groupList) ? $groupList : null;
		}
	}

	/**
	 * Grant the given permission code/arg to the given group
	 * @returns The new permission object
	 */
	static function grant($groupID, $code, $arg = "any") {
		$perm = new Permission();
		$perm->GroupID = $groupID;
		$perm->Code = $code;

		// Arg component
		switch($arg) {
			case "any": break;
			case "all": $perm->Arg = -1;
			default: 
				if(is_numeric($arg)) $perm->Arg = $arg;
				else use_error("Permission::checkMember: bad arg '$arg'", E_USER_ERROR);
		}

		$perm->write();
		return $perm;
	}
	
	function requireDefaultRecords() {
		parent::requireDefaultRecords();
		
		// Add default content if blank
		if(!DB::query("SELECT ID FROM Permission")->value()) {
			$admins = DB::query("SELECT ID FROM `Group` WHERE CanCMSAdmin = 1")->column();
			if(isset($admins)) {
				foreach($admins as $admin) Permission::grant($admin, "ADMIN");
			}
			
			$authors = DB::query("SELECT ID FROM `Group` WHERE CanCMS = 1")->column();
			if(isset($authors)) {
				foreach($authors as $author) {
					Permission::grant($author, "CMS_ACCESS_CMSMain");
					Permission::grant($author, "CMS_ACCESS_AssetAdmin");
					Permission::grant($author, "CMS_ACCESS_NewsletterAdmin");
					Permission::grant($author, "CMS_ACCESS_ReportAdmin");
				}
			}
			
		}
	}
	
	/**
	 * Returns all members for a specific permission.
	 * 
	 * @param $code String|array Either a single permission code, or a list of permission codes
	 * @return DataObjectSet
	 */
	static function get_members_by_permission($code) {
		$groupIDs = array();
        
        if(is_array($code)) $SQL_filter = "Permission.Code IN ('" . implode("','", Convert::raw2sql($code)) . "')";
        else $SQL_filter = "Permission.Code = '" . Convert::raw2sql($code) . "'";
		
		$toplevelGroups = DataObject::get(
			'Group', 
			$SQL_filter, // filter
			null, // limit
			"LEFT JOIN `Permission` ON `Group`.`ID` = `Permission`.`GroupID`" // join 
		);
		if(!$toplevelGroups) return false;
		foreach($toplevelGroups as $group) {
			$familyIDs = $group->collateFamilyIDs();
			if(is_array($familyIDs)) {
				$groupIDs = array_merge($groupIDs, array_values($familyIDs));
			}
		}
		if(!count($groupIDs)) return false;
		
		$members = DataObject::get(
			Object::getCustomClass('Member'),
			$_filter = "`Group`.ID IN (" . implode(",",$groupIDs) . ")",
			$_sort = "",
			$_join = "LEFT JOIN `Group_Members` ON `Member`.`ID` = `Group_Members`.`MemberID` " . 
				"LEFT JOIN `Group` ON `Group_Members`.`GroupID` = `Group`.`ID` "
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
		
		return DataObject::get(
			'Group',
			"Permission.Code IN ('$SQL_codes')",
			"",
			"LEFT JOIN Permission ON Group.ID = Permission.GroupID"
		);
	}

	static function get_codes($blankItemText = null) {
		$classes = ClassInfo::implementorsOf('PermissionProvider');
		
		$allCodes = array();
		if($blankItemText) $allCodes[''] = ($blankItemText === true) ? '(select)' : $blankItemText;
		$allCodes['ADMIN'] = 'Full administrative rights';
		
		foreach($classes as $class) {
			$SNG = singleton($class);
			$someCodes = $SNG->providePermissions();
			if($someCodes) foreach($someCodes as $k => $v) {
				$allCodes[$k] = $v;
			}
		}

		$otherPerms = DB::query("SELECT DISTINCT Code From Permission")->column();
		foreach($otherPerms as $otherPerm) {
			if(!array_key_exists($otherPerm, $allCodes)) $allCodes[$otherPerm] = $otherPerm;
		}
		
		asort($allCodes);
		return $allCodes;
	}
	
	/*
	 * Controller action to list the codes available
	 */
	function listcodes() {
		if(!Permission::check('ADMIN')) Security::permissionFailure();
		
		echo "<h1>The following permission codes are defined</h1>";
		$codes = self::get_codes();
		echo "<pre>";
		print_r($codes);
	}
}

?>