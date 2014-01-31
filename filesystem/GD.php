<?php
/**
 * A wrapper class for GD-based images, with lots of manipulation functions.
 * @package framework
 * @subpackage filesystem
 */
class GDBackend extends Object implements Image_Backend {
	protected $gd, $width, $height;
	protected $quality;
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

	/**
	 * Set the default image quality.
	 *
	 * @deprecated 3.2 Use the "GDBackend.default_quality" config setting instead
	 * @param quality int A number from 0 to 100, 100 being the best quality.
	 */
	public static function set_default_quality($quality) {
		Deprecation::notice('3.2', 'Use the "GDBackend.default_quality" config setting instead');
		if(is_numeric($quality) && (int) $quality >= 0 && (int) $quality <= 100) {
			config::inst()->update('GDBackend', 'default_quality', (int) $quality);
		}
	}

	public function __construct($filename = null) {
		// If we're working with image resampling, things could take a while.  Bump up the time-limit
		increase_time_limit_to(300);

		if($filename) {
			// We use getimagesize instead of extension checking, because sometimes extensions are wrong.
			list($width, $height, $type, $attr) = getimagesize($filename);
			switch($type) {
				case 1:
					if(function_exists('imagecreatefromgif'))
						$this->setImageResource(imagecreatefromgif($filename));
					break;
				case 2:
					if(function_exists('imagecreatefromjpeg'))
						$this->setImageResource(imagecreatefromjpeg($filename));
					break;
				case 3:
					if(function_exists('imagecreatefrompng')) {
						$img = imagecreatefrompng($filename);
						imagesavealpha($img, true); // save alphablending setting (important)
						$this->setImageResource($img);
					}
					break;
			}
		}
		
		parent::__construct();

		$this->quality = $this->config()->default_quality;
		$this->interlace = $this->config()->image_interlace;
	}
	
	public function setImageResource($resource) {
		$this->gd = $resource;
		$this->width = imagesx($resource);
		$this->height = imagesy($resource);
	}

	public function setGD($gd) {
		Deprecation::notice('3.1', 'Use GD::setImageResource instead');
		return $this->setImageResource($gd);
	}
	
	public function getImageResource() {
		return $this->gd;
	}

	public function getGD() {
		Deprecation::notice('3.1', 'GD::getImageResource instead');
		return $this->getImageResource();
	}

	/**
	 * Set the image quality, used when saving JPEGs.
	 */
	public function setQuality($quality) {
		$this->quality = $quality;
	}
	
	/**
	 * Resize an image to cover the given width/height completely, and crop off any overhanging edges.
	 */
	public function croppedResize($width, $height) {
		if(!$this->gd) return;
		
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
		if($gd->width > $width) $gd = $gd->resizeByWidth($width);
		return $gd;
	}
	
	/**
	 * hasImageResource
	 *
	 * @return boolean
	 */
	public function hasImageResource() {
		return $this->gd ? true : false;
	}
	
