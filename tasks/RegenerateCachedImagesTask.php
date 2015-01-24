<?php
/**
 * Regenerate all cached images that have been created as the result of a manipulation method being called on a
 * {@link Image} object
 *
 * @package framework
 * @subpackage filesystem
 */
class RegenerateCachedImagesTask extends BuildTask {

	protected $title = 'Regenerate Cached Images Task';

	protected $description = 'Regenerate all cached images created as the result of an image manipulation';

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
	 * Actually regenerate all the images
	 */
	public function run($request) {
		$processedImages   = 0;
		$regeneratedImages = 0;

		if($images = DataObject::get('Image')) foreach($images as $image) {
			if($generated = $image->regenerateFormattedImages()) {
				$regeneratedImages += $generated;
			}

			$processedImages++;
		}

		echo "Regenerated $regeneratedImages cached images from $processedImages Image objects stored in the Database.";
	}

}
