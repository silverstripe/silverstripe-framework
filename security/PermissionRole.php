<?php
/**
 * A PermissionRole represents a collection of permission codes that can be applied to groups.
 * 
 * Because permission codes are very granular, this lets website administrators create more
 * business-oriented units of access control - Roles - and assign those to groups.
 * 
 * If the <b>OnlyAdminCanApply</b> property is set to TRUE, the role can only be assigned
 * to new groups by a user with ADMIN privileges. This is a simple way to prevent users
 * with access to {@link SecurityAdmin} (but no ADMIN privileges) to get themselves ADMIN access
 * (which might be implied by certain roles).
 * 
 * @package framework
 * @subpackage security
 */
class PermissionRole extends DataObject {
	static $db = array(
		"Title" => "Varchar",
		"OnlyAdminCanApply" => "Boolean"
	);
	
	static $has_many = array(
		"Codes" => "PermissionRoleCode",
	);
	
	static $belongs_many_many = array(
		"Groups" => "Group",
	);
	
	static $default_sort = '"Title"';
	
	static $singular_name = 'Role';

	static $plural_name = 'Roles';
	
	function getCMSFields() {
		$fields = parent::getCMSFields();
		
		$fields->removeFieldFromTab('Root', 'Codes');
		$fields->removeFieldFromTab('Root', 'Groups');
		
		$fields->addFieldToTab(
			'Root.Main', 
			$permissionField = new PermissionCheckboxSetField(
				'Codes',
				singleton('Permission')->i18n_plural_name(),
				'PermissionRoleCode',
				'RoleID'
			)
		);
		$permissionField->setHiddenPermissions(SecurityAdmin::$hidden_permissions);
		
		return $fields;
	}
	
	function onAfterDelete() {
		parent::onAfterDelete();
		
		// Delete associated permission codes
		$codes = $this->Codes();
		foreach ( $codes as $code ) {
			$code->delete();
		}
	}

	function fieldLabels($includerelations = true) {
		$labels = parent::fieldLabels($includerelations);
		$labels['Title'] = _t('PermissionRole.Title', 'Title');
		$labels['OnlyAdminCanApply'] = _t(
			'PermissionRole.OnlyAdminCanApply', 
			'Only admin can apply',
			'Checkbox to limit which user can apply this role'
		);
		
		return $labels;
	}
}
