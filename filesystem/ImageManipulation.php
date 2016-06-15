<?php

namespace SilverStripe\Filesystem;

use Config;
use Convert;

use SilverStripe\Filesystem\Storage\DBFile;
use Image_Backend;
use Injector;
use InvalidArgumentException;
use SilverStripe\Filesystem\Storage\AssetContainer;
use SilverStripe\Filesystem\Storage\AssetStore;

use SilverStripe\ORM\FieldType\DBField;


/**
 * Provides image manipulation functionality.
 * Provides limited thumbnail generation functionality for non-image files.
 * Should only be applied to implementors of AssetContainer
 *
 * Allows raw images to be resampled via Resampled()
 *
 * Image scaling manipluations, including:
 * - Fit()
 * - FitMax()
 * - ScaleWidth()
 * - ScaleMaxWidth()
 * - ScaleHeight()
 * - ScaleMaxHeight()
 * - ResizedImage()
 *
 * Image cropping manipulations, including:
 * - CropHeight()
 * - CropWidth()
 * - Fill()
 * - FillMax()
 *
 * Thumbnail generation methods including:
 * - Icon()
 * - CMSThumbnail()
 *
 * @mixin AssetContainer
 */
trait ImageManipulation {

	/**
	 * @return string Data from the file in this container
	 */
	abstract public function getString();

	/**
	 * @return resource Data stream to the asset in this container
	 */
	abstract public function getStream();

	/**
	 * @param bool $grant Ensures that the url for any protected assets is granted for the current user.
	 * @return string public url to the asset in this container
	 */
	abstract public function getURL($grant = true);

	/**
	 * @return string The absolute URL to the asset in this container
	 */
	abstract public function getAbsoluteURL();

	/**
	 * Get metadata for this file
	 *
	 * @return array|null File information
	 */
	abstract public function getMetaData();

	/**
	 * Get mime type
	 *
	 * @return string Mime type for this file
	 */
	abstract public function getMimeType();

	/**
	 * Return file size in bytes.
	 *
	 * @return int
	 */
	abstract public function getAbsoluteSize();

	/**
	 * Determine if this container has a valid value
	 *
	 * @return bool Flag as to whether the file exists
	 */
	abstract public function exists();

	/**
	 * Get value of filename
	 *
	 * @return string
	 */
	abstract public function getFilename();

	/**
	 * Get value of hash
	 *
	 * @return string
	 */
	abstract public function getHash();

	/**
	 * Get value of variant
	 *
	 * @return string
	 */
	abstract public function getVariant();

	/**
	 * Determine if a valid non-empty image exists behind this asset
	 *
	 * @return bool
	 */
	abstract public function getIsImage();

	/**
	 * @config
	 * @var bool Force all images to resample in all cases
	 */
	private static $force_resample = true;

	/**
	 * @config
	 * @var int The width of an image thumbnail in a strip.
	 */
	private static $strip_thumbnail_width = 50;

	/**
	 * @config
	 * @var int The height of an image thumbnail in a strip.
	 */
	private static $strip_thumbnail_height = 50;

	/**
	 * The width of an image thumbnail in the CMS.
	 *
	 * @config
	 * @var int
	 */
	private static $cms_thumbnail_width = 100;

	/**
	 * The height of an image thumbnail in the CMS.
	 *
	 * @config
	 * @var int
	 */
	private static $cms_thumbnail_height = 100;

	/**
	 * The width of an image preview in the Asset section
	 *
	 * This thumbnail is only sized to width.
	 *
	 * @config
	 * @var int
	 */
	private static $asset_preview_width = 400;

	/**
	 * Fit image to specified dimensions and fill leftover space with a solid colour (default white). Use in templates with $Pad.
	 *
	 * @param integer $width The width to size to
	 * @param integer $height The height to size to
	 * @param string $backgroundColor
	 * @return AssetContainer
	 */
	public function Pad($width, $height, $backgroundColor = 'FFFFFF') {
		if($this->isSize($width, $height)) {
			return $this;
		}

		$variant = $this->variantName(__FUNCTION__, $width, $height, $backgroundColor);
		return $this->manipulateImage(
			$variant,
			function(Image_Backend $backend) use($width, $height, $backgroundColor) {
				return $backend->paddedResize($width, $height, $backgroundColor);
			}
		);
	}

