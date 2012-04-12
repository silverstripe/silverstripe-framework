<?php
/**
 * Tests for the {@link ManifestFileFinder} class.
 *
 * @package framework
 * @subpackage tests
 */
class ManifestFileFinderTest extends SapphireTest {

	protected $base;

	public function __construct() {
		$this->base = dirname(__FILE__) . '/fixtures/manifestfilefinder';
		parent::__construct();
	}

	public function assertFinderFinds($finder, $expect, $message = null) {
		$found = $finder->find($this->base);

		foreach ($expect as $k => $file) {
			$expect[$k] = "{$this->base}/$file";
		}

		sort($expect);
		sort($found);

		$this->assertEquals($expect, $found, $message);
	}

	public function testBasicOperation() {
		$finder = new ManifestFileFinder();
		$finder->setOption('name_regex', '/\.txt$/');

		$this->assertFinderFinds($finder, array(
			'module/module.txt'
		));
	}

	public function testIgnoreTests() {
		$finder = new ManifestFileFinder();
		$finder->setOption('name_regex', '/\.txt$/');
		$finder->setOption('ignore_tests', false);

		$this->assertFinderFinds($finder, array(
			'module/module.txt',
			'module/tests/tests.txt'
		));
	}

	public function testIncludeThemes() {
		$finder = new ManifestFileFinder();
		$finder->setOption('name_regex', '/\.txt$/');
		$finder->setOption('include_themes', true);

		$this->assertFinderFinds($finder, array(
			'module/module.txt',
			'themes/themes.txt'
		));
	}

}
