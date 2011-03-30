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
		$this->tempPath = Director::baseFolder() . '/silverstripe-cache';
	}

	public function testGetTempPathInProject() {
		if(file_exists($this->tempPath)) {
			$this->assertEquals(getTempFolder(), $this->tempPath);
		} else {
			// A typical Windows location for where sites are stored on IIS
			$this->assertEquals(getTempFolder('C:\\inetpub\\wwwroot\\silverstripe-test-project'), getSysTempDir() . '/silverstripe-cacheC--inetpub-wwwroot-silverstripe-test-project');

			// A typical Mac OS X location for where sites are stored
			$this->assertEquals(getTempFolder('/Users/joebloggs/Sites/silverstripe-test-project'), getSysTempDir() . '/silverstripe-cache-Users-joebloggs-Sites-silverstripe-test-project');

			// A typical Linux location for where sites are stored
			$this->assertEquals(getTempFolder('/var/www/silverstripe-test-project'), getSysTempDir() . '/silverstripe-cache-var-www-silverstripe-test-project');
		}
	}

	public function tearDown() {
		parent::tearDown();
		if(file_exists(getSysTempDir() . '/silverstripe-cacheC--inetpub-wwwroot-silverstripe-test-project')) {
			rmdir(getSysTempDir() . '/silverstripe-cacheC--inetpub-wwwroot-silverstripe-test-project');
		}
		if(file_exists(getSysTempDir() . '/silverstripe-cache-Users-joebloggs-Sites-silverstripe-test-project')) {
			rmdir(getSysTempDir() . '/silverstripe-cache-Users-joebloggs-Sites-silverstripe-test-project');
		}
		if(file_exists(getSysTempDir() . '/silverstripe-cache-var-www-silverstripe-test-project')) {
			rmdir(getSysTempDir() . '/silverstripe-cache-var-www-silverstripe-test-project');
		}
	}

}