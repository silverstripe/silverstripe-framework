<?php

use SilverStripe\Filesystem\Storage\AssetStore;
use SilverStripe\Filesystem\Storage\ProtectedFileController;

class ProtectedFileControllerTest extends FunctionalTest {

	protected static $fixture_file = 'FileTest.yml';

	public function setUp() {
		parent::setUp();

		// Set backend root to /ImageTest
		AssetStoreTest_SpyStore::activate('ProtectedFileControllerTest');

		// Create a test folders for each of the fixture references
		foreach (Folder::get() as $folder) {
			/** @var Folder $folder */
			$filePath = AssetStoreTest_SpyStore::getLocalPath($folder);
			\Filesystem::makeFolder($filePath);
		}

		// Create a test files for each of the fixture references
		foreach (File::get()->exclude('ClassName', 'Folder') as $file) {
			/** @var File $file */
			$path = AssetStoreTest_SpyStore::getLocalPath($file);
			\Filesystem::makeFolder(dirname($path));
			$fh = fopen($path, "w+");
			fwrite($fh, str_repeat('x', 1000000));
			fclose($fh);

			// Create variant for each file
			$this->getAssetStore()->setFromString(
				str_repeat('y', 100),
				$file->Filename,
				$file->Hash,
				'variant'
			);
		}
	}

	/**
	 * @dataProvider getFilenames
	 */
	public function testIsValidFilename($name, $isValid) {
		$controller = new ProtectedFileController();
		$this->assertEquals(
			$isValid,
			$controller->isValidFilename($name),
			"Assert filename \"$name\" is " . $isValid ? "valid" : "invalid"
		);
	}

	public function getFilenames() {
		return array(
			// Valid names
			array('name.jpg', true),
			array('parent/name.jpg', true),
			array('parent/name', true),
			array('parent\name.jpg', true),
			array('parent\name', true),
			array('name', true),

			// Invalid names
			array('.invalid/name.jpg', false),
			array('.invalid\name.jpg', false),
			array('.htaccess', false),
			array('test/.htaccess.jpg', false),
			array('name/.jpg', false),
			array('test\.htaccess.jpg', false),
			array('name\.jpg', false)
		);
	}

	/**
	 * Test that certain requests are denied
	 */
	public function testRequestDenied() {
		$result = $this->get('assets/.protected/file.jpg');
		$this->assertResponseEquals(400, null, $result);
	}

	/**
	 * Test that invalid files generate 404 response
	 */
	public function testFileNotFound() {
		$result = $this->get('assets/missing.jpg');
		$this->assertResponseEquals(404, null, $result);
	}

	/**
	 * Check public access to assets is available at the appropriate time
	 */
	public function testAccessControl() {
		$expectedContent = str_repeat('x', 1000000);
		$variantContent = str_repeat('y', 100);

		$result = $this->get('assets/55b443b601/FileTest.txt');
		$this->assertResponseEquals(200, $expectedContent, $result);
		$result = $this->get('assets/55b443b601/FileTest__variant.txt');
		$this->assertResponseEquals(200, $variantContent, $result);

		// Make this file protected
		$this->getAssetStore()->protect(
			'FileTest.txt',
			'55b443b60176235ef09801153cca4e6da7494a0c'
		);

		// Should now return explicitly denied errors
		$result = $this->get('assets/55b443b601/FileTest.txt');
		$this->assertResponseEquals(403, null, $result);
		$result = $this->get('assets/55b443b601/FileTest__variant.txt');
		$this->assertResponseEquals(403, null, $result);

		// Other assets remain available
		$result = $this->get('assets/55b443b601/FileTest.pdf');
		$this->assertResponseEquals(200, $expectedContent, $result);
		$result = $this->get('assets/55b443b601/FileTest__variant.pdf');
		$this->assertResponseEquals(200, $variantContent, $result);

		// granting access will allow access
		$this->getAssetStore()->grant(
			'FileTest.txt',
			'55b443b60176235ef09801153cca4e6da7494a0c'
		);
		$result = $this->get('assets/55b443b601/FileTest.txt');
		$this->assertResponseEquals(200, $expectedContent, $result);
		$result = $this->get('assets/55b443b601/FileTest__variant.txt');
		$this->assertResponseEquals(200, $variantContent, $result);

		// Revoking access will remove access again
		$this->getAssetStore()->revoke(
			'FileTest.txt',
			'55b443b60176235ef09801153cca4e6da7494a0c'
		);
		$result = $this->get('assets/55b443b601/FileTest.txt');
		$this->assertResponseEquals(403, null, $result);
		$result = $this->get('assets/55b443b601/FileTest__variant.txt');
		$this->assertResponseEquals(403, null, $result);

		// Moving file back to public store restores access
		$this->getAssetStore()->publish(
			'FileTest.txt',
			'55b443b60176235ef09801153cca4e6da7494a0c'
		);
		$result = $this->get('assets/55b443b601/FileTest.txt');
		$this->assertResponseEquals(200, $expectedContent, $result);
		$result = $this->get('assets/55b443b601/FileTest__variant.txt');
		$this->assertResponseEquals(200, $variantContent, $result);

		// Deleting the file will make the response 404
		$this->getAssetStore()->delete(
			'FileTest.txt',
			'55b443b60176235ef09801153cca4e6da7494a0c'
		);
		$result = $this->get('assets/55b443b601/FileTest.txt');
		$this->assertResponseEquals(404, null, $result);
		$result = $this->get('assets/55b443b601/FileTest__variant.txt');
		$this->assertResponseEquals(404, null, $result);
	}

	/**
	 * Test that access to folders is not permitted
	 */
	public function testFolders() {
		$result = $this->get('assets/55b443b601');
		$this->assertResponseEquals(403, null, $result);

		$result = $this->get('assets/FileTest-subfolder');
		$this->assertResponseEquals(403, null, $result);

		$result = $this->get('assets');
		$this->assertResponseEquals(403, null, $result);
	}

	/**
	 * @return AssetStore
	 */
	protected function getAssetStore() {
		return singleton('AssetStore');
	}

	/**
	 * Assert that a response matches the given parameters
	 *
	 * @param int $code HTTP code
	 * @param string $body Body expected for 200 responses
	 * @param SS_HTTPResponse $response
	 */
	protected function assertResponseEquals($code, $body, SS_HTTPResponse $response) {
		$this->assertEquals($code, $response->getStatusCode());
		if($code === 200) {
			$this->assertFalse($response->isError());
			$this->assertEquals($body, $response->getBody());
			$this->assertEquals('text/plain', $response->getHeader('Content-Type'));
		} else {
			$this->assertTrue($response->isError());
		}
	}

}