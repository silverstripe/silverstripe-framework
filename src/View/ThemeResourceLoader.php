<?php

namespace SilverStripe\View;

use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Core\Path;

/**
 * Handles finding templates from a stack of template manifest objects.
 */
class ThemeResourceLoader implements Flushable, TemplateGlobalProvider
{

    /**
     * @var ThemeResourceLoader
     */
    private static $instance;

    /**
     * The base path of the application
     *
     * @var string
     */
    protected $base;

    /**
     * List of template "sets" that contain a test manifest, and have an alias.
     * E.g. '$default'
     *
     * @var ThemeList[]
     */
    protected $sets = [];

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @return ThemeResourceLoader
     */
    public static function inst()
    {
        return ThemeResourceLoader::$instance ? ThemeResourceLoader::$instance : ThemeResourceLoader::$instance = new ThemeResourceLoader();
    }

    /**
     * Set instance
     *
     * @param ThemeResourceLoader $instance
     */
    public static function set_instance(ThemeResourceLoader $instance)
    {
        ThemeResourceLoader::$instance = $instance;
    }

    public function __construct($base = null)
    {
        $this->base = $base ? $base : BASE_PATH;
    }

    /**
     * Add a new theme manifest for a given identifier. E.g. '$default'
     *
     * @param string $set
     * @param ThemeList $manifest
     */
    public function addSet($set, ThemeList $manifest)
    {
        $this->sets[$set] = $manifest;
    }

    /**
     * Get a named theme set
     *
     * @param string $set
     * @return ThemeList
     */
    public function getSet($set)
    {
        if (isset($this->sets[$set])) {
            return $this->sets[$set];
        }
        return null;
    }

    /**
     * Given a theme identifier, determine the path from the root directory
     *
     * The mapping from $identifier to path follows these rules:
     * - A simple theme name ('mytheme') which maps to the standard themes dir (/themes/mytheme)
     * - A theme path with a leading slash ('/mymodule/themes/mytheme') which maps directly to that path.
     * - or a vendored theme path. (vendor/mymodule:mytheme) which maps to the nested 'theme' within
     *   that module. ('/mymodule/themes/mytheme').
     * - A vendored module with no nested theme (vendor/mymodule) which maps to the root directory
     *   of that module. ('/mymodule').
     *
     * @param string $identifier Theme identifier.
     * @return string Path from root, not including leading or trailing forward slash. E.g. themes/mytheme
     */
    public function getPath($identifier)
    {
        $slashPos = strpos($identifier ?? '', '/');
        $parts = explode(':', $identifier ?? '', 2);

        // If identifier starts with "/", it's a path from root
        if ($slashPos === 0) {
            if (count($parts ?? []) > 1) {
                throw new InvalidArgumentException("Invalid theme identifier {$identifier}");
            }
            return Path::normalise($identifier, true);
        }

        // If there is no slash / colon it's a legacy theme
        if ($slashPos === false && count($parts ?? []) === 1) {
            return Path::join(THEMES_DIR, $identifier);
        }

        // Extract from <vendor>/<module>:<theme> format.
        // <vendor> is optional, and if <theme> is omitted it defaults to the module root dir.
        // If <theme> is included, this is the name of the directory under moduleroot/themes/
        // which contains the theme.
        // <module> is always the name of the install directory, not necessarily the composer name.

        // Find module from first part
        $moduleName = $parts[0];
        $module = ModuleLoader::inst()->getManifest()->getModule($moduleName);
        if ($module) {
            $modulePath = $module->getRelativePath();
        } else {
            // If no module could be found, assume based on basename
            // with a warning
            if (strstr('/', $moduleName ?? '')) {
                list(, $modulePath) = explode('/', $parts[0] ?? '', 2);
            } else {
                $modulePath = $moduleName;
            }
            trigger_error("No module named {$moduleName} found. Assuming path {$modulePath}", E_USER_WARNING);
        }

        // Parse relative path for this theme within this module
        $theme = count($parts ?? []) > 1 ? $parts[1] : '';
        if (empty($theme)) {
            // "module/vendor:"
            // "module/vendor"
            $subpath = '';
        } elseif (strpos($theme ?? '', '/') === 0) {
            // "module/vendor:/sub/path"
            $subpath = rtrim($theme ?? '', '/');
        } else {
            // "module/vendor:subtheme"
            $subpath = '/themes/' . $theme;
        }

        // Join module with subpath
        return Path::normalise($modulePath . $subpath, true);
    }

