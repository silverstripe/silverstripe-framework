<?php

namespace SilverStripe\Forms\HTMLEditor;

use SilverStripe\Assets\Storage\GeneratedAssetHandler;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Manifest\ModuleResource;

/**
 * Generates tinymce config using a combined file generated via a standard
 * SilverStripe {@link GeneratedAssetHandler}
 */
class TinyMCECombinedGenerator implements TinyMCEScriptGenerator, Flushable
{
    use Configurable;
    use Injectable;

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

        $files = [ ];

        // Add core languages
        $language = $config->getOption('language');
        if ($language) {
            $files[] = $tinymceDir . '/langs/' . $language;
        }

        // Add plugins, along with any plugin specific lang files
        foreach ($config->getPlugins() as $plugin => $path) {
            // Add external plugin
            if ($path) {
                if ($path instanceof ModuleResource) {
                    // Resolve path / url later
                    $files[] = $path;
                } elseif (Director::is_absolute_url($path) || strpos($path, '/') === 0) {
                    // Convert URLS to relative paths
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
            if ($file instanceof ModuleResource) {
                return $file;
            }
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

        $libContent = $this->getFileContents(Director::baseFolder() . '/' . $tinymceDir . '/tinymce.min.js');

        // Register vars for config
        $baseDirJS = Convert::raw2js(Director::absoluteBaseURL());
        $buffer = [];
        $buffer[] = <<<SCRIPT
(function() {
  var baseTag = window.document.getElementsByTagName('base');
  var baseURL = baseTag.length ? baseTag[0].baseURI : '$baseDirJS';
SCRIPT;
        $buffer[] = <<<SCRIPT
(function() {
  // Avoid double-registration
  if (window.tinymce) {
    return;
  }

  var tinyMCEPreInit = {
    base: baseURL,
    suffix: '.min',
  };
  $libContent
})();
SCRIPT;

        // Load all tinymce script files into buffer
        foreach ($files as $path) {
            if ($path instanceof ModuleResource) {
                $path = $path->getPath();
            } else {
                $path = Director::baseFolder() . '/' . $path;
            }
            $buffer[] = $this->getFileContents($path);
        }

        // Generate urls for all files
        $fileURLS = array_map(function ($path) {
            if ($path instanceof ModuleResource) {
                return $path->getURL();
            }
            return $path;
        }, $files);

        // Join list of paths
        $filesList = Convert::raw2js(implode(',', $fileURLS));
        // Mark all themes, plugins and languages as done
        $buffer[] = "window.tinymce.each('$filesList'.split(','),".
            "function(f){tinymce.ScriptLoader.markDone(baseURL+f);});";

        $buffer[] = '})();';
        return implode("\n", $buffer) . "\n";
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

    /**
     * This function is triggered early in the request if the "flush" query
     * parameter has been set. Each class that implements Flushable implements
     * this function which looks after it's own specific flushing functionality.
     *
     * @see FlushMiddleware
     */
    public static function flush()
    {
        $dir = dirname(static::config()->get('filename_base'));
        static::singleton()->getAssetHandler()->removeContent($dir);
    }
}
