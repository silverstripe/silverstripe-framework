<?php

namespace SilverStripe\Filesystem\Storage;

/**
 * Represents an abstract asset persistence layer. Acts as a backend to files
 *
 * @package framework
 * @subpackage filesystem
 */
interface AssetStore {

	/**
	 * Exception on file conflict
	 */
	const CONFLICT_EXCEPTION = 'exception';

	/**
	 * Overwrite on file conflict
	 */
	const CONFLICT_OVERWRITE = 'overwrite';

	/**
	 * Rename on file conflict. Rename rules will be
	 * determined by the backend
	 */
	const CONFLICT_RENAME = 'rename';

	/**
	 * On conflict, use existing file
	 */
	const CONFLICT_USE_EXISTING = 'existing';

	/**
	 * Assign a set of data to the backend
	 *
	 * @param string $data Raw binary/text content
	 * @param string $filename Name for the resulting file
	 * @param string $conflictResolution {@see AssetStore}. Will default to one chosen by the backend
	 * @return array Tuple associative array (Filename, Hash, Variant)
	 */
	public function setFromString($data, $filename, $conflictResolution = null);

    /**
	 * Assign a local file to the backend.
	 *
	 * @param string $path Absolute filesystem path to file
	 * @param type $filename Optional path to ask the backend to name as.
	 * Will default to the filename of the $path, excluding directories.
	 * @param string $conflictResolution {@see AssetStore}
	 * @return array Tuple associative array (Filename, Hash, Variant)
	 */
    public function setFromLocalFile($path, $filename = null, $conflictResolution = null);

    /**
	 * Assign a stream to the backend
	 *
	 * @param resource $stream Streamable resource
	 * @param string $filename Name for the resulting file
	 * @param string $conflictResolution {@see AssetStore}
	 * @return array Tuple associative array (Filename, Hash, Variant)
	 */
    public function setFromStream($stream, $filename, $conflictResolution = null);

    /**
	 * Get contents of a given file
	 *
     * @param string $hash sha1 hash of the file content
     * @param string $filename Filename (not including assets)
     * @param string|null $variant Optional variant string for this file
     * @return string Data from the file.
     */
    public function getAsString($hash, $filename, $variant = null);

    /**
	 * Get a stream for this file
	 *
	 * @param string $hash sha1 hash of the file content
     * @param string $filename Filename (not including assets)
     * @param string|null $variant Optional variant string for this file
	 * @return resource Data stream
	 */
    public function getAsStream($hash, $filename, $variant = null);

    /**
	 * Get the url for the file
	 *
	 * @param string $hash sha1 hash of the file content
     * @param string $filename Filename (not including assets)
     * @param string|null $variant Optional variant string for this file
     * @return string public url to this resource
     */
    public function getAsURL($hash, $filename, $variant = null);

	/**
	 * Get metadata for this file, if available
	 *
	 * @param string $hash sha1 hash of the file content
     * @param string $filename Filename (not including assets)
     * @param string|null $variant Optional variant string for this file
	 * @return array|null File information, or null if no metadata available
	 */
	public function getMetadata($hash, $filename, $variant = null);

	/**
	 * Get mime type of this file
	 *
	 * @param string $hash sha1 hash of the file content
     * @param string $filename Filename (not including assets)
     * @param string|null $variant Optional variant string for this file
	 * @return string Mime type for this file
	 */
	public function getMimeType($hash, $filename, $variant = null);
}
