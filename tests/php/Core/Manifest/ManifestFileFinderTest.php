<?php

namespace SilverStripe\Core\Tests\Manifest;

use SilverStripe\Core\Manifest\ManifestFileFinder;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for the {@link ManifestFileFinder} class.
 */
class ManifestFileFinderTest extends SapphireTest
{
    protected $defaultBase;

    public function __construct()
    {
        $this->defaultBase = __DIR__ . '/fixtures/manifestfilefinder';
        parent::__construct();
    }

    /**
     * Test that the finder can find the given files
     *
     * @param ManifestFileFinder $finder
     * @param string $base
     * @param array $expect
     * @param string $message
     */
    public function assertFinderFinds(ManifestFileFinder $finder, $base, $expect, $message = null)
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
            [
                'module/module.txt',
                'vendor/myvendor/thismodule/module.txt',
            ]
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
            [
                'module/module.txt',
                'module/tests/tests.txt',
                'module/code/tests/tests2.txt',
                'vendor/myvendor/thismodule/module.txt',
                'vendor/myvendor/thismodule/tests/tests.txt',
                'vendor/myvendor/thismodule/code/tests/tests2.txt',
            ]
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
            [
                'module/module.txt',
                'themes/themes.txt',
                'vendor/myvendor/thismodule/module.txt',
            ]
        );
    }

    public function testIncludeWithRootConfigFile()
    {
        $finder = new ManifestFileFinder();

        $this->assertFinderFinds(
            $finder,
            __DIR__ . '/fixtures/manifestfilefinder_rootconfigfile',
            [ 'code/code.txt' ]
        );
    }

    public function testIncludeWithRootConfigFolder()
    {
        $finder = new ManifestFileFinder();

        $this->assertFinderFinds(
            $finder,
            __DIR__ . '/fixtures/manifestfilefinder_rootconfigfolder',
            [
                '_config/config.yml',
                'code/code.txt',
            ]
        );
    }
}
