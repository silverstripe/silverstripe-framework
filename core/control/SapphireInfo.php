<?php
/**
 * Returns information about the current site instance.
 * @package sapphire
 * @subpackage control
 */
class SapphireInfo extends Controller {
	function Version() {
		$sapphireVersionFile = file_get_contents('../sapphire/silverstripe_version');

		if(strstr($sapphireVersionFile, "/sapphire/trunk")) {
			$sapphireVersion = "trunk";
		} else {
			preg_match("/sapphire\/(?:(?:branches)|(?:tags))(?:\/rc)?\/([A-Za-z0-9._-]+)\/silverstripe_version/", $sapphireVersionFile, $matches);
			$sapphireVersion = $matches[1];
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