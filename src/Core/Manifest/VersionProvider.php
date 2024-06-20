<?php

namespace SilverStripe\Core\Manifest;

use InvalidArgumentException;
use Composer\InstalledVersions;
use SilverStripe\Dev\Deprecation;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

/**
 * The version provider will look up configured modules and examine the composer.lock file
 * to find the current version installed for each. This is used for the logo title in the CMS
 * via {@link LeftAndMain::CMSVersion()}
 *
 * Example configuration:
 *
 * <code>
 * SilverStripe\Core\Manifest\VersionProvider:
 *   modules:
 *     # package/name: Package Title
 *     silverstripe/framework: Framework
 *     silverstripe/cms: CMS
 * </code>
 */
class VersionProvider
{
    use Configurable;
    use Injectable;

    /**
     * @var array<string,string>
     */
    private static $modules = [
        'silverstripe/framework' => 'Framework',
        'silverstripe/recipe-core' => 'Core Recipe',
    ];

    /**
     * Gets a comma delimited string of package titles and versions
     *
     * @return string
     */
    public function getVersion()
    {
        $key = preg_replace("/[^A-Za-z0-9]/", '_', $this->getComposerLockPath() . '_all');
        $version = $this->getCachedValue($key);
        if ($version) {
            return $version;
        }
        $modules = $this->getModules();
        $lockModules = $this->getModuleVersionFromComposer(array_keys($modules));
        $moduleVersions = [];
        foreach ($modules as $module => $title) {
            if (!array_key_exists($module, $lockModules)) {
                continue;
            }
            $version = $lockModules[$module];
            $moduleVersions[$module] = [$title, $version];
        }
        $moduleVersions = $this->filterModules($moduleVersions);
        $ret = [];
        foreach ($moduleVersions as $module => $value) {
            list($title, $version) = $value;
            $ret[] = "$title: $version";
        }
        $version = implode(', ', $ret);
        if ($version) {
            $this->setCacheValue($key, $version);
        }
        return $version;
    }

    /**
     * Get the version of a specific module
     *
     * @param string $module - e.g. silverstripe/framework
     * @return string - e.g. 4.11
     */
    public function getModuleVersion(string $module): string
    {
        $key = preg_replace("/[^A-Za-z0-9]/", '_', $this->getComposerLockPath() . '_' . $module);
        $version = $this->getCachedValue($key);
        if ($version) {
            return $version;
        }
        $version = $this->getModuleVersionFromComposer([$module])[$module] ?? '';
        if ($version) {
            $this->setCacheValue($key, $version);
        }
        return $version;
    }

    /**
     * @return CacheInterface
     */
    private function getCache(): CacheInterface
    {
        return Injector::inst()->get(CacheInterface::class . '.VersionProvider');
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function getCachedValue(string $key): string
    {
        $cache = $this->getCache();
        try {
            if ($cache->has($key)) {
                return $cache->get($key);
            }
        } catch (InvalidArgumentException $e) {
        }
        return '';
    }

    /**
     * @param string $key
     * @param string $value
     */
    private function setCacheValue(string $key, string $value): void
    {
        $cache = $this->getCache();
        try {
            $cache->set($key, $value);
        } catch (InvalidArgumentException $e) {
        }
    }

    /**
     * Filter modules to only use the last module from a git repo, for example
     *
     * [
     *   silverstripe/framework => ['Framework', 1.1.1'],
     *   silverstripe/cms => ['CMS', 2.2.2'],
     *   silverstripe/recipe-cms => ['CMS Recipe', '3.3.3'],
     *   cwp/cwp-core => ['CWP', '4.4.4']
     * ]
     * =>
     * [
     *   silverstripe/recipe-cms => ['CMS Recipe', '3.3.3'],
     *   cwp/cwp-core => ['CWP', '4.4.4']
     * ]
     *
     * @param array<string,array<int,string>> $modules
     * @return array<string,array<int,string>>
     */
    private function filterModules(array $modules)
    {
        $accountModule = [];
        foreach ($modules as $module => $value) {
            if (!preg_match('#^([a-z0-9\-]+)/([a-z0-9\-]+)$#', $module, $m)) {
                continue;
            }
            $account = $m[1];
            $accountModule[$account] = [$module, $value];
        }
        $ret = [];
        foreach ($accountModule as $account => $arr) {
            list($module, $value) = $arr;
            $ret[$module] = $value;
        }
        return $ret;
    }

    /**
     * Gets the configured core modules to use for the SilverStripe application version
     *
     * @return array<string,string>
     */
    public function getModules()
    {
        $modules = Config::inst()->get(VersionProvider::class, 'modules');
        return !empty($modules) ? $modules : ['silverstripe/framework' => 'Framework'];
    }

    /**
     * Tries to obtain version number from composer.lock if it exists
     *
     * @param array<string> $modules
     * @return array<string|string>
     */
    public function getModuleVersionFromComposer($modules = [])
    {
        $versions = [];
        foreach ($modules as $module) {
            if (!InstalledVersions::isInstalled($module)) {
                continue;
            }
            $versions[$module] = InstalledVersions::getPrettyVersion($module);
        }
        return $versions;
    }

    /**
     * Load composer.lock's contents and return it
     *
     * @deprecated 5.1 Has been replaced by composer-runtime-api
     * @param bool $cache
     * @return array
     */
    protected function getComposerLock($cache = true)
    {
        Deprecation::notice("5.1", "Has been replaced by composer-runtime-api", Deprecation::SCOPE_METHOD);
        $composerLockPath = $this->getComposerLockPath();
        if (!file_exists($composerLockPath)) {
            return [];
        }

        $lockData = [];
        $jsonData = file_get_contents($composerLockPath);
        $jsonData = $jsonData ? $jsonData : '';
        $cacheKey = md5($jsonData);

        if ($cache) {
            $cache = Injector::inst()->get(CacheInterface::class . '.VersionProvider_composerlock');
            if ($versions = $cache->get($cacheKey)) {
                $lockData = json_decode($versions, true);
            }
        }

        if (empty($lockData) && $jsonData) {
            $lockData = json_decode($jsonData, true);

            if ($cache) {
                $cache->set($cacheKey, $jsonData);
            }
        }

        $lockData = $lockData ? $lockData : [];

        return $lockData;
    }

    /**
     * @return string
     */
    protected function getComposerLockPath(): string
    {
        return BASE_PATH . '/composer.lock';
    }
}
