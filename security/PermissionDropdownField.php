<?php
/**
 * Special kind of dropdown field that has all permission codes as its dropdown source.
 * Note: This would ordinarily be overkill; the main reason we have it is that TableField doesn't let you specify a dropdown source;
 * only a classname
 * @package sapphire
 * @subpackage security
 */
class PermissionDropdownField extends DropdownField {
	function __construct($name, $title = "") {
		parent::__construct($name, $title, Permission::get_codes(true)); 
	}
}