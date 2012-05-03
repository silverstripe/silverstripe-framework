<?php
/**
 * Used to let classes provide new permission codes.
 * Every implementor of PermissionProvider is accessed and providePermissions() called to get the full list of permission codes.
 * @package framework
 * @subpackage security
 */
interface PermissionProvider {
	/**
	 * Return a map of permission codes to add to the dropdown shown in the Security section of the CMS.
	 * array(
	 *   'VIEW_SITE' => 'View the site',
	 * );
	 */
	function providePermissions();	
}

