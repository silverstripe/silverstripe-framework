<?php

namespace SilverStripe\Filesystem\Storage;

/**
 * Represents a container for a specific asset.
 *
 * This is used as a use-agnostic interface to a single asset backed by an AssetStore
 *
 * Note that there are no setter equivalents for each of getHash, getVariant and getFilename.
 * User code should utilise the setFrom* methods instead.
 *
 * @package framework
 * @subpackage filesystem
 */
interface AssetContainer {

	/**
	 * Assign a set of data to the backend
	 *
	 * @param string $data Raw binary/text content
	 * @param string $filename Name for the resulting file
	 * @param string $hash Hash of original file, if storing a variant.
	 * @param string $variant Name of variant, if storing a variant.
	 * @param string $conflictResolution {@see AssetStore}. Will default to one chosen by the backend
	 * @return array Tuple associative array (Filename, Hash, Variant) Unless storing a variant, the hash
	 * will be calculated from the given data.
	 */
	public function setFromString($data, $filename, $hash = null, $variant = null, $conflictResolution = null);

    /**
	 * Assign a local file to the backend.
	 *
	 * @param string $path Absolute filesystem path to file
	 * @param type $filename Optional path to ask the backend to name as.
	 * Will default to the filename of the $path, excluding directories.
	 * @param string $hash Hash of original file, if storing a variant.
	 * @param string $variant Name of variant, if storing a variant.
	 * @param string $conflictResolution {@see AssetStore}
	 * @return array Tuple associative array (Filename, Hash, Variant) Unless storing a variant, the hash
	 * will be calculated from the local file content.
	 */
    public function setFromLocalFile($path, $filename = null, $hash = null, $variant = null, $conflictResolution = null);

    /**
	 * Assign a stream to the backend
	 *
	 * @param resource $stream Streamable resource
	 * @param string $filename Name for the resulting file
	 * @param string $hash Hash of original file, if storing a variant.
	 * @param string $variant Name of variant, if storing a variant.
	 * @param string $conflictResolution {@see AssetStore}
	 * @return array Tuple associative array (Filename, Hash, Variant) Unless storing a variant, the hash
	 * will be calculated from the raw stream.
	 */
    public function setFromStream($stream, $filename, $hash = null, $variant = null, $conflictResolution = null);

    /**
     * @return string Data from the file in this container
     */
    public function getString();

    /**
	 * @return resource Data stream to the asset in this container
	 */
    public function getStream();

    /**
     * @return string public url to the asset in this container
     */
    public function getURL();

	/**
	 * @return string The absolute URL to the asset in this container
	 */
	public function getAbsoluteURL();

	/**
	 * Get metadata for this file
	 *
	 * @return array|null File information
	 */
	public function getMetaData();

	/**
	 * Get mime type
	 *
	 * @return string Mime type for this file
	 */
	public function getMimeType();

	/**
	 * Return file size in bytes.
	 *
	 * @return int
	 */
	public function getAbsoluteSize();

	/**
	 * Determine if a valid non-empty image exists behind this asset
	 *
	 * @return bool
	 */
	public function getIsImage();

	/**
	 * Determine if this container has a valid value
	 *
	 * @return bool Flag as to whether the file exists
	 */
	public function exists();

	/**
	 * Get value of filename
	 *
	 * @return string
	 */
	public function getFilename();

	/**
	 * Get value of hash
	 *
	 * @return string
	 */
	public function getHash();

	/**
	 * Get value of variant
	 *
	 * @return string
	 */
	public function getVariant();
}
