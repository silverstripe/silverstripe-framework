<?php

/**
 * Returns information about the current site instance.
 */
class SapphireInfo extends Controller {
	function baseurl() {
		return Director::absoluteBaseUrl() . "\n";
	}
	
}