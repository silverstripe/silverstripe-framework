<?php

namespace SilverStripe\View\Tests;

use SilverStripe\Control\Director;
use SilverStripe\Dev\SapphireTest;
use Psr\SimpleCache\CacheInterface;
use ReflectionMethod;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Core\Manifest\ModuleManifest;
use SilverStripe\View\SSTemplateEngine;
use SilverStripe\View\ThemeManifest;
use SilverStripe\View\ThemeResourceLoader;

/**
 * Tests for SSTemplateEngine::findTemplate().
 * These have been separated out from SSTemplateEngineTest because of the extreme setup requirements.
 */
class SSTemplateEngineFindTemplateTest extends SapphireTest
{
    private string $base;

    private ThemeResourceLoader $origLoader;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake project root
        $this->base = dirname(__FILE__) . '/SSTemplateEngineTest_findTemplate';
        Director::config()->set('alternate_base_folder', $this->base);
        ModuleManifest::config()->set('module_priority', ['$project', '$other_modules']);
        ModuleManifest::config()->set('project', 'myproject');

        $moduleManifest = new ModuleManifest($this->base);
        $moduleManifest->init();
        $moduleManifest->sort();
        ModuleLoader::inst()->pushManifest($moduleManifest);

        // New ThemeManifest for that root
        $themeManifest = new ThemeManifest($this->base);
        $themeManifest->setProject('myproject');
        $themeManifest->init();
        // New Loader for that root
        $this->origLoader = ThemeResourceLoader::inst();
        $themeResourceLoader = new ThemeResourceLoader($this->base);
        $themeResourceLoader->addSet('$default', $themeManifest);
        ThemeResourceLoader::set_instance($themeResourceLoader);