	/**
	 * Forces the image to be resampled, if possible
	 *
	 * @return AssetContainer
	 */
	public function Resampled() {
		// If image is already resampled, return self reference
		$variant = $this->getVariant();
		if($variant) {
			return $this;
		}

		// Resample, but fallback to original object
		$result = $this->manipulateImage(__FUNCTION__, function(Image_Backend $backend) {
			return $backend;
		});
		if($result) {
			return $result;
		}
		return $this;
	}

	/**
	 * Update the url to point to a resampled version if forcing
	 *
	 * @param string $url
	 */
	public function updateURL(&$url) {
		// Skip if resampling is off, or is already resampled, or is not an image
		if(!Config::inst()->get(get_class($this), 'force_resample') || $this->getVariant() || !$this->getIsImage()) {
			return;
		}

		// Attempt to resample
		$resampled = $this->Resampled();
		if(!$resampled) {
			return;
		}

		// Only update if resampled file is a smaller file size
		if($resampled->getAbsoluteSize() < $this->getAbsoluteSize()) {
			$url = $resampled->getURL();
		}
	}


	/**
	 * Generate a resized copy of this image with the given width & height.
	 * This can be used in templates with $ResizedImage but should be avoided,
	 * as it's the only image manipulation function which can skew an image.
	 *
	 * @param integer $width Width to resize to
	 * @param integer $height Height to resize to
	 * @return AssetContainer
	 */
	public function ResizedImage($width, $height) {
		if($this->isSize($width, $height)) {
			return $this;
		}

		$variant = $this->variantName(__FUNCTION__, $width, $height);
		return $this->manipulateImage($variant, function(Image_Backend $backend) use ($width, $height) {
			return $backend->resize($width, $height);
		});
	}

	/**
	 * Scale image proportionally to fit within the specified bounds
	 *
	 * @param integer $width The width to size within
	 * @param integer $height The height to size within
	 * @return AssetContainer
	 */
	public function Fit($width, $height) {
		// Prevent divide by zero on missing/blank file
		if(!$this->getWidth() || !$this->getHeight()) {
			return null;
		}

		// Check if image is already sized to the correct dimension
		$widthRatio = $width / $this->getWidth();
		$heightRatio = $height / $this->getHeight();

		if( $widthRatio < $heightRatio ) {
			// Target is higher aspect ratio than image, so check width
			if($this->isWidth($width)) {
				return $this;
			}
		} else {
			// Target is wider or same aspect ratio as image, so check height
			if($this->isHeight($height)) {
				return $this;
			}
		}

		// Item must be regenerated
		$variant = $this->variantName(__FUNCTION__, $width, $height);
		return $this->manipulateImage($variant, function(Image_Backend $backend) use ($width, $height) {
			return $backend->resizeRatio($width, $height);
		});
	}

	/**
	 * Proportionally scale down this image if it is wider or taller than the specified dimensions.
	 * Similar to Fit but without up-sampling. Use in templates with $FitMax.
	 *
	 * @uses ScalingManipulation::Fit()
	 * @param integer $width The maximum width of the output image
	 * @param integer $height The maximum height of the output image
	 * @return AssetContainer
	 */
	public function FitMax($width, $height) {
		return $this->getWidth() > $width || $this->getHeight() > $height
			? $this->Fit($width,$height)
			: $this;
	}


	/**
	 * Scale image proportionally by width. Use in templates with $ScaleWidth.
	 *
	 * @param integer $width The width to set
	 * @return AssetContainer
	 */
	public function ScaleWidth($width) {
		if($this->isWidth($width)) {
			return $this;
		}

		$variant = $this->variantName(__FUNCTION__, $width);
		return $this->manipulateImage($variant, function(Image_Backend $backend) use ($width) {
			return $backend->resizeByWidth($width);
		});
	}

