<?php

namespace SilverStripe\Forms\Tests\HTMLEditor;

use SilverStripe\Control\Director;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\HTMLEditor\TinyMCEConfig;

class TinyMCEConfigTest extends SapphireTest
{

    public function testEditorIdentifier()
    {
        $config = TinyMCEConfig::get('myconfig');
        $this->assertEquals('myconfig', $config->getOption('editorIdentifier'));
    }

    /**
     * Ensure that all TinyMCEConfig.tinymce_lang are valid
     */
    public function testLanguagesValid()
    {
        $configDir = TinyMCEConfig::config()->get('base_dir');
        if (!$configDir) {
            $this->markTestSkipped("Test skipped without TinyMCE resources folder being installed");
        }

        $langs = Director::baseFolder() . '/' . ModuleResourceLoader::resourcePath($configDir) . '/langs';

        // Test all langs exist as real files
        foreach (TinyMCEConfig::config()->get('tinymce_lang') as $locale => $resource) {
            // Check valid
            $this->assertFileExists(
                "{$langs}/{$resource}.js",
                "Locale code {$locale} maps to {$resource}.js which exists"
            );
            // Check we don't simplify to locale when a specific version exists
            if (strpos($resource, '_') === false) {
                $this->assertFileNotExists(
                    "{$langs}/{$locale}.js",
                    "Locale code {$locale} doesn't map to simple {$resource}.js when a better {$locale}.js is available"
                );
            }
        }
    }

    public function testGetContentCSS()
    {
        TinyMCEConfig::config()->set('editor_css', [
            'silverstripe/framework:tests/php/Forms/HTMLEditor.css'
        ]);

        // Test default config
        $config = new TinyMCEConfig();
        $this->assertContains('silverstripe/framework:tests/php/Forms/HTMLEditor.css', $config->getContentCSS());

        // Test manual disable
        $config->setContentCSS([]);
        $this->assertEmpty($config->getContentCSS());

        // Test replacement config
        $config->setContentCSS([
            'silverstripe/framework:tests/php/Forms/HTMLEditor_another.css'
        ]);
        $this->assertEquals(
            [ 'silverstripe/framework:tests/php/Forms/HTMLEditor_another.css'],
            $config->getContentCSS()
        );
    }

    public function testProvideI18nEntities()
    {
        TinyMCEConfig::config()->set('image_size_presets', [
            ['i18n' => TinyMCEConfig::class . '.TEST', 'text' => 'Foo bar'],
            ['text' => 'No translation key'],
            ['i18n' => TinyMCEConfig::class . '.NO_TRANSLATION_TEXT'],
            ['i18n' => TinyMCEConfig::class . '.TEST_TWO', 'text' => 'Bar foo'],
        ]);

        $config = TinyMCEConfig::create();
        $translations = $config->provideI18nEntities();

        $this->assertEquals(
            3,
            sizeof($translations),
            'Only two presets have valid translation + the generic PIXEL_WIDTH one'
        );
        $this->assertEquals('Foo bar', $translations[TinyMCEConfig::class . '.TEST']);
        $this->assertEquals('Bar foo', $translations[TinyMCEConfig::class . '.TEST_TWO']);
        $this->assertEquals('{width} pixels', $translations[TinyMCEConfig::class . '.PIXEL_WIDTH']);
    }
}
