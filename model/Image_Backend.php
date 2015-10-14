<?php

use SilverStripe\Filesystem\Storage\AssetContainer;
use SilverStripe\Filesystem\Storage\AssetStore;

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
	 * Represents a square orientation
	 */
	const ORIENTATION_SQUARE = 0;

	/**
	 * Represents a portrait orientation
	 */
	const ORIENTATION_PORTRAIT = 1;

	/**
	 * Represents a landscape orientation
	 */
	const ORIENTATION_LANDSCAPE = 2;

	/**
	 * Create a new backend with the given object
	 *
	 * @param AssetContainer $assetContainer Object to load from
	 */
	public function __construct(AssetContainer $assetContainer = null);

	/**
	 * Populate the backend with a given object
	 *
	 * @param AssetContainer $assetContainer Object to load from
	 */
	public function loadFromContainer(AssetContainer $assetContainer);

	/**
	 * Populate the backend from a local path
	 *
	 * @param string $path
	 */
	public function loadFrom($path);

	/**
	 * Write to the given asset store
	 *
	 * @param AssetStore $assetStore
	 * @param string $filename Name for the resulting file
	 * @param string $hash Hash of original file, if storing a variant.
	 * @param string $variant Name of variant, if storing a variant.
	 * @param string $conflictResolution {@see AssetStore}. Will default to one chosen by the backend
	 * @return array Tuple associative array (Filename, Hash, Variant) Unless storing a variant, the hash
	 * will be calculated from the given data.
	 */
	public function writeToStore(AssetStore $assetStore, $filename, $hash = null, $variant = null, $conflictResolution = null);

	/**
	 * Write the backend to a local path
	 * 
	 * @param string $path
	 */
	public function writeTo($path);

	/**
	 * Set the quality to a value between 0 and 100
	 *
	 * @param int $quality
	 */
	public function setQuality($quality);

	/**
	 * Resize an image, skewing it as necessary.
	 *
	 * @param int $width
	 * @param int $height
	 * @return static
	 */
	public function resize($width, $height);

	/**
	 * Resize the image by preserving aspect ratio. By default, it will keep the image inside the maxWidth
	 * and maxHeight. Passing useAsMinimum will make the smaller dimension equal to the maximum corresponding dimension
	 *
	 * @param int $width
	 * @param int $height
	 * @param bool $useAsMinimum If true, image will be sized outside of these dimensions.
	 * If false (default) image will be sized inside these dimensions.
	 * @return static
	 */
	public function resizeRatio($width, $height, $useAsMinimum = false);

	/**
	 * Resize an image by width. Preserves aspect ratio.
	 *
	 * @param int $width
	 * @return static
	 */
	public function resizeByWidth($width);

	/**
	 * Resize an image by height. Preserves aspect ratio.
	 *
	 * @param int $height
	 * @return static
	 */
	public function resizeByHeight($height);

	/**
	 * Return a clone of this image resized, with space filled in with the given colour
	 *
	 * @param int $width
	 * @param int $height
	 * @return static
	 */
	public function paddedResize($width, $height, $backgroundColor = "FFFFFF");

	/**
	 * Resize an image to cover the given width/height completely, and crop off any overhanging edges.
	 *
	 * @param int $width
	 * @param int $height
	 * @return static
	 */
	public function croppedResize($width, $height);
}
