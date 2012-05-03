<?php
/**
 * Returns information about the current site instance.
 * @package framework
 * @subpackage control
 */
class SapphireInfo extends Controller {
	static $allowed_actions = array(
		'baseurl',
		'version',
		'environmenttype',
	);
	
	function init() {
		parent::init();
		if(!Director::is_cli() && !Permission::check('ADMIN')) return Security::permissionFailure();
	}
	
	function Version() {
		$sapphireVersion = file_get_contents(FRAMEWORK_PATH . '/silverstripe_version');
		if(!$sapphireVersion) $sapphireVersion = _t('LeftAndMain.VersionUnknown', 'unknown');
		return $sapphireVersion;
	}
	
	function EnvironmentType() {
		if(Director::isLive()) return "live";
		else if(Director::isTest()) return "test";
		else return "dev";
	}
	
	function BaseURL() {
		return Director::absoluteBaseURL();
	}
}
