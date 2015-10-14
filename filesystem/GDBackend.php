<?php

use SilverStripe\Filesystem\Storage\AssetContainer;
use SilverStripe\Filesystem\Storage\AssetStore;
/**
 * A wrapper class for GD-based images, with lots of manipulation functions.
 * @package framework
 * @subpackage filesystem
 */
class GDBackend extends Object implements Image_Backend, Flushable {

	/**
	 * GD Resource
	 *
	 * @var resource
	 */
	protected $gd;

	/**
	 * @var Zend_Cache_Core
	 */
	protected $cache;

	/**
	 * @var int
	 */
	protected $width;


	/**
	 * @var height
	 */
	protected $height;

	/**
	 * @var int
	 */
	protected $quality;

	/**
	 *
	 * @var int
	 */
	protected $interlace;

	/**
	 * @config
	 * @var integer
	 */
	private static $default_quality = 75;

	/**
	 * @config
	 * @var integer
	 */
	private static $image_interlace = 0;

	public function __construct(AssetContainer $assetContainer = null) {
		parent::__construct();
		$this->cache = SS_Cache::factory('GDBackend_Manipulations');

		if($assetContainer) {
			$this->loadFromContainer($assetContainer);
		}
	}

	public function loadFrom($path) {
		// If we're working with image resampling, things could take a while.  Bump up the time-limit
		increase_time_limit_to(300);
		$this->resetResource();

		// Skip if path is unavailable
		if(!file_exists($path)) {
			return;
		}
		$mtime = filemtime($path);

		// Skip if load failed before
		if($this->failedResample($path, $mtime)) {
			return;
		}

		// We use getimagesize instead of extension checking, because sometimes extensions are wrong.
		$meta = getimagesize($path);
		if($meta === false) {
			$this->markFailed($path, $mtime);
			return;
		}

		$gd = null;
		switch($meta[2]) {
			case 1:
				if(function_exists('imagecreatefromgif')) {
					$gd = imagecreatefromgif($path);
				}
				break;
			case 2:
				if(function_exists('imagecreatefromjpeg')) {
					$gd = imagecreatefromjpeg($path);
				}
				break;
			case 3:
				if(function_exists('imagecreatefrompng')) {
					$gd = imagecreatefrompng($path);
					if($gd) {
						imagesavealpha($gd, true); // save alphablending setting (important)
					}
				}
				break;
		}

		// image failed
		if($gd === false) {
			$this->markFailed($path, $mtime);
			return;
		}

		// Save
		$this->setImageResource($gd);
	}

	public function loadFromContainer(AssetContainer $assetContainer) {
		// If we're working with image resampling, things could take a while.  Bump up the time-limit
		increase_time_limit_to(300);
		$this->resetResource();

		// Skip non-existant files
		if(!$assetContainer->exists()) {
			return;
		}

		// Skip if failed before
		$filename = $assetContainer->getFilename();
		$hash = $assetContainer->getHash();
		$variant = $assetContainer->getVariant();
		if($this->failedResample($filename, $hash, $variant)) {
			return;
		}

		// Mark as potentially failed prior to creation, resetting this on success
		$content = $assetContainer->getString();
		$image = imagecreatefromstring($content);
		if($image === false) {
			$this->markFailed($filename, $hash, $variant);
			return;
		}
		
		imagealphablending($image, false);
		imagesavealpha($image, true); // save alphablending setting (important)
		$this->setImageResource($image);
	}

	/**
	 * Clear GD resource
	 */
	protected function resetResource(){
		// Set defaults and clear resource
		$this->setImageResource(null);
		$this->quality = $this->config()->default_quality;
		$this->interlace = $this->config()->image_interlace;
	}

	/**
	 * Assign or clear GD resource
	 *
	 * @param resource|null $resource
	 */
	public function setImageResource($resource) {
		$this->gd = $resource;
		$this->width = $resource ? imagesx($resource) : 0;
		$this->height = $resource ? imagesy($resource) : 0;
	}

	/**
	 * Get the currently assigned GD resource
	 *
	 * @return resource
	 */
	public function getImageResource() {
		return $this->gd;
	}

	/**
	 * Check if this image has previously crashed GD when attempting to open it - if it's opened
	 * successfully, the manipulation's cache key is removed.
	 *
	 * @param string $args,... Any number of args that identify this image
	 * @return bool True if failed
	 */
	public function failedResample() {
		$key = sha1(implode('|', func_get_args()));
		return (bool)$this->cache->load($key);
	}

