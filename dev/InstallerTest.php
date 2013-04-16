<?php
/**
 * Simple controller that the installer uses to test that URL rewriting is working.
 * @package framework
 * @subpackage testing
 */
class InstallerTest extends Controller {
	
	private static $allowed_actions = array(
		'testrewrite'
	);

	public function testrewrite() {
		echo "OK";
	}
}

