<?php

/**
 * @package sapphire
 * @subpackage misc
 */

/**
 * Simple controller that the installer uses to test that URL rewriting is working.
 * @package sapphire
 * @subpackage misc
 */
class InstallerTest extends Controller {

	function testrewrite() {
		echo "OK";
	}
}

?>