	/**
	 * Mark a file as failed
	 *
	 * @param string $args,... Any number of args that identify this image
	 */
	protected function markFailed() {
		$key = sha1(implode('|', func_get_args()));
		$this->cache->save('1', $key);
	}

	/**
	 * Mark a file as succeeded
	 *
	 * @param string $args,... Any number of args that identify this image
	 */
	protected function markSucceeded() {
		$key = sha1(implode('|', func_get_args()));
		$this->cache->save('0', $key);
	}


	public function setQuality($quality) {
		$this->quality = $quality;
	}

	public function croppedResize($width, $height) {
		if(!$this->gd) {
			return null;
		}

		$width = round($width);
		$height = round($height);

		// Check that a resize is actually necessary.
		if ($width == $this->width && $height == $this->height) {
			return $this;
		}

		$newGD = imagecreatetruecolor($width, $height);

		// Preserves transparency between images
		imagealphablending($newGD, false);
		imagesavealpha($newGD, true);

		$destAR = $width / $height;
		if ($this->width > 0 && $this->height > 0 ){
			// We can't divide by zero theres something wrong.

			$srcAR = $this->width / $this->height;

			// Destination narrower than the source
			if($destAR < $srcAR) {
				$srcY = 0;
				$srcHeight = $this->height;

				$srcWidth = round( $this->height * $destAR );
				$srcX = round( ($this->width - $srcWidth) / 2 );

			// Destination shorter than the source
			} else {
				$srcX = 0;
				$srcWidth = $this->width;

				$srcHeight = round( $this->width / $destAR );
				$srcY = round( ($this->height - $srcHeight) / 2 );
			}

			imagecopyresampled($newGD, $this->gd, 0,0, $srcX, $srcY, $width, $height, $srcWidth, $srcHeight);
		}
		$output = clone $this;
		$output->setImageResource($newGD);
		return $output;
	}

	/**
	 * Resizes the image to fit within the given region.
	 * Behaves similarly to paddedResize but without the padding.
	 * @todo This method isn't very efficent
	 */
	public function fittedResize($width, $height) {
		$gd = $this->resizeByHeight($height);
		if($gd->width > $width) {
			$gd = $gd->resizeByWidth($width);
		}
		return $gd;
	}

	public function resize($width, $height) {
		if(!$this->gd) {
			return null;
		}

		if($width < 0 || $height < 0) {
			throw new InvalidArgumentException("Image resizing dimensions cannot be negative");
		}
		if(!$width && !$height) {
			throw new InvalidArgumentException("No dimensions given when resizing image");
		}
		if(!$width) {
			throw new InvalidArgumentException("Width not given when resizing image");
		}
		if(!$height) {
			throw new InvalidArgumentException("Height not given when resizing image");
		}

		//use whole numbers, ensuring that size is at least 1x1
		$width = max(1, round($width));
		$height = max(1, round($height));

		// Check that a resize is actually necessary.
		if ($width == $this->width && $height == $this->height) {
			return $this;
		}


		$newGD = imagecreatetruecolor($width, $height);

		// Preserves transparency between images
		imagealphablending($newGD, false);
		imagesavealpha($newGD, true);

		imagecopyresampled($newGD, $this->gd, 0,0, 0, 0, $width, $height, $this->width, $this->height);

		$output = clone $this;
		$output->setImageResource($newGD);
		return $output;
	}

	/**
	 * Rotates image by given angle.
	 *
	 * @param float angle Angle in degrees
	 * @return static
	 */
	public function rotate($angle) {
		if(!$this->gd) {
			return null;
		}

		if(function_exists("imagerotate")) {
			$newGD = imagerotate($this->gd, $angle, 0);
		} else {
			//imagerotate is not included in PHP included in Ubuntu
			$newGD = $this->rotatePixelByPixel($angle);
		}
		$output = clone $this;
		$output->setImageResource($newGD);
		return $output;
	}

