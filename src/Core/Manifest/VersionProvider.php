<?php

namespace SilverStripe\Core\Manifest;

use InvalidArgumentException;
use SilverStripe\Core\Config\Config;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;

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
     * @var array
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
        $key = sprintf('%s-%s', $this->getComposerLockPath(), 'all');
        $version = $this->getCachedValue($key);
        if ($version) {
            return $version;
        }
        $modules = $this->getModules();
        $lockModules = $this->getModuleVersionFromComposer(array_keys($modules ?? []));
        $moduleVersions = [];
        foreach ($modules as $module => $title) {
            if (!array_key_exists($module, $lockModules ?? [])) {
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
        $key = sprintf('%s-%s', $this->getComposerLockPath(), $module);
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
     * @param array $modules
     * @return array
     */
    private function filterModules(array $modules)
    {
        $accountModule = [];
        foreach ($modules as $module => $value) {
            if (!preg_match('#^([a-z0-9\-]+)/([a-z0-9\-]+)$#', $module ?? '', $m)) {
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
     * @return array
     */
    public function getModules()
    {
        $modules = Config::inst()->get(self::class, 'modules');
        return !empty($modules) ? $modules : ['silverstripe/framework' => 'Framework'];
    }

    /**
     * Tries to obtain version number from composer.lock if it exists
     *
     * @param array $modules
     * @return array
     */
    public function getModuleVersionFromComposer($modules = [])
    {
        $versions = [];
        $lockData = $this->getComposerLock();
        if ($lockData && !empty($lockData['packages'])) {
            foreach ($lockData['packages'] as $package) {
                if (in_array($package['name'], $modules ?? []) && isset($package['version'])) {
                    $versions[$package['name']] = $package['version'];
                }
            }
        }
        return $versions;
    }

    /**
     * Load composer.lock's contents and return it
     *
     * @param bool $cache
     * @return array
     */
    protected function getComposerLock($cache = true)
    {
        $composerLockPath = $this->getComposerLockPath();
        if (!file_exists($composerLockPath ?? '')) {
            return [];
        }

        $lockData = [];
        $jsonData = file_get_contents($composerLockPath ?? '');

        if ($cache) {
            $cache = Injector::inst()->get(CacheInterface::class . '.VersionProvider_composerlock');
            $cacheKey = md5($jsonData ?? '');
            if ($versions = $cache->get($cacheKey)) {
                $lockData = json_decode($versions ?? '', true);
            }
        }

        if (empty($lockData) && $jsonData) {
            $lockData = json_decode($jsonData ?? '', true);

            if ($cache) {
                $cache->set($cacheKey, $jsonData);
            }
        }

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
