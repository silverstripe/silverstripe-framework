<?php

namespace SilverStripe\Core\Manifest;

use SilverStripe\Assets\FileFinder;

/**
 * An extension to the default file finder with some extra filters to facilitate
 * autoload and template manifest generation:
 *   - Only modules with _config.php files are scanned.
 *   - If a _manifest_exclude file is present inside a directory it is ignored.
 *   - Assets and module language directories are ignored.
 *   - Module tests directories are skipped if the `ignore_tests` option is not
 *     set to false.
 */
class ManifestFileFinder extends FileFinder
{

    const CONFIG_FILE = '_config.php';
    const CONFIG_DIR = '_config';
    const EXCLUDE_FILE = '_manifest_exclude';
    const LANG_DIR = 'lang';
    const TESTS_DIR = 'tests';
    const VENDOR_DIR = 'vendor';

    protected static $default_options = [
        'include_themes' => false,
        'ignore_tests' => true,
        'min_depth' => 1,
        'ignore_dirs' => ['node_modules'],
    ];

    public function acceptDir($basename, $pathname, $depth)
    {
        // Skip if ignored
        if ($this->isInsideIgnored($basename, $pathname, $depth)) {
            return false;
        }

        // Keep searching inside vendor
        $inVendor = $this->isInsideVendor($basename, $pathname, $depth);
        if ($inVendor) {
            // Skip nested vendor folders (e.g. vendor/silverstripe/framework/vendor)
            if ($depth == 4 && basename($pathname ?? '') === ManifestFileFinder::VENDOR_DIR) {
                return false;
            }

            // Keep searching if we could have a subdir module
            if ($depth < 3) {
                return true;
            }

            // Stop searching if we are in a non-module library
            $libraryPath = $this->upLevels($pathname, $depth - 3);
            $libraryBase = basename($libraryPath ?? '');
            if (!$this->isDirectoryModule($libraryBase, $libraryPath, 3)) {
                return false;
            }
        }

        // Include themes
        if ($this->getOption('include_themes') && $this->isInsideThemes($basename, $pathname, $depth)) {
            return true;
        }

        // Skip if not in module
        if (!$this->isInsideModule($basename, $pathname, $depth)) {
            return false;
        }

        return parent::acceptDir($basename, $pathname, $depth);
    }

    /**
     * Check if the given dir is, or is inside the vendor folder
     *
     * @param string $basename
     * @param string $pathname
     * @param int $depth
     * @return bool
     */
    public function isInsideVendor($basename, $pathname, $depth)
    {
        $base = basename($this->upLevels($pathname, $depth - 1) ?? '');
        return $base === ManifestFileFinder::VENDOR_DIR;
    }

    /**
     * Check if the given dir is, or is inside the themes folder
     *
     * @param string $basename
     * @param string $pathname
     * @param int $depth
     * @return bool
     */
    public function isInsideThemes($basename, $pathname, $depth)
    {
        $base = basename($this->upLevels($pathname, $depth - 1) ?? '');
        return $base === THEMES_DIR;
    }

    /**
     * Check if this folder or any parent is ignored
     *
     * @param string $basename
     * @param string $pathname
     * @param int $depth
     * @return bool
     */
    public function isInsideIgnored($basename, $pathname, $depth)
    {
        return $this->anyParents($basename, $pathname, $depth, function ($basename, $pathname, $depth) {
            return $this->isDirectoryIgnored($basename, $pathname, $depth);
        });
    }

    /**
     * Check if this folder is inside any module
     *
     * @param string $basename
     * @param string $pathname
     * @param int $depth
     * @return bool
     */
    public function isInsideModule($basename, $pathname, $depth)
    {
        return $this->anyParents($basename, $pathname, $depth, function ($basename, $pathname, $depth) {
            return $this->isDirectoryModule($basename, $pathname, $depth);
        });
    }

    /**
     * Check if any parents match the given callback
     *
     * @param string $basename
     * @param string $pathname
     * @param int $depth
     * @param callable $callback
     * @return bool
     */
    protected function anyParents($basename, $pathname, $depth, $callback)
    {
        // Check all ignored dir up the path
        while ($depth >= 0) {
            $ignored = $callback($basename, $pathname, $depth);
            if ($ignored) {
                return true;
            }
            $pathname = dirname($pathname ?? '');
            $basename = basename($pathname ?? '');
            $depth--;
        }
        return false;
    }

    /**
     * Check if the given dir is a module root (not a subdir)
     *
     * @param string $basename
     * @param string $pathname
     * @param string $depth
     * @return bool
     */
    public function isDirectoryModule($basename, $pathname, $depth)
    {
        // Depth can either be 0, 1, or 3 (if and only if inside vendor)
        $inVendor = $this->isInsideVendor($basename, $pathname, $depth);
        if ($depth > 0 && $depth !== ($inVendor ? 3 : 1)) {
            return false;
        }

        // True if config file exists
        if (file_exists($pathname . '/' . ManifestFileFinder::CONFIG_FILE)) {
            return true;
        }

        // True if config dir exists
        if (file_exists($pathname . '/' . ManifestFileFinder::CONFIG_DIR)) {
            return true;
        }

        return false;
    }

    /**
     * Get a parent path the given levels above
     *
     * @param string $pathname
     * @param int $depth Number of parents to rise
     * @return string
     */
    protected function upLevels($pathname, $depth)
    {
        if ($depth < 0) {
            return null;
        }
        while ($depth--) {
            $pathname = dirname($pathname ?? '');
        }
        return $pathname;
    }

    /**
     * Get all ignored directories
     *
     * @return array
     */
    protected function getIgnoredDirs()
    {
        $ignored = [ManifestFileFinder::LANG_DIR, 'node_modules'];
        if ($this->getOption('ignore_tests')) {
            $ignored[] = ManifestFileFinder::TESTS_DIR;
        }
        return $ignored;
    }

    /**
     * Check if the given directory is ignored
     * @param string $basename
     * @param string $pathname
     * @param string $depth
     * @return bool
     */
    public function isDirectoryIgnored($basename, $pathname, $depth)
    {
        // Don't ignore root
        if ($depth === 0) {
            return false;
        }

        // Check if manifest-ignored is present
        if (file_exists($pathname . '/' . ManifestFileFinder::EXCLUDE_FILE)) {
            return true;
        }

        // Check if directory name is ignored
        $ignored = $this->getIgnoredDirs();
        if (in_array($basename, $ignored ?? [])) {
            return true;
        }

        // Ignore these dirs in the root only
        if ($depth === 1 && in_array($basename, [ASSETS_DIR, RESOURCES_DIR])) {
            return true;
        }

        return false;
    }
}
