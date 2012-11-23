<?php

/**
 * @package framework
 * @subpackage filesystem
 */

if(class_exists('Imagick')) {
class ImagickBackend extends Imagick implements Image_Backend {
	
	/**
	 * @var int
	 */
	protected static $default_quality = 75;
	
	/**
	 * __construct
	 *
	 * @param string $filename = null
	 * @return void
	 */
	public function __construct($filename = null) {
		if(is_string($filename)) {
			parent::__construct($filename);
		} else {
			self::setImageCompressionQuality(self::$default_quality);
		}
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
	 * @static
	 * @param int $quality
	 * @return void
	 */
	public static function set_default_quality($quality) {
		self::$default_quality = $quality;
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
	 * resize
	 *
	 * @param int $width
	 * @param int $height
	 * @return Image_Backend
	 */
	public function resize($width, $height) {
		if(!$this->valid()) return;
	
		$width = round($width);
		$height = round($height);
		
		$geometry = $this->getImageGeometry();
		
		// Check that a resize is actually necessary.
		if ($width == $geometry["width"] && $height == $geometry["height"]) {
			return $this;
		}
		
		if(!$width && !$height) user_error("No dimensions given", E_USER_ERROR);
		if(!$width) user_error("Width not given", E_USER_ERROR);
		if(!$height) user_error("Height not given", E_USER_ERROR);
		
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
		if(!$this->valid()) return;
		
		$width = round($width);
		$height = round($height);
		$geometry = $this->getImageGeometry();
		
		// Check that a resize is actually necessary.
		if ($width == $geometry["width"] && $height == $geometry["height"]) {
			return $this;
		}
		
		$new = clone $this;
		$new->setBackgroundColor($backgroundColor);
		
		$destAR = $width / $height;
		if ($geometry["width"] > 0 && $geometry["height"] > 0) {
			// We can't divide by zero theres something wrong.
			
			$srcAR = $geometry["width"] / $geometry["height"];
		
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
		
			$new->extentImage($width, $height, $destX, $destY);
		}
		
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
		$geometry = $this->getImageGeometry();
		
		// Check that a resize is actually necessary.
		if ($width == $geometry["width"] && $height == $geometry["height"]) {
			return $this;
		}
		
		$new = clone $this;
		$new->setBackgroundColor($backgroundColor);
		
		$destAR = $width / $height;
		if ($geometry["width"] > 0 && $geometry["height"] > 0) {
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
		
			$new->extentImage($width, $height, $destX, $destY);
		}
		
		return $new;
	}
}
}