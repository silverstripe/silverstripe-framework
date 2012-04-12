<?php
/**
 * Tests for the {@link SS_FileFinder} class.
 *
 * @package framework
 * @subpackage tests
 */
class FileFinderTest extends SapphireTest {

	protected $base;

	public function __construct() {
		$this->base = dirname(__FILE__) . '/fixtures/filefinder';
		parent::__construct();
	}

	public function testBasicOperation() {
		$this->assertFinderFinds(new SS_FileFinder(), array(
			'file1.txt',
			'file2.txt',
			'dir1/dir1file1.txt',
			'dir1/dir1file2.txt',
			'dir1/dir2/dir2file1.txt',
			'dir1/dir2/dir3/dir3file1.txt'
		));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidOptionThrowsException() {
		$finder = new SS_FileFinder();
		$finder->setOption('this_doesnt_exist', 'ok');
	}

	public function testFilenameRegex() {
		$finder = new SS_FileFinder();
		$finder->setOption('name_regex', '/file2\.txt$/');

		$this->assertFinderFinds(
			$finder,
			array(
				'file2.txt',
				'dir1/dir1file2.txt'),
			'The finder only returns files matching the name regex.');
	}

	public function testIgnoreFiles() {
		$finder = new SS_FileFinder();
		$finder->setOption('ignore_files', array('file1.txt', 'dir1file1.txt', 'dir2file1.txt'));

		$this->assertFinderFinds(
			$finder,
			array(
				'file2.txt',
				'dir1/dir1file2.txt',
				'dir1/dir2/dir3/dir3file1.txt'),
			'The finder ignores files with the basename in the ignore_files setting.');
	}

	public function testIgnoreDirs() {
		$finder = new SS_FileFinder();
		$finder->setOption('ignore_dirs', array('dir2'));

		$this->assertFinderFinds(
			$finder,
			array(
				'file1.txt',
				'file2.txt',
				'dir1/dir1file1.txt',
				'dir1/dir1file2.txt'),
			'The finder ignores directories in ignore_dirs.');
	}

	public function testMinDepth() {
		$finder = new SS_FileFinder();
		$finder->setOption('min_depth', 2);

		$this->assertFinderFinds(
			$finder,
			array(
				'dir1/dir2/dir2file1.txt',
				'dir1/dir2/dir3/dir3file1.txt'),
			'The finder respects the min depth setting.');
	}

	public function testMaxDepth() {
		$finder = new SS_FileFinder();
		$finder->setOption('max_depth', 1);

		$this->assertFinderFinds(
			$finder,
			array(
				'file1.txt',
				'file2.txt',
				'dir1/dir1file1.txt',
				'dir1/dir1file2.txt'),
			'The finder respects the max depth setting.');
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

}