    /**
     * Attempts to find possible candidate templates from a set of template
     * names from modules, current theme directory and finally the application
     * folder.
     *
     * The template names can be passed in as plain strings, or be in the
     * format "type/name", where type is the type of template to search for
     * (e.g. Includes, Layout).
     *
     * The results of this method will be cached for future use.
     *
     * @param string|array $template Template name, or template spec in array format with the keys
     * 'type' (type string) and 'templates' (template hierarchy in order of precedence).
     * If 'templates' is omitted then any other item in the array will be treated as the template
     * list, or list of templates each in the array spec given.
     * Templates with an .ss extension will be treated as file paths, and will bypass
     * theme-coupled resolution.
     * @param array $themes List of themes to use to resolve themes. Defaults to {@see SSViewer::get_themes()}
     * @return string Absolute path to resolved template file, or null if not resolved.
     * File location will be in the format themes/<theme>/templates/<directories>/<type>/<basename>.ss
     * Note that type (e.g. 'Layout') is not the root level directory under 'templates'.
     */
    public function findTemplate($template, $themes = null)
    {
        if ($themes === null) {
            $themes = SSViewer::get_themes();
        }

        // Look for a cached result for this data set
        $cacheKey = md5(json_encode($template) . json_encode($themes));
        if ($this->getCache()->has($cacheKey)) {
            return $this->getCache()->get($cacheKey);
        }

        $type = '';
        if (is_array($template)) {
            // Check if templates has type specified
            if (array_key_exists('type', $template ?? [])) {
                $type = $template['type'];
                unset($template['type']);
            }
            // Templates are either nested in 'templates' or just the rest of the list
            $templateList = array_key_exists('templates', $template ?? []) ? $template['templates'] : $template;
        } else {
            $templateList = [$template];
        }

        foreach ($templateList as $i => $template) {
            // Check if passed list of templates in array format
            if (is_array($template)) {
                $path = $this->findTemplate($template, $themes);
                if ($path) {
                    $this->getCache()->set($cacheKey, $path);
                    return $path;
                }
                continue;
            }

            // If we have an .ss extension, this is a path, not a template name. We should
            // pass in templates without extensions in order for template manifest to find
            // files dynamically.
            if (substr($template ?? '', -3) == '.ss' && file_exists($template ?? '')) {
                $this->getCache()->set($cacheKey, $template);
                return $template;
            }

            // Check string template identifier
            $template = str_replace('\\', '/', $template ?? '');
            $parts = explode('/', $template ?? '');

            $tail = array_pop($parts);
            $head = implode('/', $parts);
            $themePaths = $this->getThemePaths($themes);
            foreach ($themePaths as $themePath) {
                // Join path
                $pathParts = [ $this->base, $themePath, 'templates', $head, $type, $tail ];
                try {
                    $path = Path::join($pathParts) . '.ss';
                    if (file_exists($path ?? '')) {
                        $this->getCache()->set($cacheKey, $path);
                        return $path;
                    }
                } catch (InvalidArgumentException $e) {
                    // No-op
                }
            }
        }

        // No template found
        $this->getCache()->set($cacheKey, null);
        return null;
    }

