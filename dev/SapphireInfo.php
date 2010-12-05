<?php
/**
 * Returns information about the current site instance.
 * @package sapphire
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
		$sapphireVersionFile = file_get_contents(BASE_PATH . '/sapphire/silverstripe_version');

		if(strstr($sapphireVersionFile, "/sapphire/trunk")) {
			$sapphireVersion = "trunk";
		} else {
			if(preg_match("/sapphire\/(?:(?:branches)|(?:tags))(?:\/rc)?\/([A-Za-z0-9._-]+)\/silverstripe_version/", $sapphireVersionFile, $matches)) {
				$sapphireVersion = $matches[1];
			} else {
				$sapphireVersion = "unknown";
			}
		}
		
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