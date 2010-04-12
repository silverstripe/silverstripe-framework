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
			// A typical Windows location for where sites are stored on IIS
			$this->assertEquals(getTempFolder('C:\\inetpub\\wwwroot\\silverstripe'), getSysTempDir() . '/silverstripe-cacheC--inetpub-wwwroot-silverstripe');
			
			// A typical Mac OS X location for where sites are stored
			$this->assertEquals(getTempFolder('/Users/joebloggs/Sites/silverstripe'), getSysTempDir() . '/silverstripe-cache-Users-joebloggs-Sites-silverstripe');
			
			// A typical Linux location for where sites are stored
			$this->assertEquals(getTempFolder('/var/www/silverstripe'), getSysTempDir() . '/silverstripe-cache-var-www-silverstripe');
		}
	}

}