    /**
     * Resolve themed CSS path
     *
     * @param string $name Name of CSS file without extension
     * @param array $themes List of themes, Defaults to {@see SSViewer::get_themes()}
     * @return string Path to resolved CSS file (relative to base dir)
     */
    public function findThemedCSS($name, $themes = null)
    {
        if ($themes === null) {
            $themes = SSViewer::get_themes();
        }

        if (substr($name ?? '', -4) !== '.css') {
            $name .= '.css';
        }

        $filename = $this->findThemedResource("css/$name", $themes);
        if ($filename === null) {
            $filename = $this->findThemedResource($name, $themes);
        }

        return $filename;
    }

    /**
     * Resolve themed javascript path
     *
     * A javascript file in the current theme path name 'themename/javascript/$name.js' is first searched for,
     * and it that doesn't exist and the module parameter is set then a javascript file with that name in
     * the module is used.
     *
     * @param string $name The name of the file - eg '/js/File.js' would have the name 'File'
     * @param array $themes List of themes, Defaults to {@see SSViewer::get_themes()}
     * @return string Path to resolved javascript file (relative to base dir)
     */
    public function findThemedJavascript($name, $themes = null)
    {
        if ($themes === null) {
            $themes = SSViewer::get_themes();
        }

        if (substr($name ?? '', -3) !== '.js') {
            $name .= '.js';
        }

        $filename = $this->findThemedResource("javascript/$name", $themes);
        if ($filename === null) {
            $filename = $this->findThemedResource($name, $themes);
        }

        return $filename;
    }

    /**
     * Resolve a themed resource or directory
     *
     * A themed resource can be any file that resides in a theme folder.
     *
     * @param string $resource A file path relative to the root folder of a theme
     * @param array $themes An order listed of themes to search, Defaults to {@see SSViewer::get_themes()}
     * @return string
     */
    public function findThemedResource($resource, $themes = null)
    {
        if ($themes === null) {
            $themes = SSViewer::get_themes();
        }

        $paths = $this->getThemePaths($themes);

        foreach ($paths as $themePath) {
            $relativePath = Path::join($themePath, $resource);
            $absolutePath = Path::join($this->base, $relativePath);
            if (file_exists($absolutePath ?? '')) {
                return $relativePath;
            }
        }

        // Resource exists in no context
        return null;
    }

    /**
     * Return the URL for a given themed resource or directory within the project.
     *
     * A themed resource can be any file that resides in a theme folder.
     */
    public static function themedResourceURL(string $resource): ?string
    {
        $filePath = static::inst()->findThemedResource($resource);
        if (!$filePath) {
            return '';
        }

        return ModuleResourceLoader::singleton()->resolveURL($filePath);
    }

    public static function get_template_global_variables()
    {
        return [
            'themedResourceURL',
        ];
    }

    /**
     * Resolve all themes to the list of root folders relative to site root
     *
     * @param array $themes List of themes to resolve. Supports named theme sets. Defaults to {@see SSViewer::get_themes()}.
     * @return array List of root-relative folders in order of precedence.
     */
    public function getThemePaths($themes = null)
    {
        if ($themes === null) {
            $themes = SSViewer::get_themes();
        }

        $paths = [];
        foreach ($themes as $themename) {
            // Expand theme sets
            $set = $this->getSet($themename);
            $subthemes = $set ? $set->getThemes() : [$themename];

            // Resolve paths
            foreach ($subthemes as $theme) {
                $paths[] = $this->getPath($theme);
            }
        }
        return $paths;
    }

    /**
     * Flush any cached data
     */
    public static function flush()
    {
        ThemeResourceLoader::inst()->getCache()->clear();
    }

    /**
     * @return CacheInterface
     */
    public function getCache()
    {
        if (!$this->cache) {
            $this->setCache(Injector::inst()->get(CacheInterface::class . '.ThemeResourceLoader'));
        }
        return $this->cache;
    }

    /**
     * @param CacheInterface $cache
     * @return ThemeResourceLoader
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }
}
