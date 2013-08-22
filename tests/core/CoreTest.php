<?php
/**
 * Tests for the core of SilverStripe, such as how the temporary
 * directory is determined throughout the framework.
 * 
 * @package framework
 * @subpackage tests
 */
class CoreTest extends SapphireTest {

	protected $tempPath;

	public function setUp() {
		parent::setUp();
		$this->tempPath = Director::baseFolder() . '/silverstripe-cache';
	}

	public function testGetTempPathInProject() {
		$user = getTempFolderUsername();

		if(file_exists($this->tempPath)) {
			$this->assertEquals(getTempFolder(BASE_PATH), $this->tempPath . '/' . $user);
		} else {
			$user = getTempFolderUsername();

			// A typical Windows location for where sites are stored on IIS
			$this->assertEquals(sys_get_temp_dir() . 
				'/silverstripe-cacheC--inetpub-wwwroot-silverstripe-test-project/' . $user,
				getTempFolder('C:\\inetpub\\wwwroot\\silverstripe-test-project'));

			// A typical Mac OS X location for where sites are stored
			$this->assertEquals(sys_get_temp_dir() . 
				'/silverstripe-cache-Users-joebloggs-Sites-silverstripe-test-project/' . $user,
				getTempFolder('/Users/joebloggs/Sites/silverstripe-test-project'));

			// A typical Linux location for where sites are stored
			$this->assertEquals(sys_get_temp_dir() . '/silverstripe-cache-var-www-silverstripe-test-project/' . $user,
				getTempFolder('/var/www/silverstripe-test-project'));
		}
	}

	public function tearDown() {
		parent::tearDown();
		$user = getTempFolderUsername();
		if(file_exists(sys_get_temp_dir() . '/silverstripe-cacheC--inetpub-wwwroot-silverstripe-test-project')) {
			rmdir(sys_get_temp_dir() . '/silverstripe-cacheC--inetpub-wwwroot-silverstripe-test-project/' . $user);
			rmdir(sys_get_temp_dir() . '/silverstripe-cacheC--inetpub-wwwroot-silverstripe-test-project');
		}
		if(file_exists(sys_get_temp_dir() . '/silverstripe-cache-Users-joebloggs-Sites-silverstripe-test-project')) {
			rmdir(sys_get_temp_dir() . '/silverstripe-cache-Users-joebloggs-Sites-silverstripe-test-project/' . $user);
			rmdir(sys_get_temp_dir() . '/silverstripe-cache-Users-joebloggs-Sites-silverstripe-test-project');
		}
		if(file_exists(sys_get_temp_dir() . '/silverstripe-cache-var-www-silverstripe-test-project')) {
			rmdir(sys_get_temp_dir() . '/silverstripe-cache-var-www-silverstripe-test-project/' . $user);
			rmdir(sys_get_temp_dir() . '/silverstripe-cache-var-www-silverstripe-test-project');
		}
	}

}
