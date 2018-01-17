<?php

namespace SilverStripe\Forms\Tests\HTMLEditor;

use SilverStripe\Control\Director;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\HTMLEditor\TinyMCEConfig;

class TinyMCEConfigTest extends SapphireTest
{
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
}
