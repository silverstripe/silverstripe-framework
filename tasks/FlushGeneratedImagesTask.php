<?php
/**
 * Remove all cached/generated images that have been created as the result of a manipulation method being called on a
 * {@link Image} object
 *
 * @package framework
 * @subpackage filesystem
 */
class FlushGeneratedImagesTask extends BuildTask {
	
	protected $title = 'Flush Generated Images Task';
	
	protected $description = 'Remove all cached/generated images created as the result of an image manipulation';
	
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
	 * Actually clear out all the images
	 */
	public function run($request) {
		$processedImages = 0;
		$removedItems    = 0;
		
		if($images = DataObject::get('Image')) foreach($images as $image) {
			if($deleted = $image->deleteFormattedImages()) {
				$removedItems += $deleted;
			}
			
			$processedImages++;
		}
		
		echo "Removed $removedItems generated images from $processedImages Image objects stored in the Database.";
	}
	
}
