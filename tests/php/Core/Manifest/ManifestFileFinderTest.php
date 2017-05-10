<?php

namespace SilverStripe\Core\Tests\Manifest;

use SilverStripe\Core\Manifest\ManifestFileFinder;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for the {@link ManifestFileFinder} class.
 */
class ManifestFileFinderTest extends SapphireTest
{

    protected $base;

    public function __construct()
    {
        $this->defaultBase = dirname(__FILE__) . '/fixtures/manifestfilefinder';
        parent::__construct();
    }

    public function assertFinderFinds($finder, $base, $expect, $message = null)
    {
        if (!$base) {
            $base = $this->defaultBase;
        }

        $found = $finder->find($base);

        foreach ($expect as $k => $file) {
            $expect[$k] = "{$base}/$file";
        }

        sort($expect);
        sort($found);

        $this->assertEquals($expect, $found, $message);
    }

    public function testBasicOperation()
    {
        $finder = new ManifestFileFinder();
        $finder->setOption('name_regex', '/\.txt$/');

        $this->assertFinderFinds(
            $finder,
            null,
            array(
            'module/module.txt'
            )
        );
    }

    public function testIgnoreTests()
    {
        $finder = new ManifestFileFinder();
        $finder->setOption('name_regex', '/\.txt$/');
        $finder->setOption('ignore_tests', false);

        $this->assertFinderFinds(
            $finder,
            null,
            array(
            'module/module.txt',
            'module/tests/tests.txt',
            'module/code/tests/tests2.txt'
            )
        );
    }

    public function testIncludeThemes()
    {
        $finder = new ManifestFileFinder();
        $finder->setOption('name_regex', '/\.txt$/');
        $finder->setOption('include_themes', true);

        $this->assertFinderFinds(
            $finder,
            null,
            array(
            'module/module.txt',
            'themes/themes.txt'
            )
        );
    }

    public function testIncludeWithRootConfigFile()
    {
        $finder = new ManifestFileFinder();

        $this->assertFinderFinds(
            $finder,
            dirname(__FILE__) . '/fixtures/manifestfilefinder_rootconfigfile',
            array(
                'code/code.txt',
            )
        );
    }

    public function testIncludeWithRootConfigFolder()
    {
        $finder = new ManifestFileFinder();

        $this->assertFinderFinds(
            $finder,
            dirname(__FILE__) . '/fixtures/manifestfilefinder_rootconfigfolder',
            array(
                'code/code.txt',
            )
        );
    }
}
