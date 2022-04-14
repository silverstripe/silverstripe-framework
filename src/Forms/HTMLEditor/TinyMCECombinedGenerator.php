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
        $tinymceDir = $config->getTinyMCEResource();

        // List of string / ModuleResource references to embed
        $files = [];

        // Add core languages
        $language = $config->getOption('language');
        if ($language) {
            $files[] = $this->resolveRelativeResource($tinymceDir, "langs/{$language}");
        }

        // Add plugins, along with any plugin specific lang files
        foreach ($config->getPlugins() as $plugin => $path) {
            // Add external plugin
            if ($path) {
                // Skip external urls
                if (is_string($path) && !Director::is_site_url($path)) {
                    continue;
                }
                // Convert URLS to relative paths
                if (is_string($path)) {
                    $path = Director::makeRelative($path);
                }
                // Ensure file exists
                if ($this->resourceExists($path)) {
                    $files[] = $path;
                }
                continue;
            }

            // Core tinymce plugin
            $files[] = $this->resolveRelativeResource($tinymceDir, "plugins/{$plugin}/plugin");
            if ($language) {
                $files[] = $this->resolveRelativeResource($tinymceDir, "plugins/{$plugin}/langs/{$language}");
            }
        }

        // Add themes
        $theme = $config->getTheme();
        if ($theme) {
            $files[] = $this->resolveRelativeResource($tinymceDir, "themes/{$theme}/theme");
            if ($language) {
                $files[] = $this->resolveRelativeResource($tinymceDir, "themes/{$theme}/langs/{$language}");
            }
        }

        // Process source files
        $files = array_filter($files ?? []);
        $libResource = $this->resolveRelativeResource($tinymceDir, 'tinymce');
        $libContent = $this->getFileContents($libResource);

        // Register vars for config
        $baseDirJS = Convert::raw2js(Director::absoluteBaseURL());
        $name = Convert::raw2js($this->checkName($config));
        $buffer = [];
        $buffer[] = <<<SCRIPT
(function() {
  var baseTag = window.document.getElementsByTagName('base');
  var baseURL = baseTag.length ? baseTag[0].baseURI : '$baseDirJS';
  var editorIdentifier = '$name';
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
            $buffer[] = $this->getFileContents($path);
        }

        // Generate urls for all files
        // Note all urls must be relative to base_dir
        $fileURLS = array_map(function ($path) {
            if ($path instanceof ModuleResource) {
                return Director::makeRelative($path->getURL());
            }
            return $path;
        }, $files ?? []);

        // Join list of paths
        $filesList = Convert::raw2js(implode(',', $fileURLS));
        // Mark all themes, plugins and languages as done
        $buffer[] = "window.tinymce.each('$filesList'.split(',')," . "function(f){tinymce.ScriptLoader.markDone(baseURL+f);});";

        $buffer[] = '})();';
        return implode("\n", $buffer) . "\n";
    }

    /**
     * Returns the contents of the script file if it exists and removes the UTF-8 BOM header if it exists.
     *
     * @param string|ModuleResource $file File to load.
     * @return string File contents or empty string if it doesn't exist.
     */
    protected function getFileContents($file)
    {
        if ($file instanceof ModuleResource) {
            $path = $file->getPath();
        } else {
            $path = Director::baseFolder() . '/' . $file;
        }
        if (!file_exists($path ?? '')) {
            return null;
        }
        $content = file_get_contents($path ?? '');

        // Remove UTF-8 BOM
        if (substr($content ?? '', 0, 3) === pack("CCC", 0xef, 0xbb, 0xbf)) {
            $content = substr($content ?? '', 3);
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
        $hash = substr(sha1(json_encode($config->getAttributes()) ?? ''), 0, 10);
        $name = $this->checkName($config);
        $url = str_replace(
            ['{name}', '{hash}'],
            [$name, $hash],
            $this->config()->get('filename_base') ?? ''
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
        $dir = dirname(static::config()->get('filename_base') ?? '');
        static::singleton()->getAssetHandler()->removeContent($dir);
    }

    /**
     * Get relative resource for a given base and string
     *
     * @param ModuleResource|string $base
     * @param string $resource
     * @return ModuleResource|string
     */
    protected function resolveRelativeResource($base, $resource)
    {
        // Return resource path based on relative resource path
        foreach (['', '.min.js', '.js'] as $ext) {
            // Map resource
            if ($base instanceof ModuleResource) {
                $next = $base->getRelativeResource($resource . $ext);
            } else {
                $next = rtrim($base ?? '', '/') . '/' . $resource . $ext;
            }
            // Ensure resource exists
            if ($this->resourceExists($next)) {
                return $next;
            }
        }
        return null;
    }

    /**
     * Check if the given resource exists
     *
     * @param string|ModuleResource $resource
     * @return bool
     */
    protected function resourceExists($resource)
    {
        if (!$resource) {
            return false;
        }
        if ($resource instanceof ModuleResource) {
            return $resource->exists();
        }
        $base = rtrim(Director::baseFolder() ?? '', '/');
        return file_exists($base . '/' . $resource);
    }
}
