<?php
/**
 * Image_Backend
 * 
 * A backend for manipulation of images via the Image class
 *
 * @package framework
 * @subpackage filesystem
 */
interface Image_Backend {

	/**
	 * __construct
	 *
	 * @param string $filename = null
	 * @return void
	 */
	public function __construct($filename = null);

	/**
	 * writeTo
	 *
	 * @param string $path
	 * @return void
	 */
	public function writeTo($path);
		
	/**
	 * setQuality
	 *
	 * @param int $quality
	 * @return void
	 */
	public function setQuality($quality);
	
	/**
	 * setImageResource
	 * 
	 * Set the backend-specific resource handling the manipulations. Replaces Image::setGD()
	 *
	 * @param mixed $resource
	 * @return void
	 */
	public function setImageResource($resource);
	
	/**
	 * getImageResource
	 * 
	 * Get the backend-specific resource handling the manipulations. Replaces Image::getGD()
	 *
	 * @return mixed
	 */
	public function getImageResource();
	
	/**
	 * hasImageResource
	 *
	 * @return boolean
	 */
	public function hasImageResource();

	/**
	 * resize
	 *
	 * @param int $width
	 * @param int $height
	 * @return Image_Backend
	 */
	public function resize($width, $height);
	
	/**
	 * resizeRatio
	 *
	 * @param int $width
	 * @param int $height
	 * @return Image_Backend
	 */
	public function resizeRatio($maxWidth, $maxHeight, $useAsMinimum = false);
	
	/**
	 * resizeByWidth
	 *
	 * @param int $width
	 * @return Image_Backend
	 */
	public function resizeByWidth($width);
	
	/**
	 * resizeByHeight
	 *
	 * @param int $height
	 * @return Image_Backend
	 */
	public function resizeByHeight($height);
	
	/**
	 * paddedResize
	 *
	 * @param int $width
	 * @param int $height
	 * @return Image_Backend
	 */
	public function paddedResize($width, $height, $backgroundColor = "FFFFFF");
	
	/**
	 * croppedResize
	 *
	 * @param int $width
	 * @param int $height
	 * @return Image_Backend
	 */
	public function croppedResize($width, $height);
}
