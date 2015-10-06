<?php

/**
 * @package framework
 * @subpackage filesystem
 */

if(class_exists('Imagick')) {
class ImagickBackend extends Imagick implements Image_Backend {

	/**
	 * @config
	 * @var int
	 */
	private static $default_quality = 75;

	/**
	 * __construct
	 *
	 * @param string $filename = null
	 * @return void
	 */
	public function __construct($filename = null) {
		if(is_string($filename)) {
			parent::__construct($filename);
		}
		$this->setQuality(Config::inst()->get('ImagickBackend','default_quality'));
	}

	/**
	 * writeTo
	 *
	 * @param string $path
	 * @return void
	 */
	public function writeTo($path) {
		Filesystem::makeFolder(dirname($path));
		if(is_dir(dirname($path)))
			self::writeImage($path);
	}

	/**
	 * set_default_quality
	 *
	 * @deprecated 4.0 Use the "ImagickBackend.default_quality" config setting instead
	 * @param int $quality
	 * @return void
	 */
	public static function set_default_quality($quality) {
		Deprecation::notice('4.0', 'Use the "ImagickBackend.default_quality" config setting instead');
		if(is_numeric($quality) && (int) $quality >= 0 && (int) $quality <= 100) {
			Config::inst()->update('ImagickBackend', 'default_quality', (int) $quality);
		}
	}

	/**
	 * setQuality
	 *
	 * @param int $quality
	 * @return void
	 */
	public function setQuality($quality) {
		self::setImageCompressionQuality($quality);
	}

	/**
	 * setImageResource
	 *
	 * Set the backend-specific resource handling the manipulations. Replaces Image::setGD()
	 *
	 * @param mixed $resource
	 * @return void
	 */
	public function setImageResource($resource) {
		trigger_error("Imagick::setImageResource is not supported", E_USER_ERROR);
	}

	/**
	 * getImageResource
	 *
	 * Get the backend-specific resource handling the manipulations. Replaces Image::getGD()
	 *
	 * @return mixed
	 */
	public function getImageResource() {
		return $this;
	}

	/**
	 * hasImageResource
	 *
	 * @return boolean
	 */
	public function hasImageResource() {
		return true; // $this is the resource, necessarily
	}

	/**
	 * @todo Implement memory checking for Imagick? See {@link GD}
	 *
	 * @param string $filename
	 * @param string $manipulation
	 * @return boolean
	 */
	public function imageAvailable($filename, $manipulation) {
		return true;
	}

	/**
	 * resize
	 *
	 * @param int $width
	 * @param int $height
	 * @return Image_Backend
	 */
	public function resize($width, $height) {
		if(!$this->valid()) return;
		
		if($width < 0 || $height < 0) throw new InvalidArgumentException("Image resizing dimensions cannot be negative");
		if(!$width && !$height) throw new InvalidArgumentException("No dimensions given when resizing image");
		if(!$width) throw new InvalidArgumentException("Width not given when resizing image");
		if(!$height) throw new InvalidArgumentException("Height not given when resizing image");
		
		//use whole numbers, ensuring that size is at least 1x1
		$width = max(1, round($width));
		$height = max(1, round($height));

		$geometry = $this->getImageGeometry();

		// Check that a resize is actually necessary.
		if ($width == $geometry["width"] && $height == $geometry["height"]) {
			return $this;
		}
		
		$new = clone $this;
		$new->resizeImage($width, $height, self::FILTER_LANCZOS, 1);

		return $new;
	}

	/**
	 * resizeRatio
	 *
	 * @param int $width
	 * @param int $height
	 * @return Image_Backend
	 */
	public function resizeRatio($maxWidth, $maxHeight, $useAsMinimum = false) {
		if(!$this->valid()) return;

		$geometry = $this->getImageGeometry();

		$widthRatio = $maxWidth / $geometry["width"];
		$heightRatio = $maxHeight / $geometry["height"];

		if( $widthRatio < $heightRatio )
			return $useAsMinimum ? $this->resizeByHeight( $maxHeight ) : $this->resizeByWidth( $maxWidth );
		else
			return $useAsMinimum ? $this->resizeByWidth( $maxWidth ) : $this->resizeByHeight( $maxHeight );
	}

	/**
	 * resizeByWidth
	 *
	 * @param int $width
	 * @return Image_Backend
	 */
	public function resizeByWidth($width) {
		if(!$this->valid()) return;

		$geometry = $this->getImageGeometry();

		$heightScale = $width / $geometry["width"];
		return $this->resize( $width, $heightScale * $geometry["height"] );
	}

	/**
	 * resizeByHeight
	 *
	 * @param int $height
	 * @return Image_Backend
	 */
	public function resizeByHeight($height) {
		if(!$this->valid()) return;

		$geometry = $this->getImageGeometry();

		$scale = $height / $geometry["height"];
		return $this->resize( $scale * $geometry["width"], $height );
	}

	/**
	 * paddedResize
	 *
	 * @param int $width
	 * @param int $height
	 * @return Image_Backend
	 */
	public function paddedResize($width, $height, $backgroundColor = "FFFFFF") {
		$new = $this->resizeRatio($width, $height);
		$new->setImageBackgroundColor("#".$backgroundColor);
		$w = $new->getImageWidth();
		$h = $new->getImageHeight();
		$new->extentImage($width,$height,($w-$width)/2,($h-$height)/2);
		
		return $new;
	}

	/**
	 * croppedResize
	 *
	 * @param int $width
	 * @param int $height
	 * @return Image_Backend
	 */
	public function croppedResize($width, $height) {
		if(!$this->valid()) return;

		$width = round($width);
		$height = round($height);
		$geo = $this->getImageGeometry();

		// Check that a resize is actually necessary.
		if ($width == $geo["width"] && $height == $geo["height"]) {
			return $this;
		}
		
		$new = clone $this;
		$new->setBackgroundColor(new ImagickPixel('transparent'));

		if(($geo['width']/$width) < ($geo['height']/$height)){
			$new->cropImage($geo['width'], floor($height*$geo['width']/$width),
				0, (($geo['height']-($height*$geo['width']/$width))/2));
		}else{
			$new->cropImage(ceil($width*$geo['height']/$height), $geo['height'],
				(($geo['width']-($width*$geo['height']/$height))/2), 0);
		}
		$new->ThumbnailImage($width,$height,true);
		return $new;
	}

	/**
	 * @param Image $frontend
	 * @return void
	 */
	public function onBeforeDelete($frontend) {
		// Not in use
	}
}
}
