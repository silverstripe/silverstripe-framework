<?php

/**
 * @package framework
 * @subpackage filesystem
 */

if(class_exists('Imagick')) {
class ImagickBackend implements Image_Backend {
	
	protected $im;

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
		try{
			$this->im = new Imagick($filename);
			$this->setQuality(Config::inst()->get('ImagickBackend','default_quality'));
		}catch(ImagickException $e){
			//fail gracefully
			$this->im = null;
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
		if($this->im && is_dir(dirname($path)))
			$this->im->writeImage($path);
	}
	
	/**
	 * set_default_quality
	 *
	 * @deprecated 3.2 Use the "IMagickBackend.default_quality" config setting instead
	 * @param int $quality
	 * @return void
	 */
	public static function set_default_quality($quality) {
		Deprecation::notice('3.2', 'Use the "IMagickBackend.default_quality" config setting instead');
		if(is_numeric($quality) && (int) $quality >= 0 && (int) $quality <= 100) {
			config::inst()->update('IMagickBackend', 'default_quality', (int) $quality);
		}
	}
	
	/**
	 * setQuality
	 *
	 * @param int $quality
	 * @return void
	 */
	public function setQuality($quality) {
		if(!$this->im) return;
		$this->im->setImageCompressionQuality($quality);
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
		$this->im = $resource;
	}
	
	/**
	 * getImageResource
	 * 
	 * Get the backend-specific resource handling the manipulations. Replaces Image::getGD()
	 *
	 * @return mixed
	 */
	public function getImageResource() {
		return $this->im;
	}
	
	/**
	 * hasImageResource
	 *
	 * @return boolean
	 */
	public function hasImageResource() {
		return $this->im ? true : false;
	}

	/**
	 * resize
	 *
	 * @param int $width
	 * @param int $height
	 * @return Image_Backend
	 */
	public function resize($width, $height) {
		if(!$this->im) return;
	
		$width = round($width);
		$height = round($height);
		
		$geometry = $this->im->getImageGeometry();
		
		// Check that a resize is actually necessary.
		if ($width == $geometry["width"] && $height == $geometry["height"]) {
			$new = $this;
		}
		if(!$width && !$height) user_error("No dimensions given", E_USER_ERROR);
		if(!$width) user_error("Width not given", E_USER_ERROR);
		if(!$height) user_error("Height not given", E_USER_ERROR);
		
		$new = clone $this->im;
		$new->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1);

		$output = clone $this;
		$output->setImageResource($new);
		
		return $output;
	}
	
	/**
	 * resizeRatio
	 *
	 * @param int $width
	 * @param int $height
	 * @return Image_Backend
	 */
	public function resizeRatio($maxWidth, $maxHeight, $useAsMinimum = false) {
		if(!$this->im) return;
		
		$geometry = $this->im->getImageGeometry();
	
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
		if(!$this->im) return;
		
		$geometry = $this->im->getImageGeometry();
		
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
		if(!$this->im) return;
		
		$geometry = $this->im->getImageGeometry();
		
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
		if(!$this->im) return;
		$new = $this->resizeRatio($width, $height)->getImageResource();
		$new->setImageBackgroundColor("#".$backgroundColor);
		$w = $new->getImageWidth();
		$h = $new->getImageHeight();
		$new->extentImage($width,$height,($w-$width)/2,($h-$height)/2);
		
		$output = clone $this;
		$output->setImageResource($new);
		
		return $output;
	}
	
	/**
	 * croppedResize
	 *
	 * @param int $width
	 * @param int $height
	 * @return Image_Backend
	 */
	public function croppedResize($width, $height) {
		if(!$this->im) return;
		
		$width = round($width);
		$height = round($height);
		$geo = $this->im->getImageGeometry();
		
		// Check that a resize is actually necessary.
		if ($width == $geo["width"] && $height == $geo["height"]) {
			return $this;
		}
		
		$new = clone $this->im;
		$new->setBackgroundColor(new ImagickPixel('transparent'));
		
		if(($geo['width']/$width) < ($geo['height']/$height)){
			$new->cropImage($geo['width'], floor($height*$geo['width']/$width),
				0, (($geo['height']-($height*$geo['width']/$width))/2));
		}else{
			$new->cropImage(ceil($width*$geo['height']/$height), $geo['height'],
				(($geo['width']-($width*$geo['height']/$height))/2), 0);
		}
		$new->ThumbnailImage($width,$height,true);

		$output = clone $this;
		$output->setImageResource($new);
		
		return $output;
	}
}
}
