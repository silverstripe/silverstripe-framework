<?php

namespace SilverStripe\Core\Tests\Manifest;

use SilverStripe\Core\Manifest\ManifestFileFinder;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Manifest\Module;

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
    public function assertFinderFinds(ManifestFileFinder $finder, $base, $expect, $message = '')
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
                'vendor/myvendor/phpunit5module/code/logic.txt',
                'vendor/myvendor/phpunit9module/code/logic.txt',
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
                'vendor/myvendor/phpunit5module/code/logic.txt',
                'vendor/myvendor/phpunit5module/tests/phpunit5tests.txt',
                'vendor/myvendor/phpunit9module/code/logic.txt',
                'vendor/myvendor/phpunit9module/tests/phpunit9tests.txt',
            ]
        );
    }

    public function testIgnorePHPUnit5Tests()
    {
        $finder = new ManifestFileFinder();
        $finder->setOption('name_regex', '/\.txt$/');
        $finder->setOption('ignore_tests', false);
        $finder->setOption('ignore_ci_configs', [Module::CI_PHPUNIT_FIVE]);

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
                'vendor/myvendor/phpunit5module/code/logic.txt',
                'vendor/myvendor/phpunit9module/code/logic.txt',
                'vendor/myvendor/phpunit9module/tests/phpunit9tests.txt',
            ]
        );
    }

    public function testIgnoreNonePHPUnit9Tests()
    {
        $finder = new ManifestFileFinder();
        $finder->setOption('name_regex', '/\.txt$/');
        $finder->setOption('ignore_tests', false);
        $finder->setOption('ignore_ci_configs', [Module::CI_PHPUNIT_FIVE, Module::CI_UNKNOWN]);

        $this->assertFinderFinds(
            $finder,
            null,
            [
                'module/module.txt',
                'module/tests/tests.txt',
                'module/code/tests/tests2.txt',
                'vendor/myvendor/thismodule/module.txt',
                'vendor/myvendor/phpunit5module/code/logic.txt',
                'vendor/myvendor/phpunit9module/code/logic.txt',
                'vendor/myvendor/phpunit9module/tests/phpunit9tests.txt',
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
                'vendor/myvendor/phpunit5module/code/logic.txt',
                'vendor/myvendor/phpunit9module/code/logic.txt',
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
