<?php

namespace SilverStripe\Core\Manifest;

use SilverStripe\Core\Config\Config;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Convert;
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
    /**
     * Gets a comma delimited string of package titles and versions
     *
     * @return string
     */
    public function getVersion()
    {
        $modules = $this->getModules();
        $lockModules = $this->getModuleVersionFromComposer(array_keys($modules));
        $output = [];
        foreach ($modules as $module => $title) {
            $version = isset($lockModules[$module])
                ? $lockModules[$module]
                : _t(__CLASS__ . '.VERSIONUNKNOWN', 'Unknown');
            $output[] = $title . ': ' . $version;
        }
        return implode(', ', $output);
    }

    /**
     * Gets the configured core modules to use for the SilverStripe application version. Filtering
     * is used to ensure that modules can turn the result off for other modules, e.g. CMS can disable Framework.
     *
     * @return array
     */
    public function getModules()
    {
        $modules = Config::inst()->get(self::class, 'modules');
        return $modules ? array_filter($modules) : [];
    }

    /**
     * Tries to obtain version number from composer.lock if it exists
     *
     * @param  array $modules
     * @return array
     */
    public function getModuleVersionFromComposer($modules = [])
    {
        $versions = [];
        $lockData = $this->getComposerLock();
        if ($lockData && !empty($lockData['packages'])) {
            foreach ($lockData['packages'] as $package) {
                if (in_array($package['name'], $modules) && isset($package['version'])) {
                    $versions[$package['name']] = $package['version'];
                }
            }
        }
        return $versions;
    }

    /**
     * Load composer.lock's contents and return it
     *
     * @param  bool $cache
     * @return array
     */
    protected function getComposerLock($cache = true)
    {
        $composerLockPath = BASE_PATH . '/composer.lock';
        if (!file_exists($composerLockPath)) {
            return [];
        }

        $lockData = [];
        $jsonData = file_get_contents($composerLockPath);

        if ($cache) {
            $cache = Injector::inst()->get(CacheInterface::class . '.VersionProvider_composerlock');
            $cacheKey = md5($jsonData);
            if ($versions = $cache->get($cacheKey)) {
                $lockData = Convert::json2array($versions);
            }
        }

        if (empty($lockData) && $jsonData) {
            $lockData = Convert::json2array($jsonData);

            if ($cache) {
                $cache->set($cacheKey, $jsonData);
            }
        }

        return $lockData;
    }
}
