<?php

use SilverStripe\Filesystem\Storage\AssetContainer;
use SilverStripe\Filesystem\Storage\AssetStore;

/**
 * @package framework
 * @subpackage filesystem
 */

if(!class_exists('Imagick')) {
	return;
}

class ImagickBackend extends Imagick implements Image_Backend {

	/**
	 * @config
	 * @var int
	 */
	private static $default_quality = 75;

	/**
	 * Create a new backend with the given object
	 *
	 * @param AssetContainer $assetContainer Object to load from
	 */
	public function __construct(AssetContainer $assetContainer = null) {
		parent::__construct();
		
		if($assetContainer) {
			$this->loadFromContainer($assetContainer);
		}
	}

	public function loadFromContainer(AssetContainer $assetContainer) {
		$stream = $assetContainer->getStream();
		$this->readimagefile($stream);
		fclose($stream);
		$this->setDefaultQuality();
	}

	public function loadFrom($path) {
		$this->readimage($path);
		$this->setDefaultQuality();
	}

	protected function setDefaultQuality() {
		$this->setQuality(Config::inst()->get('ImagickBackend', 'default_quality'));
	}

	public function writeToStore(AssetStore $assetStore, $filename, $hash = null, $variant = null, $conflictResolution = null) {
		// Write to temporary file, taking care to maintain the extension
		$path = tempnam(sys_get_temp_dir(), 'imagemagick');
		if($extension = pathinfo($filename, PATHINFO_EXTENSION)) {
			$path .= "." . $extension;
		}
		$this->writeimage($path);
		$result = $assetStore->setFromLocalFile($path, $filename, $hash, $variant, $conflictResolution);
		unlink($path);
		return $result;
	}

	public function writeTo($path) {
		Filesystem::makeFolder(dirname($path));
		if(is_dir(dirname($path))) {
			$this->writeImage($path);
		}
	}

	public function setQuality($quality) {
		$this->setImageCompressionQuality($quality);
	}

	public function resize($width, $height) {
		if(!$this->valid()) {
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

		$geometry = $this->getImageGeometry();

		// Check that a resize is actually necessary.
		if ($width === $geometry["width"] && $height === $geometry["height"]) {
			return $this;
		}
		
		$new = clone $this;
		$new->resizeImage($width, $height, self::FILTER_LANCZOS, 1);

		return $new;
	}

	public function resizeRatio($maxWidth, $maxHeight, $useAsMinimum = false) {
		if(!$this->valid()) {
			return null;
		}

		$geometry = $this->getImageGeometry();

		$widthRatio = $maxWidth / $geometry["width"];
		$heightRatio = $maxHeight / $geometry["height"];

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

	public function resizeByWidth($width) {
		if(!$this->valid()) {
			return null;
		}

		$geometry = $this->getImageGeometry();

		$heightScale = $width / $geometry["width"];
		return $this->resize( $width, $heightScale * $geometry["height"] );
	}

	public function resizeByHeight($height) {
		if(!$this->valid()) {
			return null;
		}

		$geometry = $this->getImageGeometry();

		$scale = $height / $geometry["height"];
		return $this->resize( $scale * $geometry["width"], $height );
	}

	public function paddedResize($width, $height, $backgroundColor = "FFFFFF") {
		if(!$this->valid()) {
			return null;
		}
		
		$new = $this->resizeRatio($width, $height);
		$new->setImageBackgroundColor("#".$backgroundColor);
		$w = $new->getImageWidth();
		$h = $new->getImageHeight();
		$new->extentImage($width,$height,($w-$width)/2,($h-$height)/2);
		return $new;
	}

	public function croppedResize($width, $height) {
		if(!$this->valid()) {
			return null;
		}

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
			$new->cropImage(
				$geo['width'],
				floor($height*$geo['width']/$width),
				0,
				($geo['height'] - ($height*$geo['width']/$width))/2
			);
		}else{
			$new->cropImage(
				ceil($width*$geo['height']/$height),
				$geo['height'],
				($geo['width'] - ($width*$geo['height']/$height))/2,
				0
			);
		}
		$new->ThumbnailImage($width,$height,true);
		return $new;
	}
}
