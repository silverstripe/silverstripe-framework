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
 * Write options have an additional $config parameter to provide additional options to the backend.
 * This is an associative array. Standard array options include 'visibility' and 'conflict'.
 *
 * 'conflict' config option determines the conflict resolution mechanism.
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
 * 'visibility' config option determines whether the file should be marked as publicly visible.
 * This may be assigned to one of the below values:
 *
 * - VISIBILITY_PUBLIC: This file may be accessed by any public user.
 * - VISIBILITY_PROTECTED: This file must be whitelisted for individual users before being made available to that user.
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
	 * Protect this file
	 */
	const VISIBILITY_PROTECTED = 'protected';

	/**
	 * Make this file public
	 */
	const VISIBILITY_PUBLIC = 'public';

	/**
	 * Return list of feature capabilities of this backend as an array.
	 * Array keys will be the options supported by $config, and the
	 * values will be the list of accepted values for each option (or
	 * true if any value is allowed).
	 *
	 * @return array
	 */
	public function getCapabilities();

	/**
	 * Assign a set of data to the backend
	 *
	 * @param string $data Raw binary/text content
	 * @param string $filename Name for the resulting file
	 * @param string $hash Hash of original file, if storing a variant.
	 * @param string $variant Name of variant, if storing a variant.
	 * @param array $config Write options. {@see AssetStore}
	 * @return array Tuple associative array (Filename, Hash, Variant) Unless storing a variant, the hash
	 * will be calculated from the given data.
	 */
	public function setFromString($data, $filename, $hash = null, $variant = null, $config = array());

	/**
	 * Assign a local file to the backend.
	 *
	 * @param string $path Absolute filesystem path to file
	 * @param string $filename Optional path to ask the backend to name as.
	 * Will default to the filename of the $path, excluding directories.
	 * @param string $hash Hash of original file, if storing a variant.
	 * @param string $variant Name of variant, if storing a variant.
	 * @param array $config Write options. {@see AssetStore}
	 * @return array Tuple associative array (Filename, Hash, Variant) Unless storing a variant, the hash
	 * will be calculated from the local file content.
	 */
    public function setFromLocalFile($path, $filename = null, $hash = null, $variant = null, $config = array());

	/**
	 * Assign a stream to the backend
	 *
	 * @param resource $stream Streamable resource
	 * @param string $filename Name for the resulting file
	 * @param string $hash Hash of original file, if storing a variant.
	 * @param string $variant Name of variant, if storing a variant.
	 * @param array $config Write options. {@see AssetStore}
	 * @return array Tuple associative array (Filename, Hash, Variant) Unless storing a variant, the hash
	 * will be calculated from the raw stream.
	 */
    public function setFromStream($stream, $filename, $hash = null, $variant = null, $config = array());

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
	 * @param bool $grant Ensures that the url for any protected assets is granted for the current user.
	 * If set to true, and the file is currently in protected mode, the asset store will ensure the
	 * returned URL is accessible for the duration of the current session / user.
	 * This will have no effect if the file is in published mode.
	 * This will not grant access to users other than the owner of the current session.
     * @return string public url to this resource
     */
    public function getAsURL($filename, $hash, $variant = null, $grant = true);

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
	 * Determine visibility of the given file
	 *
	 * @param string $filename
	 * @param string $hash
	 * @return string one of values defined by the constants VISIBILITY_PROTECTED or VISIBILITY_PUBLIC, or
	 * null if the file does not exist
	 */
	public function getVisibility($filename, $hash);

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

	/**
	 * Delete a file (and all variants) identified by the given filename and hash
	 *
	 * @param string $filename
	 * @param string $hash
	 * @return bool Flag if a file was deleted
	 */
	public function delete($filename, $hash);

	/**
	 * Publicly expose the file (and all variants) identified by the given filename and hash
	 *
	 * @param string $filename Filename (not including assets)
	 * @param string $hash sha1 hash of the file content.
	 */
	public function publish($filename, $hash);

	/**
	 * Protect a file (and all variants) from public access, identified by the given filename and hash.
	 *
	 * A protected file can be granted access to users on a per-session or per-user basis as response
	 * to any future invocations of {@see grant()} or {@see getAsURL()} with $grant = true
	 *
	 * @param string $filename Filename (not including assets)
	 * @param string $hash sha1 hash of the file content.
	 */
	public function protect($filename, $hash);

	/**
	 * Ensures that access to the specified protected file is granted for the current user.
	 * If this file is currently in protected mode, the asset store will ensure the
	 * returned asset for the duration of the current session / user.
	 * This will have no effect if the file is in published mode.
	 * This will not grant access to users other than the owner of the current session.
	 * Does not require a member to be logged in.
	 *
	 * @param string $filename
	 * @param string $hash
	 */
	public function grant($filename, $hash);

	/**
	 * Revoke access to the given file for the current user.
	 * Note: This will have no effect if the given file is public
	 *
	 * @param string $filename
	 * @param string $hash
	 */
	public function revoke($filename, $hash);

	/**
	 * Check if the current user can view the given file.
	 *
	 * @param string $filename
	 * @param string $hash
	 * @return bool True if the file is verified and grants access to the current session / user.
	 */
	public function canView($filename, $hash);
}