	public function hasGD() {
		Deprecation::notice('3.1', 'GD::hasImageResource instead',
			Deprecation::SCOPE_CLASS);
		return $this->hasImageResource();
	}
	
	
	/**
	 * Resize an image, skewing it as necessary.
	 */
	public function resize($width, $height) {
		if(!$this->gd) return;

		$width = round($width);
		$height = round($height);
		
		// Check that a resize is actually necessary.
		if ($width == $this->width && $height == $this->height) {
			return $this;
		}
		
		if(!$width && !$height) user_error("No dimensions given", E_USER_ERROR);
		if(!$width) user_error("Width not given", E_USER_ERROR);
		if(!$height) user_error("Height not given", E_USER_ERROR);

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
	 * @param angle 
	 *
	 * @return GD 
	*/ 
	
	public function rotate($angle) {
		if(!$this->gd) return;
		
		if(function_exists("imagerotate")) {
			$newGD = imagerotate($this->gd, $angle,0);
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
	 * @param angle 
	 *
	 * @return GD 
	*/ 
	
	public function rotatePixelByPixel($angle) {
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
	 * @param top y position of left upper corner of crop rectangle 
	 * @param left x position of left upper corner of crop rectangle
	 * @param width rectangle width
	 * @param height rectangle height
	 *
	 * @return GD  
	*/ 
	
	public function crop($top, $left, $width, $height) {
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
	 * Method return width of image.
	 *
	 * @return integer width.
	*/ 
	public function getWidth() {
		return $this->width;
	}
	
	/**
	 * Method return height of image.
	 *
	 * @return integer height 
	*/ 
	
	public function getHeight() {
		return $this->height;
	}
	
	/**
	 * Resize an image by width. Preserves aspect ratio.
	 */
	public function resizeByWidth( $width ) {
		$heightScale = $width / $this->width;
		return $this->resize( $width, $heightScale * $this->height );
	}
	
	/**
	 * Resize an image by height. Preserves aspect ratio
	 */
	public function resizeByHeight( $height ) {
		$scale = $height / $this->height;
		return $this->resize( $scale * $this->width, $height );
	}
	
	/**
	 * Resize the image by preserving aspect ratio. By default, it will keep the image inside the maxWidth
	 * and maxHeight. Passing useAsMinimum will make the smaller dimension equal to the maximum corresponding dimension
	 */
	public function resizeRatio( $maxWidth, $maxHeight, $useAsMinimum = false ) {
		
		$widthRatio = $maxWidth / $this->width;
		$heightRatio = $maxHeight / $this->height;
		
		if( $widthRatio < $heightRatio )
			return $useAsMinimum ? $this->resizeByHeight( $maxHeight ) : $this->resizeByWidth( $maxWidth );
		else
			return $useAsMinimum ? $this->resizeByWidth( $maxWidth ) : $this->resizeByHeight( $maxHeight );
	}
	
	public static function color_web2gd($image, $webColor) {
		if(substr($webColor,0,1) == "#") $webColor = substr($webColor,1);
		$r = hexdec(substr($webColor,0,2));
		$g = hexdec(substr($webColor,2,2));
		$b = hexdec(substr($webColor,4,2));
		
		return imagecolorallocate($image, $r, $g, $b);
		
	}

	/**
	 * Resize to fit fully within the given box, without resizing.  Extra space left around
	 * the image will be padded with the background color.
	 * @param width
	 * @param height
	 * @param backgroundColour
	 */
	public function paddedResize($width, $height, $backgroundColor = "FFFFFF") {
		if(!$this->gd) return;
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
		
		$bg = GD::color_web2gd($newGD, $backgroundColor);
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
	
	public function makeDir($dirname) {
		if(!file_exists(dirname($dirname))) $this->makeDir(dirname($dirname));
		if(!file_exists($dirname)) mkdir($dirname, Config::inst()->get('Filesystem', 'folder_create_mask'));
	}
	
	public function writeTo($filename) {
		$this->makeDir(dirname($filename));
		
		if($filename) {
			if(file_exists($filename)) list($width, $height, $type, $attr) = getimagesize($filename);
			
			if(file_exists($filename)) unlink($filename);

			$ext = strtolower(substr($filename, strrpos($filename,'.')+1));
			if(!isset($type)) switch($ext) {
				case "gif": $type = IMAGETYPE_GIF; break;
				case "jpeg": case "jpg": case "jpe": $type = IMAGETYPE_JPEG; break;
				default: $type = IMAGETYPE_PNG; break;
			}

			// if $this->interlace != 0, the output image will be interlaced
			imageinterlace ($this->gd, $this->interlace);
			
			// if the extension does not exist, the file will not be created!
			
			switch($type) {
				case IMAGETYPE_GIF: imagegif($this->gd, $filename); break;
				case IMAGETYPE_JPEG: imagejpeg($this->gd, $filename, $this->quality); break;
				
				// case 3, and everything else
				default: 
					// Save them as 8-bit images
					// imagetruecolortopalette($this->gd, false, 256);
					imagepng($this->gd, $filename); break;
			}
			if(file_exists($filename)) @chmod($filename,0664);
		}
	}
	
}

/**
 * This class is maintained for backwards-compatibility only. Please use the {@link GDBackend} class instead.
 *
 * @package framework
 * @subpackage filesystem
 */
class GD extends GDBackend {

	/**
	 * @deprecated 3.2 Use the "GDBackend.default_quality" config setting instead
	 */
	public static function set_default_quality($quality) {
		Deprecation::notice('3.2', 'Use the "GDBackend.default_quality" config setting instead');
		GDBackend::set_default_quality($quality);
	}	
}
