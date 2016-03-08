<?php

/**
 * Represents an Image
 *
 * @package framework
 * @subpackage filesystem
 */
class Image extends File {
	public function __construct($record = null, $isSingleton = false, $model = null) {
		parent::__construct($record, $isSingleton, $model);
		$this->File->setAllowedCategories('image/supported');
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->insertAfter(
			'LastEdited',
			new ReadonlyField("Dimensions", _t('AssetTableField.DIM','Dimensions') . ':')
		);
		return $fields;
	}

	public function getIsImage() {
		return true;
	}

	/**
	 * Helper method to regenerate all image links in the given HTML block, optionally resizing them if
	 * the image native size differs to the width and height properties on the <img /> tag
	 *
	 * @param string $value HTML value
	 * @return string value with links resampled
	 */
	public static function regenerate_html_links($value) {
		$htmlValue = Injector::inst()->create('HTMLValue', $value);

		// Resample images and add default attributes
		$imageElements = $htmlValue->getElementsByTagName('img');
		if($imageElements) foreach($imageElements as $imageElement) {
			$imageDO = null;
			$src = $imageElement->getAttribute('src');

			// Skip if this image has a shortcode 'src'
			if(preg_match('/^\[.+\]$/', $src)) {
				continue;
			}

			// strip any ?r=n data from the src attribute
			$src = preg_replace('/([^\?]*)\?r=[0-9]+$/i', '$1', $src);

			// Resample the images if the width & height have changed.
			$fileID = $imageElement->getAttribute('data-fileid');
			if($fileID && ($imageDO = File::get()->byID($fileID))) {
				$width  = (int)$imageElement->getAttribute('width');
				$height = (int)$imageElement->getAttribute('height');
				if($imageDO instanceof Image && $width && $height
					&& ($width != $imageDO->getWidth() || $height != $imageDO->getHeight())
				) {
					//Make sure that the resized image actually returns an image:
					$resized = $imageDO->ResizedImage($width, $height);
					if($resized) {
						$imageDO = $resized;
					}
				}
				$src = $imageDO->getURL();
			}

			// Update attributes, including intelligent defaults for alt and title
			$imageElement->setAttribute('src', $src);
			if(!$imageElement->getAttribute('alt')) {
				$imageElement->setAttribute('alt', '');
			}
			if(!$imageElement->getAttribute('title')) {
				$imageElement->setAttribute('title', '');
			}

			// Use this extension point to manipulate images inserted using TinyMCE,
			// e.g. add a CSS class, change default title
			self::singleton()
				->extend('regenerateImageHTML', $imageDO, $imageElement);
		}
		return $htmlValue->getContent();
	}
}
