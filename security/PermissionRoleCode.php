<?php
/**
 * A PermissionRoleCode represents a single permission code assigned to a {@link PermissionRole}.
 *
 * @package framework
 * @subpackage security
 *
 * @property string Code
 *
 * @property int RoleID
 *
 * @method PermissionRole Role()
 */
class PermissionRoleCode extends DataObject {
	private static $db = array(
		"Code" => "Varchar",
	);

	private static $has_one = array(
		"Role" => "PermissionRole",
	);

	protected function validate() {
		$result = parent::validate();

		// Check that new code doesn't increase privileges, unless an admin is editing.
		$privilegedCodes = Config::inst()->get('Permission', 'privileged_permissions');
		if(
			$this->Code
			&& in_array($this->Code, $privilegedCodes)
			&& !Permission::check('ADMIN')
		) {
			$result->error(sprintf(
				_t(
					'PermissionRoleCode.PermsError',
					'Can\'t assign code "%s" with privileged permissions (requires ADMIN access)'
				),
				$this->Code
			));
		}

		return $result;
	}

	public function canCreate($member = null) {
		return Permission::check('APPLY_ROLES', 'any', $member);
	}

	public function canEdit($member = null) {
		return Permission::check('APPLY_ROLES', 'any', $member);
	}

	public function canDelete($member = null) {
		return Permission::check('APPLY_ROLES', 'any', $member);
	}
}
