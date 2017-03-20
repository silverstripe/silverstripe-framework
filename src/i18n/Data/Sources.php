<?php

namespace SilverStripe\i18n\Data;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Core\Resettable;
use SilverStripe\i18n\i18n;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeResourceLoader;

/**
 * Data sources for localisation strings. I.e. yml files stored
 * within the /lang folder in any installed module.
 */
class Sources implements Resettable
{
    use Injectable;
    use Configurable;

    /**
     * List of prioritised modules, in lowest to highest priority.
     *
     * @config
     * @var array
     */
    private static $module_priority = [];

    /**
     * Get sorted modules
     *
     * @return array Array of module names -> path
     */
    public function getSortedModules()
    {
        // Get list of module => path pairs, and then just the names
        $modules = ModuleLoader::instance()->getManifest()->getModules();
        $moduleNames = array_keys($modules);

        // Remove the "project" module from the list - we'll add it back specially later if needed
        global $project;
        if (($idx = array_search($project, $moduleNames)) !== false) {
            array_splice($moduleNames, $idx, 1);
        }

        // Get the order from the config system (lowest to highest)
        $order = Sources::config()->uninherited('module_priority');

        // Find all modules that don't have their order specified by the config system
        $unspecified = array_diff($moduleNames, $order);

        // If the placeholder "other_modules" exists in the order array, replace it by the unspecified modules
        if (($idx = array_search('other_modules', $order)) !== false) {
            array_splice($order, $idx, 1, $unspecified);
        } else {
            // Otherwise just jam them on the front
            array_splice($order, 0, 0, $unspecified);
        }

        // Put the project at end (highest priority)
        if (!in_array($project, $order)) {
            $order[] = $project;
        }

        $sortedModulePaths = array();
        foreach ($order as $module) {
            if (isset($modules[$module])) {
                $sortedModulePaths[$module] = $modules[$module]->getPath();
            }
        }
        $sortedModulePaths = array_reverse($sortedModulePaths, true);
        return $sortedModulePaths;
    }

    /**
     * Cache of found lang dirs
     * @var array
     */
    protected static $cache_lang_dirs = [];

    /**
     * Find the list of prioritised /lang folders in this application
     *
     * @return array
     */
    public function getLangDirs()
    {
        if (static::$cache_lang_dirs) {
            return static::$cache_lang_dirs;
        }
        $paths = [];

        // Search sorted modules (receives absolute paths)
        foreach ($this->getSortedModules() as $module => $path) {
            $langPath = "{$path}/lang/";
            if (is_dir($langPath)) {
                $paths[] = $langPath;
            }
        }

        // Search theme dirs (receives relative paths)
        $locator = ThemeResourceLoader::instance();
        foreach (SSViewer::get_themes() as $theme) {
            if ($locator->getSet($theme)) {
                continue;
            }
            $path = $locator->getPath($theme);
            $langPath = BASE_PATH . "/{$path}/lang/";
            if (is_dir($langPath)) {
                $paths[] = $langPath;
            }
        }

        static::$cache_lang_dirs = $paths;
        return $paths;
    }

    /**
     * Cache of found lang files
     *
     * @var array
     */
    protected static $cache_lang_files = [];

    /**
     * Search directories for list of distinct locale filenames
     *
     * @return array Map of locale key => key of all distinct localisation file names
     */
    protected function getLangFiles()
    {
        if (static::$cache_lang_files) {
            return static::$cache_lang_files;
        }

        $locales = [];
        foreach ($this->getLangDirs() as $langPath) {
            $langFiles = scandir($langPath);
            foreach ($langFiles as $langFile) {
                $locale = pathinfo($langFile, PATHINFO_FILENAME);
                $ext = pathinfo($langFile, PATHINFO_EXTENSION);
                if ($locale && $ext === 'yml') {
                    $locales[$locale] = $locale;
                }
            }
        }
        ksort($locales);
        static::$cache_lang_files = $locales;
        return $locales;
    }

    /**
     * Searches the root-directory for module-directories
     * (identified by having a _config.php on their first directory-level).
     * Finds locales by filename convention ("<locale>.<extension>", e.g. "de_AT.yml").
     *
     * @return array Map of locale codes to names (localised)
     */
    public function getKnownLocales()
    {
        $localesData = i18n::getData();
        $allLocales = $localesData->getLocales();

        // Find installed locales
        $locales = array();
        foreach ($this->getLangFiles() as $locale) {
            // Normalize locale to include likely region tag, avoid repetition in locale labels
            $fullLocale = $localesData->localeFromLang($locale);
            if (isset($allLocales[$fullLocale])) {
                $locales[$fullLocale] = $allLocales[$fullLocale];
            }
        }

        // sort by title (not locale)
        asort($locales);
        return $locales;
    }

    public static function reset()
    {
        static::$cache_lang_files = [];
        static::$cache_lang_dirs = [];
    }
}