	/**
	 * Proportionally scale down this image if it is wider than the specified width.
	 * Similar to ScaleWidth but without up-sampling. Use in templates with $ScaleMaxWidth.
	 *
	 * @uses ScalingManipulation::ScaleWidth()
	 * @param integer $width The maximum width of the output image
	 * @return AssetContainer
	 */
	public function ScaleMaxWidth($width) {
		return $this->getWidth() > $width
			? $this->ScaleWidth($width)
			: $this;
	}

	/**
	 * Scale image proportionally by height. Use in templates with $ScaleHeight.
	 *
	 * @param int $height The height to set
	 * @return AssetContainer
	 */
	public function ScaleHeight($height) {
		if($this->isHeight($height)) {
			return $this;
		}

		$variant = $this->variantName(__FUNCTION__, $height);
		return $this->manipulateImage($variant, function(Image_Backend $backend) use ($height) {
			return $backend->resizeByHeight($height);
		});
	}

	/**
	 * Proportionally scale down this image if it is taller than the specified height.
	 * Similar to ScaleHeight but without up-sampling. Use in templates with $ScaleMaxHeight.
	 *
	 * @uses ScalingManipulation::ScaleHeight()
	 * @param integer $height The maximum height of the output image
	 * @return AssetContainer
	 */
	public function ScaleMaxHeight($height) {
		return $this->getHeight() > $height
			? $this->ScaleHeight($height)
			: $this;
	}


	/**
	 * Crop image on X axis if it exceeds specified width. Retain height.
	 * Use in templates with $CropWidth. Example: $Image.ScaleHeight(100).$CropWidth(100)
	 *
	 * @uses CropManipulation::Fill()
	 * @param integer $width The maximum width of the output image
	 * @return AssetContainer
	 */
	public function CropWidth($width) {
		return $this->getWidth() > $width
			? $this->Fill($width, $this->getHeight())
			: $this;
	}

	/**
	 * Crop image on Y axis if it exceeds specified height. Retain width.
	 * Use in templates with $CropHeight. Example: $Image.ScaleWidth(100).CropHeight(100)
	 *
	 * @uses CropManipulation::Fill()
	 * @param integer $height The maximum height of the output image
	 * @return AssetContainer
	 */
	public function CropHeight($height) {
		return $this->getHeight() > $height
			? $this->Fill($this->getWidth(), $height)
			: $this;
	}

	/**
	 * Crop this image to the aspect ratio defined by the specified width and height,
	 * then scale down the image to those dimensions if it exceeds them.
	 * Similar to Fill but without up-sampling. Use in templates with $FillMax.
	 *
	 * @uses ImageManipulation::Fill()
	 * @param integer $width The relative (used to determine aspect ratio) and maximum width of the output image
	 * @param integer $height The relative (used to determine aspect ratio) and maximum height of the output image
	 * @return AssetContainer
	 */
	public function FillMax($width, $height) {
		// Prevent divide by zero on missing/blank file
		if(!$this->getWidth() || !$this->getHeight()) {
			return null;
		}

		// Is the image already the correct size?
		if ($this->isSize($width, $height)) {
			return $this;
		}

		// If not, make sure the image isn't upsampled
		$imageRatio = $this->getWidth() / $this->getHeight();
		$cropRatio = $width / $height;
		// If cropping on the x axis compare heights
		if ($cropRatio < $imageRatio && $this->getHeight() < $height) {
			return $this->Fill($this->getHeight() * $cropRatio, $this->getHeight());
		}

		// Otherwise we're cropping on the y axis (or not cropping at all) so compare widths
		if ($this->getWidth() < $width) {
			return $this->Fill($this->getWidth(), $this->getWidth() / $cropRatio);
		}

		return $this->Fill($width, $height);
	}

	/**
	 * Resize and crop image to fill specified dimensions.
	 * Use in templates with $Fill
	 *
	 * @param integer $width Width to crop to
	 * @param integer $height Height to crop to
	 * @return AssetContainer
	 */
	public function Fill($width, $height) {
		if($this->isSize($width, $height)) {
			return $this;
		}

		// Resize
		$variant = $this->variantName(__FUNCTION__, $width, $height);
		return $this->manipulateImage($variant, function(Image_Backend $backend) use ($width, $height) {
			return $backend->croppedResize($width, $height);
		});
	}