        // Ensure the cache is flushed between tests
        ThemeResourceLoader::flush();
    }

    protected function tearDown(): void
    {
        ThemeResourceLoader::set_instance($this->origLoader);
        ModuleLoader::inst()->popManifest();
        parent::tearDown();
    }

    /**
     * Test that 'main' and 'Layout' templates are loaded from module
     */
    public function testFindTemplatesInModule()
    {
        $base = ThemeResourceLoader::inst()->getBase();
        $engine = new SSTemplateEngine();
        $reflectionFindTemplate = new ReflectionMethod($engine, 'findTemplate');
        $reflectionFindTemplate->setAccessible(true);

        $this->assertEquals(
            "$base/module/templates/Page.ss",
            $reflectionFindTemplate->invoke($engine, 'Page', ['$default'])
        );

        $this->assertEquals(
            "$base/module/templates/Layout/Page.ss",
            $reflectionFindTemplate->invoke($engine, ['type' => 'Layout', 'Page'], ['$default'])
        );
    }

    public function testFindNestedThemeTemplates()
    {
        $base = ThemeResourceLoader::inst()->getBase();
        $engine = new SSTemplateEngine();
        $reflectionFindTemplate = new ReflectionMethod($engine, 'findTemplate');
        $reflectionFindTemplate->setAccessible(true);

        // Without including the theme this template cannot be found
        $this->assertEquals(null, $reflectionFindTemplate->invoke($engine, 'NestedThemePage', ['$default']));

        // With a nested theme available then it is available
        $this->assertEquals(
            "{$base}/module/themes/subtheme/templates/NestedThemePage.ss",
            $reflectionFindTemplate->invoke(
                $engine,
                'NestedThemePage',
                [
                    'silverstripe/module:subtheme',
                    '$default'
                ]
            )
        );

        // Can also be found if excluding $default theme
        $this->assertEquals(
            "{$base}/module/themes/subtheme/templates/NestedThemePage.ss",
            $reflectionFindTemplate->invoke(
                $engine,
                'NestedThemePage',
                [
                    'silverstripe/module:subtheme',
                ]
            )
        );
    }

    public function testFindTemplateByType()
    {
        $base = ThemeResourceLoader::inst()->getBase();
        $engine = new SSTemplateEngine();
        $reflectionFindTemplate = new ReflectionMethod($engine, 'findTemplate');
        $reflectionFindTemplate->setAccessible(true);

        // Test that "type" is respected properly
        $this->assertEquals(
            "{$base}/module/templates/MyNamespace/Layout/MyClass.ss",
            $reflectionFindTemplate->invoke(
                $engine,
                [
                    [
                        'type' => 'Layout',
                        'MyNamespace/NonExistantTemplate'
                    ],
                    [
                        'type' => 'Layout',
                        'MyNamespace/MyClass'
                    ],
                    'MyNamespace/MyClass'
                ],
                [
                    'silverstripe/module:subtheme',
                    'theme',
                    '$default',
                ]
            )
        );

        // Non-typed template can be found even if looking for typed theme at a lower priority
        $this->assertEquals(
            "{$base}/module/templates/MyNamespace/MyClass.ss",
            $reflectionFindTemplate->invoke(
                $engine,
                [
                    [
                        'type' => 'Layout',
                        'MyNamespace/NonExistantTemplate'
                    ],
                    'MyNamespace/MyClass',
                    [
                        'type' => 'Layout',
                        'MyNamespace/MyClass'
                    ]
                ],
                [
                    'silverstripe/module',
                    'theme',
                    '$default',
                ]
            )
        );
    }

    public function testFindTemplatesByPath()
    {
        $base = ThemeResourceLoader::inst()->getBase();
        $engine = new SSTemplateEngine();
        $reflectionFindTemplate = new ReflectionMethod($engine, 'findTemplate');
        $reflectionFindTemplate->setAccessible(true);

        // Items given as full paths are returned directly
        $this->assertEquals(
            "$base/themes/theme/templates/Page.ss",
            $reflectionFindTemplate->invoke($engine, "$base/themes/theme/templates/Page.ss", ['theme'])
        );

        $this->assertEquals(
            "$base/themes/theme/templates/Page.ss",
            $reflectionFindTemplate->invoke(
                $engine,
                [
                    "$base/themes/theme/templates/Page.ss",
                    "Page"
                ],
                ['theme']
            )
        );

        // Ensure checks for file_exists
        $this->assertEquals(
            "$base/themes/theme/templates/Page.ss",
            $reflectionFindTemplate->invoke(
                $engine,
                [
                    "$base/themes/theme/templates/NotAPage.ss",
                    "$base/themes/theme/templates/Page.ss",
                ],
                ['theme']
            )
        );
    }

    /**
     * Test that 'main' and 'Layout' templates are loaded from set theme
     */
    public function testFindTemplatesInTheme()
    {
        $base = ThemeResourceLoader::inst()->getBase();
        $engine = new SSTemplateEngine();
        $reflectionFindTemplate = new ReflectionMethod($engine, 'findTemplate');
        $reflectionFindTemplate->setAccessible(true);

        $this->assertEquals(
            "$base/themes/theme/templates/Page.ss",
            $reflectionFindTemplate->invoke($engine, 'Page', ['theme'])
        );

        $this->assertEquals(
            "$base/themes/theme/templates/Layout/Page.ss",
            $reflectionFindTemplate->invoke($engine, ['type' => 'Layout', 'Page'], ['theme'])
        );
    }

    /**
     * Test that 'main' and 'Layout' templates are loaded from project without a set theme
     */
    public function testFindTemplatesInApplication()
    {
        $base = ThemeResourceLoader::inst()->getBase();
        $engine = new SSTemplateEngine();
        $reflectionFindTemplate = new ReflectionMethod($engine, 'findTemplate');
        $reflectionFindTemplate->setAccessible(true);

        $templates = [
            $base . '/myproject/templates/Page.ss',
            $base . '/myproject/templates/Layout/Page.ss'
        ];
        foreach ($templates as $template) {
            file_put_contents($template, '');
        }

        try {
            $this->assertEquals(
                "$base/myproject/templates/Page.ss",
                $reflectionFindTemplate->invoke($engine, 'Page', ['$default'])
            );

            $this->assertEquals(
                "$base/myproject/templates/Layout/Page.ss",
                $reflectionFindTemplate->invoke($engine, ['type' => 'Layout', 'Page'], ['$default'])
            );
        } finally {
            foreach ($templates as $template) {
                unlink($template);
            }
        }
    }

    /**
     * Test that 'main' template is found in theme and 'Layout' is found in module
     */
    public function testFindTemplatesMainThemeLayoutModule()
    {
        $base = ThemeResourceLoader::inst()->getBase();
        $engine = new SSTemplateEngine();
        $reflectionFindTemplate = new ReflectionMethod($engine, 'findTemplate');
        $reflectionFindTemplate->setAccessible(true);

        $this->assertEquals(
            "$base/themes/theme/templates/CustomThemePage.ss",
            $reflectionFindTemplate->invoke($engine, 'CustomThemePage', ['theme', '$default'])
        );

        $this->assertEquals(
            "$base/module/templates/Layout/CustomThemePage.ss",
            $reflectionFindTemplate->invoke($engine, ['type' => 'Layout', 'CustomThemePage'], ['theme', '$default'])
        );
    }

    public function testFindTemplateWithCacheMiss()
    {
        $mockCache = $this->createMock(CacheInterface::class);
        $mockCache->expects($this->once())->method('has')->willReturn(false);
        $mockCache->expects($this->never())->method('get');
        $mockCache->expects($this->once())->method('set');
        ThemeResourceLoader::inst()->setCache($mockCache);

        $engine = new SSTemplateEngine();
        $reflectionFindTemplate = new ReflectionMethod($engine, 'findTemplate');
        $reflectionFindTemplate->setAccessible(true);

        $reflectionFindTemplate->invoke($engine, 'Page', ['$default']);
    }

    public function testFindTemplateWithCacheHit()
    {
        $mockCache = $this->createMock(CacheInterface::class);
        $mockCache->expects($this->once())->method('has')->willReturn(true);
        $mockCache->expects($this->never())->method('set');
        $mockCache->expects($this->once())->method('get')->willReturn('mock_template.ss');
        ThemeResourceLoader::inst()->setCache($mockCache);

        $engine = new SSTemplateEngine();
        $reflectionFindTemplate = new ReflectionMethod($engine, 'findTemplate');
        $reflectionFindTemplate->setAccessible(true);

        $result = $reflectionFindTemplate->invoke($engine, 'Page', ['$default']);
        $this->assertSame('mock_template.ss', $result);
    }
}