	/**
	 * Rotates image by given angle. It's slow because makes it pixel by pixel rather than
	 * using built-in function. Used when imagerotate function is not available(i.e. Ubuntu)
	 *
	 * @param float $angle Angle in degrees
	 * @return static
	 */
	public function rotatePixelByPixel($angle) {
		if(!$this->gd) {
			return null;
		}
		$sourceWidth = imagesx($this->gd);
		$sourceHeight = imagesy($this->gd);
		if ($angle == 180) {
			$destWidth = $sourceWidth;
			$destHeight = $sourceHeight;
		} else {
			$destWidth = $sourceHeight;
			$destHeight = $sourceWidth;
		}
		$rotate=imagecreatetruecolor($destWidth,$destHeight);
		imagealphablending($rotate, false);
		for ($x = 0; $x < ($sourceWidth); $x++) {
			for ($y = 0; $y < ($sourceHeight); $y++) {
				$color = imagecolorat($this->gd, $x, $y);
				switch ($angle) {
					case 90:
						imagesetpixel($rotate, $y, $destHeight - $x - 1, $color);
					break;
					case 180:
						imagesetpixel($rotate, $destWidth - $x - 1, $destHeight - $y - 1, $color);
					break;
					case 270:
						imagesetpixel($rotate, $destWidth - $y - 1, $x, $color);
					break;
					default: $rotate = $this->gd;
				};
			}
		}
		return $rotate;
	}


	/**
	 * Crop's part of image.
	 *
	 * @param int $top y position of left upper corner of crop rectangle
	 * @param int $left x position of left upper corner of crop rectangle
	 * @param int $width rectangle width
	 * @param int $height rectangle height
	 * @return static
	 */
	public function crop($top, $left, $width, $height) {
		if(!$this->gd) {
			return null;
		}

		$newGD = imagecreatetruecolor($width, $height);

		// Preserve alpha channel between images
		imagealphablending($newGD, false);
		imagesavealpha($newGD, true);

		imagecopyresampled($newGD, $this->gd, 0, 0, $left, $top, $width, $height, $width, $height);

		$output = clone $this;
		$output->setImageResource($newGD);
		return $output;
	}

	/**
	 * Width of image.
	 *
	 * @return int
	 */
	public function getWidth() {
		return $this->width;
	}

	/**
	 * Height of image.
	 *
	 * @return int
	 */
	public function getHeight() {
		return $this->height;
	}

	public function resizeByWidth($width) {
		$heightScale = $width / $this->width;
		return $this->resize($width, $heightScale * $this->height );
	}

	public function resizeByHeight($height) {
		$scale = $height / $this->height;
		return $this->resize($scale * $this->width, $height );
	}

	public function resizeRatio($maxWidth, $maxHeight, $useAsMinimum = false) {
		$widthRatio = $maxWidth / $this->width;
		$heightRatio = $maxHeight / $this->height;

		if( $widthRatio < $heightRatio ) {
			return $useAsMinimum
				? $this->resizeByHeight( $maxHeight )
				: $this->resizeByWidth( $maxWidth );
		} else {
			return $useAsMinimum
				? $this->resizeByWidth( $maxWidth )
				: $this->resizeByHeight( $maxHeight );
		}
	}

	public function paddedResize($width, $height, $backgroundColor = "FFFFFF") {
		if(!$this->gd) {
			return null;
		}
		$width = round($width);
		$height = round($height);

		// Check that a resize is actually necessary.
		if ($width == $this->width && $height == $this->height) {
			return $this;
		}

		$newGD = imagecreatetruecolor($width, $height);

		// Preserves transparency between images
		imagealphablending($newGD, false);
		imagesavealpha($newGD, true);

		$bg = $this->colourWeb2GD($newGD, $backgroundColor);
		imagefilledrectangle($newGD, 0, 0, $width, $height, $bg);

		$destAR = $width / $height;
		if ($this->width > 0 && $this->height > 0) {
			// We can't divide by zero theres something wrong.

			$srcAR = $this->width / $this->height;

			// Destination narrower than the source
			if($destAR > $srcAR) {
				$destY = 0;
				$destHeight = $height;

				$destWidth = round( $height * $srcAR );
				$destX = round( ($width - $destWidth) / 2 );

			// Destination shorter than the source
			} else {
				$destX = 0;
				$destWidth = $width;

				$destHeight = round( $width / $srcAR );
				$destY = round( ($height - $destHeight) / 2 );
			}

			imagecopyresampled($newGD, $this->gd,
				$destX, $destY, 0, 0,
				$destWidth, $destHeight, $this->width, $this->height);
		}
		$output = clone $this;
		$output->setImageResource($newGD);
		return $output;
	}