	/**
	 * Default CMS thumbnail
	 *
	 * @return DBFile|DBHTMLText Either a resized thumbnail, or html for a thumbnail icon
	 */
	public function CMSThumbnail() {
		$width = (int)Config::inst()->get(get_class($this), 'cms_thumbnail_width');
		$height = (int)Config::inst()->get(get_class($this), 'cms_thumbnail_height');
		return $this->ThumbnailIcon($width, $height);
	}

	/**
	 * Generates a thumbnail for use in the gridfield view
	 *
	 * @return AssetContainer|DBHTMLText Either a resized thumbnail, or html for a thumbnail icon
	 */
	public function StripThumbnail() {
		$width = (int)Config::inst()->get(get_class($this), 'strip_thumbnail_width');
		$height = (int)Config::inst()->get(get_class($this), 'strip_thumbnail_height');
		return $this->ThumbnailIcon($width, $height);
	}

	/**
	 * Get preview for this file
	 *
	 * @return AssetContainer|DBHTMLText Either a resized thumbnail, or html for a thumbnail icon
	 */
	public function PreviewThumbnail() {
		$width = (int)Config::inst()->get(get_class($this), 'asset_preview_width');
		return $this->ScaleWidth($width)  ?: $this->IconTag();
	}

	/**
	 * Default thumbnail generation for Images
	 *
	 * @param int $width
	 * @param int $height
	 * @return AssetContainer
	 */
	public function Thumbnail($width, $height) {
		return $this->Pad($width, $height);
	}

	/**
	 * Thubnail generation for all file types.
	 *
	 * Resizes images, but returns an icon <img /> tag if this is not a resizable image
	 *
	 * @param int $width
	 * @param int $height
	 * @return AssetContainer|DBHTMLText
	 */
	public function ThumbnailIcon($width, $height) {
		return $this->Thumbnail($width, $height) ?: $this->IconTag();
	}

	/**
	 * Get HTML for img containing the icon for this file
	 *
	 * @return DBHTMLText
	 */
	public function IconTag() {
		return DBField::create_field(
			'HTMLText',
			'<img src="' . Convert::raw2att($this->getIcon()) . '" />'
		);
	}

	/**
	 * Get URL to thumbnail of the given size.
	 *
	 * May fallback to default icon
	 *
	 * @param int $width
	 * @param int $height
	 * @return string
	 */
	public function ThumbnailURL($width, $height) {
		$thumbnail = $this->Thumbnail($width, $height);
		if($thumbnail) {
			return $thumbnail->getURL();
		}
		return $this->getIcon();
	}

	/**
	 * Return the relative URL of an icon for the file type,
	 * based on the {@link appCategory()} value.
	 * Images are searched for in "framework/images/app_icons/".
	 *
	 * @return string URL to icon
	 */
	public function getIcon() {
		$filename = $this->getFilename();
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		return \File::get_icon_for_extension($ext);
	}

	/**
	 * Get Image_Backend instance for this image
	 *
	 * @return Image_Backend
	 */
	public function getImageBackend() {
		if(!$this->getIsImage()) {
			return null;
		}

		// Create backend for this object
		return Injector::inst()->createWithArgs('Image_Backend', array($this));
	}

	/**
	 * Get the dimensions of this Image.
	 *
	 * @param string $dim One of the following:
	 *  - "string": return the dimensions in string form
	 *  - "array": it'll return the raw result
	 *  - 0: return the height
	 *  - 1: return the width
	 * @return string|int|array|null
	 */
	public function getDimensions($dim = "string") {
		if(!$this->getIsImage()) {
			return null;
		}

		$content = $this->getString();
		if(!$content) {
			return null;
		}

		// Get raw content
		$size = getimagesizefromstring($content);
		if($size === false) {
			return null;
		}

		if($dim === 'array') {
			return $size;
		}

		// Get single dimension
		if(is_numeric($dim)) {
			return $size[$dim];
		}

		return "$size[0]x$size[1]";
	}

	/**
	 * Get the width of this image.
	 *
	 * @return int
	 */
	public function getWidth() {
		return $this->getDimensions(0);
	}

	/**
	 * Get the height of this image.
	 *
	 * @return int
	 */
	public function getHeight() {
		return $this->getDimensions(1);
	}

