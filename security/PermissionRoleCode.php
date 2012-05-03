<?php
/**
 * A PermissionRoleCode represents a single permission code assigned to a {@link PermissionRole}.
 * 
 * @package framework
 * @subpackage security
 */
class PermissionRoleCode extends DataObject {
	static $db = array(
		"Code" => "Varchar",
	);
	
	static $has_one = array(
		"Role" => "PermissionRole",
	);
}
