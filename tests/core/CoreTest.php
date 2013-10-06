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
		$this->tempPath = Director::baseFolder() . DIRECTORY_SEPARATOR . 'silverstripe-cache';
	}

	public function testGetTempPathInProject() {
		$user = getTempFolderUsername();

		if(file_exists($this->tempPath)) {
			$this->assertEquals(getTempFolder(BASE_PATH), $this->tempPath . DIRECTORY_SEPARATOR . $user);
		} else {
			$user = getTempFolderUsername();

			// A typical Windows location for where sites are stored on IIS
			$this->assertEquals(sys_get_temp_dir() . DIRECTORY_SEPARATOR .
				'silverstripe-cacheC--inetpub-wwwroot-silverstripe-test-project' . DIRECTORY_SEPARATOR . $user,
				getTempFolder('C:\\inetpub\\wwwroot\\silverstripe-test-project'));

			// A typical Mac OS X location for where sites are stored
			$this->assertEquals(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 
				'silverstripe-cache-Users-joebloggs-Sites-silverstripe-test-project' . DIRECTORY_SEPARATOR . $user,
				getTempFolder('/Users/joebloggs/Sites/silverstripe-test-project'));

			// A typical Linux location for where sites are stored
			$this->assertEquals(sys_get_temp_dir() . DIRECTORY_SEPARATOR .
				'silverstripe-cache-var-www-silverstripe-test-project' . DIRECTORY_SEPARATOR . $user,
				getTempFolder('/var/www/silverstripe-test-project'));
		}
	}

	public function tearDown() {
		parent::tearDown();
		$user = getTempFolderUsername();
		foreach(array(
			'silverstripe-cacheC--inetpub-wwwroot-silverstripe-test-project',
			'silverstripe-cache-Users-joebloggs-Sites-silverstripe-test-project',
			'silverstripe-cache-var-www-silverstripe-test-project'
		) as $dir) {
			$path = sys_get_temp_dir().DIRECTORY_SEPARATOR.$dir;
			if(file_exists($path)) {
				rmdir($path . DIRECTORY_SEPARATOR . $user);
				rmdir($path);
		}
		}
		}

}
