<?php
/**
 * A PermissionRoleCode represents a single permission code assigned to a {@link PermissionRole}.
 * 
 * @package framework
 * @subpackage security
 */
class PermissionRoleCode extends DataObject {
	private static $db = array(
		"Code" => "Varchar",
	);
	
	private static $has_one = array(
		"Role" => "PermissionRole",
	);
}
