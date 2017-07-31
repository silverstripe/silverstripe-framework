<?php

namespace SilverStripe\Forms\HTMLEditor;

use SilverStripe\Assets\Storage\GeneratedAssetHandler;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;

/**
 * Generates tinymce config using a combined file generated via a standard
 * SilverStripe {@link GeneratedAssetHandler}
 */
class TinyMCECombinedGenerator implements TinyMCEScriptGenerator
{
    use Configurable;

    /**
     * Named config
     *
     * @var string
     */
    private static $filename_base = '_tinymce/tinymce-{name}-{hash}.js';

    /**
     * @var GeneratedAssetHandler
     */
    protected $assetHandler = null;

    /**
     * Assign backend store for generated assets
     *
     * @param GeneratedAssetHandler $assetHandler
     * @return $this
     */
    public function setAssetHandler(GeneratedAssetHandler $assetHandler)
    {
        $this->assetHandler = $assetHandler;
        return $this;
    }

    /**
     * Get backend for assets
     * @return GeneratedAssetHandler
     */
    public function getAssetHandler()
    {
        return $this->assetHandler;
    }

    /**
     * Generate a script URL for the given config
     *
     * @param TinyMCEConfig $config
     * @return string
     */
    public function getScriptURL(TinyMCEConfig $config)
    {
        // Build URL for this config based on named config and hash of settings
        $url = $this->generateFilename($config);

        // Pass content generator
        return $this->getAssetHandler()->getContentURL($url, function () use ($config) {
            return $this->generateContent($config);
        });
    }

    /**
     * Build raw config for tinymce
     *
     * @param TinyMCEConfig $config
     * @return string
     */
    public function generateContent(TinyMCEConfig $config)
    {
        $tinymceDir = $config->getTinyMCEPath();

        // Core JS file
        $files = [ $tinymceDir . '/tinymce' ];

        // Add core languages
        $language = $config->getOption('language');
        if ($language) {
            $files[] = $tinymceDir . '/langs/' . $language;
        }

        // Add plugins, along with any plugin specific lang files
        foreach ($config->getPlugins() as $plugin => $path) {
            // Add external plugin
            if ($path) {
                // Convert URLS to relative paths
                if (Director::is_absolute_url($path) || strpos($path, '/') === 0) {
                    // De-absolute site urls
                    $path = Director::makeRelative($path);
                    if ($path) {
                        $files[] = $path;
                    }
                } else {
                    // Relative URLs are safe
                    $files[] = $path;
                }
                continue;
            }

            // Core tinymce plugin
            $files[] = $tinymceDir . '/plugins/' . $plugin . '/plugin';
            if ($language) {
                $files[] = $tinymceDir . '/plugins/' . $plugin . '/langs/' . $language;
            }
        }

        // Add themes
        $theme = $config->getTheme();
        if ($theme) {
            $files[] = $tinymceDir . '/themes/' . $theme . '/theme';
            if ($language) {
                $files[] = $tinymceDir . '/themes/' . $theme . '/langs/' . $language;
            }
        }

        // Process source files
        $files = array_filter(array_map(function ($file) {
            // Prioritise preferred paths
            $paths = [
                $file,
                $file . '.min.js',
                $file . '.js',
            ];
            foreach ($paths as $path) {
                if (file_exists(Director::baseFolder() . '/' . $path)) {
                    return $path;
                }
            }
            return null;
        }, $files));

        // Set base URL for where tinymce is loaded from
        $buffer = "var tinyMCEPreInit={base:'" . Convert::raw2js($tinymceDir) . "',suffix:'.min'};\n";

        // Load all tinymce script files into buffer
        foreach ($files as $file) {
            $buffer .= $this->getFileContents(Director::baseFolder() . '/' . $file) . "\n";
        }

        // Mark all themes, plugins and languages as done
        $buffer .= 'tinymce.each("' . Convert::raw2js(implode(',', $files)) . '".split(","),function(f){tinymce.ScriptLoader.markDone(f);});';

        return $buffer . "\n";
    }


    /**
     * Returns the contents of the script file if it exists and removes the UTF-8 BOM header if it exists.
     *
     * @param string $file File to load.
     * @return string File contents or empty string if it doesn't exist.
     */
    protected function getFileContents($file)
    {
        $content = file_get_contents($file);

        // Remove UTF-8 BOM
        if (substr($content, 0, 3) === pack("CCC", 0xef, 0xbb, 0xbf)) {
            $content = substr($content, 3);
        }

        return $content;
    }

    /**
     * Check if this config is registered under a given key
     *
     * @param TinyMCEConfig $config
     * @return string
     */
    protected function checkName(TinyMCEConfig $config)
    {
        $configs = HTMLEditorConfig::get_available_configs_map();
        foreach ($configs as $id => $name) {
            if (HTMLEditorConfig::get($id) === $config) {
                return $id;
            }
        }
        return 'custom';
    }

    /**
     * Get filename to use for this config
     *
     * @param TinyMCEConfig $config
     * @return mixed
     */
    public function generateFilename(TinyMCEConfig $config)
    {
        $hash = substr(sha1(json_encode($config->getAttributes())), 0, 10);
        $name = $this->checkName($config);
        $url = str_replace(
            ['{name}', '{hash}'],
            [$name, $hash],
            $this->config()->get('filename_base')
        );
        return $url;
    }
}
