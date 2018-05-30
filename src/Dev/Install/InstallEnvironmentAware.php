<?php

namespace SilverStripe\Dev\Install;

use BadMethodCallException;
use SilverStripe\Core\Path;

/**
 * For classes which are aware of install, project, and environment state.
 *
 * These should be basic getters / setters that infer from current state.
 */
trait InstallEnvironmentAware
{
    /**
     * Base path
     * @var
     */
    protected $baseDir;

    /**
     * Init base path, or guess if able
     *
     * @param string|null $basePath
     */
    protected function initBaseDir($basePath)
    {
        if ($basePath) {
            $this->setBaseDir($basePath);
        } elseif (defined('BASE_PATH')) {
            $this->setBaseDir(BASE_PATH);
        } else {
            throw new BadMethodCallException("No BASE_PATH defined");
        }
    }

    /**
     * @param string $base
     * @return $this
     */
    protected function setBaseDir($base)
    {
        $this->baseDir = $base;
        return $this;
    }

    /**
     * Get base path for this installation
     *
     * @return string
     */
    public function getBaseDir()
    {
        return Path::normalise($this->baseDir) . DIRECTORY_SEPARATOR;
    }

    /**
     * Get path to public directory
     *
     * @return string
     */
    public function getPublicDir()
    {
        $base = $this->getBaseDir();
        $public = Path::join($base, 'public') . DIRECTORY_SEPARATOR;
        if (file_exists($public)) {
            return $public;
        }
        return $base;
    }

    /**
     * Check that a module exists
     *
     * @param string $dirname
     * @return bool
     */
    public function checkModuleExists($dirname)
    {
        // Mysite is base-only and doesn't need _config.php to be counted
        if (in_array($dirname, ['mysite', 'app'])) {
            return file_exists($this->getBaseDir() . $dirname);
        }

        $paths = [
            "vendor/silverstripe/{$dirname}/",
            "{$dirname}/",
        ];
        foreach ($paths as $path) {
            $checks = ['_config', '_config.php'];
            foreach ($checks as $check) {
                if (file_exists($this->getBaseDir() . $path . $check)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get project dir name.
     *
     * @return string 'app', or 'mysite' (deprecated)
     */
    protected function getProjectDir()
    {
        $base = $this->getBaseDir();
        if (is_dir($base . 'mysite')) {
            /** @deprecated 4.2..5.0 */
            return 'mysite';
        }

        // Default
        return 'app';
    }

    /**
     * Get src dir name for project
     *
     * @return string
     */
    protected function getProjectSrcDir()
    {
        $projectDir = $this->getProjectDir();
        if ($projectDir === 'mysite') {
            /** @deprecated 4.2..5.0 */
            return $projectDir . DIRECTORY_SEPARATOR . 'code';
        }

        // Default
        return $projectDir . DIRECTORY_SEPARATOR . 'src';
    }

    /**
     * Check if the web server is IIS and version greater than the given version.
     *
     * @param int $fromVersion
     * @return bool
     */
    public function isIIS($fromVersion = 7)
    {
        $webserver = $this->findWebserver();
        if (preg_match('#.*IIS/(?<version>[.\\d]+)$#', $webserver, $matches)) {
            return version_compare($matches['version'], $fromVersion, '>=');
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isApache()
    {
        return strpos($this->findWebserver(), 'Apache') !== false;
    }

    /**
     * Find the webserver software running on the PHP host.
     *
     * @return string|false Server software or boolean FALSE
     */
    public function findWebserver()
    {
        // Try finding from SERVER_SIGNATURE or SERVER_SOFTWARE
        if (!empty($_SERVER['SERVER_SIGNATURE'])) {
            $webserver = $_SERVER['SERVER_SIGNATURE'];
        } elseif (!empty($_SERVER['SERVER_SOFTWARE'])) {
            $webserver = $_SERVER['SERVER_SOFTWARE'];
        } else {
            return false;
        }

        return strip_tags(trim($webserver));
    }

    public function testApacheRewriteExists($moduleName = 'mod_rewrite')
    {
        if (function_exists('apache_get_modules') && in_array($moduleName, apache_get_modules())) {
            return true;
        }
        if (isset($_SERVER['HTTP_MOD_REWRITE']) && $_SERVER['HTTP_MOD_REWRITE'] == 'On') {
            return true;
        }
        if (isset($_SERVER['REDIRECT_HTTP_MOD_REWRITE']) && $_SERVER['REDIRECT_HTTP_MOD_REWRITE'] == 'On') {
            return true;
        }
        return false;
    }

    public function testIISRewriteModuleExists($moduleName = 'IIS_UrlRewriteModule')
    {
        if (isset($_SERVER[$moduleName]) && $_SERVER[$moduleName]) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determines if the web server has any rewriting capability.
     *
     * @return bool
     */
    public function hasRewritingCapability()
    {
        return ($this->testApacheRewriteExists() || $this->testIISRewriteModuleExists());
    }

    /**
     * Get "nice" database name without "Database" suffix
     *
     * @param string $databaseClass
     * @return string
     */
    public function getDatabaseTypeNice($databaseClass)
    {
        return substr($databaseClass, 0, -8);
    }

    /**
     * Get an instance of a helper class for the specific database.
     *
     * @param string $databaseClass e.g. MySQLDatabase or MSSQLDatabase
     * @return DatabaseConfigurationHelper
     */
    public function getDatabaseConfigurationHelper($databaseClass)
    {
        return DatabaseAdapterRegistry::getDatabaseConfigurationHelper($databaseClass);
    }
}
