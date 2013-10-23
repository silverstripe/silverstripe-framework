<?php
/**
 * Initialises the versioned stage when a request is made.
 *
 * @package framework
 * @subpackage control
 */
class VersionedRequestFilter {

	public function preRequest() {
		Versioned::choose_site_stage();
	}

	public function postRequest() {
	}

}
