<?php

use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
/**
 * Returns information about the current site instance.
 * @package framework
 * @subpackage control
 */
class SapphireInfo extends Controller {
	private static $allowed_actions = array(
		'baseurl',
		'version',
		'environmenttype',
	);

	protected function init() {
		parent::init();
		if(!Director::is_cli() && !Permission::check('ADMIN')) return Security::permissionFailure();
	}

	public function Version() {
		$sapphireVersion = file_get_contents(FRAMEWORK_PATH . '/silverstripe_version');
		if(!$sapphireVersion) $sapphireVersion = _t('LeftAndMain.VersionUnknown', 'unknown');
		return $sapphireVersion;
	}

	public function EnvironmentType() {
		if(Director::isLive()) return "live";
		else if(Director::isTest()) return "test";
		else return "dev";
	}

	public function BaseURL() {
		return Director::absoluteBaseURL();
	}
}
