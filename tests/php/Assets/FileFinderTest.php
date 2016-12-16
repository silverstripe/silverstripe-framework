<?php

namespace SilverStripe\Assets\Tests;

use InvalidArgumentException;
use SilverStripe\Assets\FileFinder;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for the {@link SS_FileFinder} class.
 */
class FileFinderTest extends SapphireTest
{

    protected $base;

    public function __construct()
    {
        $this->base = __DIR__ . '/FileFinderTest';
        parent::__construct();
    }

    public function testBasicOperation()
    {
        $this->assertFinderFinds(
            new FileFinder(),
            array(
                'file1.txt',
                'file2.txt',
                'dir1/dir1file1.txt',
                'dir1/dir1file2.txt',
                'dir1/dir2/dir2file1.txt',
                'dir1/dir2/dir3/dir3file1.txt'
            )
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidOptionThrowsException()
    {
        $finder = new FileFinder();
        $finder->setOption('this_doesnt_exist', 'ok');
    }

    public function testFilenameRegex()
    {
        $finder = new FileFinder();
        $finder->setOption('name_regex', '/file2\.txt$/');

        $this->assertFinderFinds(
            $finder,
            array(
                'file2.txt',
                'dir1/dir1file2.txt'),
            'The finder only returns files matching the name regex.'
        );
    }

    public function testIgnoreFiles()
    {
        $finder = new FileFinder();
        $finder->setOption('ignore_files', array('file1.txt', 'dir1file1.txt', 'dir2file1.txt'));

        $this->assertFinderFinds(
            $finder,
            array(
                'file2.txt',
                'dir1/dir1file2.txt',
                'dir1/dir2/dir3/dir3file1.txt'),
            'The finder ignores files with the basename in the ignore_files setting.'
        );
    }

    public function testIgnoreDirs()
    {
        $finder = new FileFinder();
        $finder->setOption('ignore_dirs', array('dir2'));

        $this->assertFinderFinds(
            $finder,
            array(
                'file1.txt',
                'file2.txt',
                'dir1/dir1file1.txt',
                'dir1/dir1file2.txt'),
            'The finder ignores directories in ignore_dirs.'
        );
    }

    public function testMinDepth()
    {
        $finder = new FileFinder();
        $finder->setOption('min_depth', 2);

        $this->assertFinderFinds(
            $finder,
            array(
                'dir1/dir2/dir2file1.txt',
                'dir1/dir2/dir3/dir3file1.txt'
            ),
            'The finder respects the min depth setting.'
        );
    }

    public function testMaxDepth()
    {
        $finder = new FileFinder();
        $finder->setOption('max_depth', 1);

        $this->assertFinderFinds(
            $finder,
            array(
                'file1.txt',
                'file2.txt',
                'dir1/dir1file1.txt',
                'dir1/dir1file2.txt'),
            'The finder respects the max depth setting.'
        );
    }

    public function assertFinderFinds(FileFinder $finder, $expect, $message = null)
    {
        $found = $finder->find($this->base);

        foreach ($expect as $k => $file) {
            $expect[$k] = "{$this->base}/$file";
        }

        sort($expect);
        sort($found);

        $this->assertEquals($expect, $found, $message);
    }
}
