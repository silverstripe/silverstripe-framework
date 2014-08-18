<?php
/**
 * Wipe the cache of failed image manipulations. When {@link GDBackend} attempts to resample an image, it will write
 * the attempted manipulation to the cache and remove it from the cache if the resample is successful. The objective
 * of the cache is to prevent fatal errors (for example from exceeded memory limits) from occurring more than once.
 *
 * @package framework
 * @subpackage filesystem
 */
class CleanImageManipulationCache extends BuildTask {

	protected $title = 'Clean Image Manipulation Cache';

	protected $description = 'Clean the failed image manipulation cache. Use this to allow SilverStripe to attempt
		to resample images that have previously failed to resample (for example if memory limits were exceeded).';

	/**
	 * Check that the user has appropriate permissions to execute this task
	 */
	public function init() {
		if(!Director::is_cli() && !Director::isDev() && !Permission::check('ADMIN')) {
			return Security::permissionFailure();
		}

		parent::init();
	}

	/**
	 * Clear out the image manipulation cache
	 * @param SS_HTTPRequest $request
	 */
	public function run($request) {
		$failedManipulations = 0;
		$processedImages = 0;
		$images = DataObject::get('Image');

		if($images && Image::get_backend() == "GDBackend") {
			$cache = SS_Cache::factory('GDBackend_Manipulations');

			foreach($images as $image) {
				$path = $image->getFullPath();

				if (file_exists($path)) {
					$key = md5(implode('_', array($path, filemtime($path))));

					if ($manipulations = unserialize($cache->load($key))) {
						$failedManipulations += count($manipulations);
						$processedImages++;
						$cache->remove($key);
					}
				}
			}
		}

		echo "Cleared $failedManipulations failed manipulations from
			$processedImages Image objects stored in the Database.";
	}

}
