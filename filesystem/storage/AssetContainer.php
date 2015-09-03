<?php

namespace SilverStripe\Filesystem\Storage;

/**
 * Represents a container for a specific asset.
 *
 * This is used as a use-agnostic interface to a single asset backed by an AssetStore
 *
 * @package framework
 * @subpackage filesystem
 */
interface AssetContainer {

	/**
	 * Assign a set of data to this container
	 *
	 * @param string $data Raw binary/text content
	 * @param string $filename Name for the resulting file
	 * @param string $conflictResolution {@see AssetStore}. Will default to one chosen by the backend
	 * @return array Tuple associative array (Filename, Hash, Variant)
	 */
	public function setFromString($data, $filename, $conflictResolution = null);

    /**
	 * Assign a local file to this container
	 *
	 * @param string $path Absolute filesystem path to file
	 * @param type $filename Optional path to ask the backend to name as.
	 * Will default to the filename of the $path, excluding directories.
	 * @param string $conflictResolution {@see AssetStore}
	 * @return array Tuple associative array (Filename, Hash, Variant)
	 */
    public function setFromLocalFile($path, $filename = null, $conflictResolution = null);

    /**
	 * Assign a stream to this container
	 *
	 * @param resource $stream Streamable resource
	 * @param string $filename Name for the resulting file
	 * @param string $conflictResolution {@see AssetStore}
	 * @return array Tuple associative array (Filename, Hash, Variant)
	 */
    public function setFromStream($stream, $filename, $conflictResolution = null);

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
}
