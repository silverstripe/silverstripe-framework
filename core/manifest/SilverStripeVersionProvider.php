<?php

/**
 * The version provider will look up configured modules and examine the composer.lock file
 * to find the current version installed for each. This is used for the logo title in the CMS
 * via {@link LeftAndMain::CMSVersion()}
 *
 * Example configuration:
 *
 * <code>
 * SilverStripeVersionProvider:
 *   modules:
 *     # package/name: Package Title
 *     silverstripe/framework: Framework
 *     silverstripe/cms: CMS
 * </code>
 */
class SilverStripeVersionProvider
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
		$output = array();
		foreach ($modules as $module => $title) {
			$version = isset($lockModules[$module])
				? $lockModules[$module]
				: _t('SilverStripeVersionProvider.VERSIONUNKNOWN', 'Unknown');
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
		$modules = Config::inst()->get(get_class($this), 'modules');
		return array_filter($modules);
	}

	/**
	 * Tries to obtain version number from composer.lock if it exists
	 *
	 * @param  array $modules
	 * @return array
	 */
	public function getModuleVersionFromComposer($modules = array())
	{
		$versions = array();
		$composerLockPath = BASE_PATH . '/composer.lock';
		if (file_exists($composerLockPath)) {
			$cache = SS_Cache::factory('SilverStripeVersionProvider_composerlock');
			$cacheKey = filemtime($composerLockPath);
			$versions = $cache->load($cacheKey);
			if ($versions) {
				$versions = json_decode($versions, true);
			} else {
				$versions = array();
			}
			if (!$versions && $jsonData = file_get_contents($composerLockPath)) {
				$lockData = json_decode($jsonData);
				if ($lockData && isset($lockData->packages)) {
					foreach ($lockData->packages as $package) {
						if (in_array($package->name, $modules) && isset($package->version)) {
							$versions[$package->name] = $package->version;
						}
					}
					$cache->save(json_encode($versions), $cacheKey);
				}
			}
		}
		return $versions;
	}
}
