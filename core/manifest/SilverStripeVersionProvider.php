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
			return array();
		}

		$lockData = array();
		if ($cache) {
			$cache = SS_Cache::factory(
				'SilverStripeVersionProvider_composerlock',
				'Output',
				array('disable-segmentation' => true)
			);
			$cacheKey = filemtime($composerLockPath);
			if ($versions = $cache->load($cacheKey)) {
				$lockData = json_decode($versions, true);
			}
		}

		if (empty($lockData) && $jsonData = file_get_contents($composerLockPath)) {
			$lockData = json_decode($jsonData, true);

			if ($cache) {
				$cache->save(json_encode($lockData), $cacheKey);
			}
		}

		return $lockData;
	}
}