	/**
	 * Get the orientation of this image.
	 *
	 * @return int ORIENTATION_SQUARE | ORIENTATION_PORTRAIT | ORIENTATION_LANDSCAPE
	 */
	public function getOrientation() {
		$width = $this->getWidth();
		$height = $this->getHeight();
		if($width > $height) {
			return Image_Backend::ORIENTATION_LANDSCAPE;
		} elseif($height > $width) {
			return Image_Backend::ORIENTATION_PORTRAIT;
		} else {
			return Image_Backend::ORIENTATION_SQUARE;
		}
	}

	/**
	 * Determine if this image is of the specified size
	 *
	 * @param integer $width Width to check
	 * @param integer $height Height to check
	 * @return boolean
	 */
	public function isSize($width, $height) {
		return $this->isWidth($width) && $this->isHeight($height);
	}

	/**
	 * Determine if this image is of the specified width
	 *
	 * @param integer $width Width to check
	 * @return boolean
	 */
	public function isWidth($width) {
		if(empty($width) || !is_numeric($width)) {
			throw new InvalidArgumentException("Invalid value for width");
		}
		return $this->getWidth() == $width;
	}

	/**
	 * Determine if this image is of the specified width
	 *
	 * @param integer $height Height to check
	 * @return boolean
	 */
	public function isHeight($height) {
		if(empty($height) || !is_numeric($height)) {
			throw new InvalidArgumentException("Invalid value for height");
		}
		return $this->getHeight() == $height;
	}

	/**
	 * Wrapper for manipulate that passes in and stores Image_Backend objects instead of tuples
	 *
	 * @param string $variant
	 * @param callable $callback Callback which takes an Image_Backend object, and returns an Image_Backend result
	 * @return DBFile The manipulated file
	 */
	public function manipulateImage($variant, $callback) {
		return $this->manipulate(
			$variant,
			function(AssetStore $store, $filename, $hash, $variant) use ($callback) {
				/** @var Image_Backend $backend */
				$backend = $this->getImageBackend();
				if(!$backend) {
					return null;
				}
				$backend = $callback($backend);
				if(!$backend) {
					return null;
				}

				return $backend->writeToStore(
					$store, $filename, $hash, $variant,
					array('conflict' => AssetStore::CONFLICT_USE_EXISTING)
				);
			}
		);
	}

	/**
	 * Generate a new DBFile instance using the given callback if it hasn't been created yet, or
	 * return the existing one if it has.
	 *
	 * @param string $variant name of the variant to create
	 * @param callable $callback Callback which should return a new tuple as an array.
	 * This callback will be passed the backend, filename, hash, and variant
	 * This will not be called if the file does not
	 * need to be created.
	 * @return DBFile The manipulated file
	 */
	public function manipulate($variant, $callback) {
		// Verify this manipulation is applicable to this instance
		if(!$this->exists()) {
			return null;
		}

		// Build output tuple
		$filename = $this->getFilename();
		$hash = $this->getHash();
		$existingVariant = $this->getVariant();
		if($existingVariant) {
			$variant = $existingVariant . '_' . $variant;
		}

		// Skip empty files (e.g. Folder does not have a hash)
		if(empty($filename) || empty($hash)) {
			return null;
		}

		// Create this asset in the store if it doesn't already exist,
		// otherwise use the existing variant
		$store = Injector::inst()->get('AssetStore');
		$result = null;
		if(!$store->exists($filename, $hash, $variant)) {
			$result = call_user_func($callback, $store, $filename, $hash, $variant);
		} else {
			$result = array(
				'Filename' => $filename,
				'Hash' => $hash,
				'Variant' => $variant
			);
		}

		// Callback may fail to perform this manipulation (e.g. resize on text file)
		if(!$result) {
			return null;
		}

		// Store result in new DBFile instance
		return DBField::create_field('DBFile', $result)
			->setOriginal($this);
	}

	/**
	 * Name a variant based on a format with arbitrary parameters
	 *
	 * @param string $format The format name.
	 * @param mixed ...$args Additional arguments
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function variantName($format) {
		$args = func_get_args();
		array_shift($args);
		return $format . Convert::base64url_encode($args);
	}
}
