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
     * Get sorted modules
     *
     * @return array Array of module names -> path
     */
    public function getSortedModules()
    {
        $sortedModules = [];
        foreach (ModuleLoader::inst()->getManifest()->getModules() as $module) {
            $sortedModules[$module->getName()] = $module->getPath();
        };

        return $sortedModules;
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
            if (is_dir($langPath ?? '')) {
                $paths[] = $langPath;
            }
        }

        // Search theme dirs (receives relative paths)
        $locator = ThemeResourceLoader::inst();
        foreach (SSViewer::get_themes() as $theme) {
            if ($locator->getSet($theme)) {
                continue;
            }
            $path = $locator->getPath($theme);
            $langPath = BASE_PATH . "/{$path}/lang/";
            if (is_dir($langPath ?? '')) {
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
            $langFiles = scandir($langPath ?? '');
            foreach ($langFiles as $langFile) {
                $locale = pathinfo($langFile ?? '', PATHINFO_FILENAME);
                $ext = pathinfo($langFile ?? '', PATHINFO_EXTENSION);
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
        $locales = [];
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
