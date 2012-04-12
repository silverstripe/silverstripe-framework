<?php
/**
 * Simple controller that the installer uses to test that URL rewriting is working.
 * @package framework
 * @subpackage testing
 */
class InstallerTest extends Controller {
	
	static $allowed_actions = array(
		'testrewrite'
	);

	function testrewrite() {
		echo "OK";
	}
}

