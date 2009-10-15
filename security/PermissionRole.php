<?php

/**
 * A PermissionRole represents a collection of permission codes that can be applied to groups.
 * 
 * Because permission codes are very granular, this lets website administrators create more
 * business-oriented units of access control - Roles - and assign those to groups.
 */
class PermissionRole extends DataObject {
	static $db = array(
		"Title" => "Varchar",
	);
	
	static $has_many = array(
		"Codes" => "PermissionRoleCode",
	);
	
	static $belongs_many_many = array(
		"Groups" => "Group",
	);
}