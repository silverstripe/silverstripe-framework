<?php
/**
 * Tests for the {@link SS_ClassManifest} class.
 *
 * @package    sapphire
 * @subpackage tests
 */
class ClassLoaderTest extends SapphireTest {

	protected $base;
	protected $manifest;
	
	function setUp() {
		parent::setUp();
		
		$this->baseManifest1 = dirname(__FILE__) . '/fixtures/classmanifest';
		$this->baseManifest2 = dirname(__FILE__) . '/fixtures/classmanifest_other';
		$this->testManifest1 = new SS_ClassManifest($this->baseManifest1, false, true, false);
		$this->testManifest2 = new SS_ClassManifest($this->baseManifest2, false, true, false);
	}

	function testExclusive() {
		$loader = new SS_ClassLoader();
		
		$loader->pushManifest($this->testManifest1);
		$this->assertTrue((bool)$loader->getItemPath('ClassA'));
		$this->assertFalse((bool)$loader->getItemPath('OtherClassA'));
		
		$loader->pushManifest($this->testManifest2);
		$this->assertFalse((bool)$loader->getItemPath('ClassA'));
		$this->assertTrue((bool)$loader->getItemPath('OtherClassA'));
		
		$loader->popManifest();
		$loader->pushManifest($this->testManifest2, false);
		$this->assertTrue((bool)$loader->getItemPath('ClassA'));
		$this->assertTrue((bool)$loader->getItemPath('OtherClassA'));
	}

	function testGetItemPath() {
		$ds = DIRECTORY_SEPARATOR;
		$loader = new SS_ClassLoader();
		
		$loader->pushManifest($this->testManifest1);
		$this->assertEquals(
			$this->baseManifest1 . $ds . 'module' . $ds . 'classes' . $ds . 'ClassA.php',
			$loader->getItemPath('ClassA')
		);
		$this->assertEquals(
			false,
			$loader->getItemPath('UnknownClass')
		);
		$this->assertEquals(
			false,
			$loader->getItemPath('OtherClassA')
		);
		
		$loader->pushManifest($this->testManifest2);
		$this->assertEquals(
			false,
			$loader->getItemPath('ClassA')
		);
		$this->assertEquals(
			false,
			$loader->getItemPath('UnknownClass')
		);
		$this->assertEquals(
			$this->baseManifest2 . $ds . 'module' . $ds . 'classes' . $ds . 'OtherClassA.php',
			$loader->getItemPath('OtherClassA')
		);
	}
}