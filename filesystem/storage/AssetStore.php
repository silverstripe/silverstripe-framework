<?php

namespace SilverStripe\Filesystem\Storage;

/**
 * Represents an abstract asset persistence layer. Acts as a backend to files.
 *
 * Asset storage is identified by the following values arranged into a tuple:
 *
 * - "Filename" - Descriptive path for a file, although not necessarily a physical location. This could include
 *   custom directory names as a parent, as well as an extension.
 * - "Hash" - The SHA1 of the file. This means that multiple files with the same Filename could be
 *   stored independently (depending on implementation) as long as they have different hashes.
 *   When a variant is identified, this value will refer to the hash of the file it was generated
 *   from, not the hash of the actual generated file.
 * - "Variant" - An arbitrary string (which should not contain filesystem invalid characters) used
 *   to identify an asset which is a variant of an original. The asset storage backend has no knowledge
 *   of the mechanism used to generate this file, and is up to user code to perform the actual
 *   generation. An empty variant identifies this file as the original file.
 *
 * When assets are stored in the backend, user code may request one of the following conflict resolution
 * mechanisms:
 *
 * - CONFLICT_OVERWRITE - If there is an existing file with this tuple, overwrite it.
 * - CONFLICT_RENAME - If there is an existing file with this tuple, pick a new Filename for it and return it.
 *   This option is not allowed for use when storing variants, which should not modify the underlying
 *   Filename tuple value.
 * - CONFLICT_USE_EXISTING - If there is an existing file with this tuple, return the tuple for the
 *   existing file instead.
 * - CONFLICT_EXCEPTION - If there is an existing file with this tuple, throw an exception.
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
	 * Rename on file conflict. Rename rules will be determined by the backend.
	 *
	 * This option is not allowed for use when storing variants, which should not modify the underlying
	 * Filename tuple value.
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
	 * Get contents of a given file
	 *
     * @param string $filename Filename (not including assets)
	 * @param string $hash sha1 hash of the file content.
	 * If a variant is requested, this is the hash of the file before it was modified.
     * @param string|null $variant Optional variant string for this file
     * @return string Data from the file.
     */
    public function getAsString($filename, $hash, $variant = null);

    /**
	 * Get a stream for this file
	 *
     * @param string $filename Filename (not including assets)
	 * @param string $hash sha1 hash of the file content.
	 * If a variant is requested, this is the hash of the file before it was modified.
     * @param string|null $variant Optional variant string for this file
	 * @return resource Data stream
	 */
    public function getAsStream($filename, $hash, $variant = null);

    /**
	 * Get the url for the file
	 *
     * @param string $filename Filename (not including assets)
	 * @param string $hash sha1 hash of the file content.
	 * If a variant is requested, this is the hash of the file before it was modified.
     * @param string|null $variant Optional variant string for this file
     * @return string public url to this resource
     */
    public function getAsURL($filename, $hash, $variant = null);

	/**
	 * Get metadata for this file, if available
	 *
     * @param string $filename Filename (not including assets)
	 * @param string $hash sha1 hash of the file content.
	 * If a variant is requested, this is the hash of the file before it was modified.
     * @param string|null $variant Optional variant string for this file
	 * @return array|null File information, or null if no metadata available
	 */
	public function getMetadata($filename, $hash, $variant = null);

	/**
	 * Get mime type of this file
	 *
     * @param string $filename Filename (not including assets)
	 * @param string $hash sha1 hash of the file content.
	 * If a variant is requested, this is the hash of the file before it was modified.
     * @param string|null $variant Optional variant string for this file
	 * @return string Mime type for this file
	 */
	public function getMimeType($filename, $hash, $variant = null);

	/**
	 * Determine if a file exists with the given tuple
	 *
	 * @param string $filename Filename (not including assets)
	 * @param string $hash sha1 hash of the file content.
	 * If a variant is requested, this is the hash of the file before it was modified.
     * @param string|null $variant Optional variant string for this file
	 * @return bool Flag as to whether the file exists
	 */
	public function exists($filename, $hash, $variant = null);
}