	/**
	 * Make the image greyscale
	 * $rv = red value, defaults to 38
	 * $gv = green value, defaults to 36
	 * $bv = blue value, defaults to 26
	 * Based (more or less entirely, with changes for readability) on code from
	 * http://www.teckis.com/scriptix/thumbnails/teck.html
	 */
	public function greyscale($rv=38, $gv=36, $bv=26) {
		if(!$this->gd) {
			return null;
		}

		$width = $this->width;
		$height = $this->height;
		$newGD = imagecreatetruecolor($this->width, $this->height);

		// Preserves transparency between images
		imagealphablending($newGD, false);
		imagesavealpha($newGD, true);

		$rt = $rv + $bv + $gv;
		$rr = ($rv == 0) ? 0 : 1/($rt/$rv);
		$br = ($bv == 0) ? 0 : 1/($rt/$bv);
		$gr = ($gv == 0) ? 0 : 1/($rt/$gv);
		for($dy = 0; $dy < $height; $dy++) {
			for($dx = 0; $dx < $width; $dx++) {
				$pxrgb = imagecolorat($this->gd, $dx, $dy);
				$heightgb = ImageColorsforIndex($this->gd, $pxrgb);
				$newcol = ($rr*$heightgb['red']) + ($br*$heightgb['blue']) + ($gr*$heightgb['green']);
				$setcol = ImageColorAllocateAlpha($newGD, $newcol, $newcol, $newcol, $heightgb['alpha']);
				imagesetpixel($newGD, $dx, $dy, $setcol);
			}
		}

		$output = clone $this;
		$output->setImageResource($newGD);
		return $output;
	}

	public function writeToStore(AssetStore $assetStore, $filename, $hash = null, $variant = null, $conflictResolution = null) {
		// Write to temporary file, taking care to maintain the extension
		$path = tempnam(sys_get_temp_dir(), 'gd');
		if($extension = pathinfo($filename, PATHINFO_EXTENSION)) {
			$path .= "." . $extension;
		}
		$this->writeTo($path);
		$result = $assetStore->setFromLocalFile($path, $filename, $hash, $variant, $conflictResolution);
		unlink($path);
		return $result;
	}

	public function writeTo($filename) {
		if(!$filename) {
			return;
		}

		// Get current image data
		if(file_exists($filename)) {
			list($width, $height, $type, $attr) = getimagesize($filename);
			unlink($filename);
		} else {
			Filesystem::makeFolder(dirname($filename));
		}

		// If image type isn't known, guess from extension
		$ext = strtolower(substr($filename, strrpos($filename,'.')+1));
		if(empty($type)) {
			switch($ext) {
				case "gif":
					$type = IMAGETYPE_GIF;
					break;
				case "jpeg":
				case "jpg":
				case "jpe":
					$type = IMAGETYPE_JPEG;
					break;
				default:
					$type = IMAGETYPE_PNG;
					break;
			}
		}

		// if $this->interlace != 0, the output image will be interlaced
		imageinterlace ($this->gd, $this->interlace);

		// if the extension does not exist, the file will not be created!
		switch($type) {
			case IMAGETYPE_GIF:
				imagegif($this->gd, $filename);
				break;
			case IMAGETYPE_JPEG:
				imagejpeg($this->gd, $filename, $this->quality);
				break;

			// case 3, and everything else
			default:
				// Save them as 8-bit images
				// imagetruecolortopalette($this->gd, false, 256);
				imagepng($this->gd, $filename);
				break;
		}
		if(file_exists($filename)) {
			@chmod($filename, 0664);
		}
	}

	/**
	 * Helper function to allocate a colour to an image
	 *
	 * @param resource $image
	 * @param string $webColor
	 * @return int
	 */
	protected function colourWeb2GD($image, $webColor) {
		if(substr($webColor,0,1) == "#") {
			$webColor = substr($webColor, 1);
		}
		$r = hexdec(substr($webColor,0,2));
		$g = hexdec(substr($webColor,2,2));
		$b = hexdec(substr($webColor,4,2));

		return imagecolorallocate($image, $r, $g, $b);
	}

	public static function flush() {
		// Clear factory
		$cache = SS_Cache::factory('GDBackend_Manipulations');
		$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
	}

}