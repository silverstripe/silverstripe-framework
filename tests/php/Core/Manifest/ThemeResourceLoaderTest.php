<?php

namespace SilverStripe\Core\Tests\Manifest;

use SilverStripe\Control\Director;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\View\ThemeResourceLoader;
use SilverStripe\View\ThemeManifest;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Manifest\ModuleManifest;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the {@link TemplateLoader} class.
 */
class ThemeResourceLoaderTest extends SapphireTest
{
    /**
     * @var string
     */
    private $base;

    /**
     * @var ThemeManifest
     */
    private $manifest;

    /**
     * @var ThemeResourceLoader
     */
    private $loader;

    /**
     * Set up manifest before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Fake project root
        $this->base = dirname(__FILE__) . '/fixtures/templatemanifest';
        Director::config()->set('alternate_base_folder', $this->base);
        ModuleManifest::config()->set('module_priority', ['$project', '$other_modules']);
        ModuleManifest::config()->set('project', 'myproject');

        $moduleManifest = new ModuleManifest($this->base);
        $moduleManifest->init();
        $moduleManifest->sort();
        ModuleLoader::inst()->pushManifest($moduleManifest);

        // New ThemeManifest for that root
        $this->manifest = new ThemeManifest($this->base);
        $this->manifest->setProject('myproject');
        $this->manifest->init();
        // New Loader for that root
        $this->loader = new ThemeResourceLoader($this->base);
        $this->loader->addSet('$default', $this->manifest);

        // Ensure the cache is flushed between tests
        ThemeResourceLoader::flush();
    }

    protected function tearDown(): void
    {
        ModuleLoader::inst()->popManifest();
        parent::tearDown();
    }

    public function testFindThemedCSS()
    {
        $this->assertEquals(
            "myproject/css/project.css",
            $this->loader->findThemedCSS('project', ['$default', 'theme'])
        );
        $this->assertEquals(
            "themes/theme/css/project.css",
            $this->loader->findThemedCSS('project', ['theme', '$default'])
        );
        $this->assertEmpty(
            $this->loader->findThemedCSS('nofile', ['theme', '$default'])
        );
        $this->assertEquals(
            'module/css/content.css',
            $this->loader->findThemedCSS('content', ['/module', 'theme'])
        );
        $this->assertEquals(
            'module/css/content.css',
            $this->loader->findThemedCSS('content', ['/module', 'theme', '$default'])
        );
        $this->assertEquals(
            'module/css/content.css',
            $this->loader->findThemedCSS('content', ['$default', '/module', 'theme'])
        );
    }

    public function testFindThemedJavascript()
    {
        $this->assertEquals(
            "myproject/javascript/project.js",
            $this->loader->findThemedJavascript('project', ['$default', 'theme'])
        );
        $this->assertEquals(
            "themes/theme/javascript/project.js",
            $this->loader->findThemedJavascript('project', ['theme', '$default'])
        );
        $this->assertEmpty(
            $this->loader->findThemedJavascript('nofile', ['theme', '$default'])
        );
        $this->assertEquals(
            'module/javascript/content.js',
            $this->loader->findThemedJavascript('content', ['/module', 'theme'])
        );
        $this->assertEquals(
            'module/javascript/content.js',
            $this->loader->findThemedJavascript('content', ['/module', 'theme', '$default'])
        );
        $this->assertEquals(
            'module/javascript/content.js',
            $this->loader->findThemedJavascript('content', ['$default', '/module', 'theme'])
        );
    }

    public static function providerTestGetPath()
    {
        return [
            // Legacy theme
            [
                'theme',
                'themes/theme',
            ],
            // Module themes
            [
                'silverstripe/vendormodule:vendortheme',
                'vendor/silverstripe/vendormodule/themes/vendortheme',
            ],
            [
                'module:subtheme',
                'module/themes/subtheme',
            ],
            // Module absolute paths
            [
                'silverstripe/vendormodule:/themes/vendortheme',
                'vendor/silverstripe/vendormodule/themes/vendortheme',
            ],
            [
                'module:/themes/subtheme',
                'module/themes/subtheme',
            ],
            // Module root directory
            [
                'silverstripe/vendormodule:/',
                'vendor/silverstripe/vendormodule',
            ],
            [
                'silverstripe/vendormodule:',
                'vendor/silverstripe/vendormodule',
            ],
            [
                'silverstripe/vendormodule',
                'vendor/silverstripe/vendormodule',
            ],
            [
                'module:',
                'module',
            ],
            // Absolute paths
            [
                '/vendor/silverstripe/vendormodule/themes/vendortheme',
                'vendor/silverstripe/vendormodule/themes/vendortheme',
            ],
            [
                '/module/themes/subtheme',
                'module/themes/subtheme'
            ]
        ];
    }

    /**
     * @param string $name Theme identifier
     * @param string $path Path to theme
     */
    #[DataProvider('providerTestGetPath')]
    public function testGetPath($name, $path)
    {
        $this->assertEquals($path, $this->loader->getPath($name));
    }
}
