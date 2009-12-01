<?php

/**
 * Tests for the core of sapphire, such as how the temporary
 * directory is determined throughout the framework.
 * 
 * @package sapphire
 * @subpackage tests
 */
class CoreTest extends SapphireTest {

	protected $tempPath;

	public function setUp() {
		parent::setUp();
		$this->tempPath = $tempPath = Director::baseFolder() . '/silverstripe-cache';
	}

	public function testGetTempPathInProject() {
		if(file_exists($this->tempPath)) {
			$this->assertEquals(getTempFolder(), $this->tempPath);
		} else {
			// Store the original variable so we know what to change it back to
			$old = $_SERVER['SCRIPT_FILENAME'];
			
			// A typical Windows location for where sites are stored on IIS
			$_SERVER['SCRIPT_FILENAME'] = 'C:\\inetpub\\wwwroot\\silverstripe\\sapphire\\main.php';
			$this->assertEquals(getTempFolder(), getSysTempDir() . '/silverstripe-cacheC--inetpub-wwwroot-silverstripe');
			
			// A typical Mac OS X location for where sites are stored
			$_SERVER['SCRIPT_FILENAME'] = '/Users/joebloggs/Sites/silverstripe/sapphire/main.php';
			$this->assertEquals(getTempFolder(), getSysTempDir() . '/silverstripe-cache-Users-joebloggs-Sites-silverstripe');
			
			// A typical Linux location for where sites are stored
			$_SERVER['SCRIPT_FILENAME'] = '/var/www/silverstripe/sapphire/main.php';
			$this->assertEquals(getTempFolder(), getSysTempDir() . '/silverstripe-cache-var-www-silverstripe');
			
			// Restore the SCRIPT_FILENAME variable back to the original
			$_SERVER['SCRIPT_FILENAME'] = $old;
		}
	}

}