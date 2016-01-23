<?php

namespace SilverStripe\Filesystem\Storage;

use SilverStripe\Filesystem\Storage\AssetStore;

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
interface AssetContainer
{

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
	 * @return string Data from the file in this container
	 */
	public function getString();

	/**
	 * @return resource Data stream to the asset in this container
	 */
	public function getStream();

	/**
	 * @param bool $grant Ensures that the url for any protected assets is granted for the current user.
	 * If set to true, and the file is currently in protected mode, the asset store will ensure the
	 * returned URL is accessible for the duration of the current session / user.
	 * This will have no effect if the file is in published mode.
	 * This will not grant access to users other than the owner of the current session.
	 * @return string public url to the asset in this container
	 */
	public function getURL($grant = true);

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
	 * Determine visibility of the given file
	 *
	 * @return string one of values defined by the constants VISIBILITY_PROTECTED or VISIBILITY_PUBLIC, or
	 * null if the file does not exist
	 */
	public function getVisibility();

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

	/**
	 * Delete a file (and all variants).
	 * {@see AssetStore::delete()}
	 *
	 * @return bool Flag if a file was deleted
	 */
	public function deleteFile();

	/**
	 * Publicly expose the file (and all variants) identified by the given filename and hash
	 * {@see AssetStore::publish}
	 */
	public function publishFile();

	/**
	 * Protect a file (and all variants) from public access, identified by the given filename and hash.
	 * {@see AssetStore::protect()}
	 */
	public function protectFile();

	/**
	 * Ensures that access to the specified protected file is granted for the current user.
	 * If this file is currently in protected mode, the asset store will ensure the
	 * returned asset for the duration of the current session / user.
	 * This will have no effect if the file is in published mode.
	 * This will not grant access to users other than the owner of the current session.
	 * Does not require a member to be logged in.
	 */
	public function grantFile();

	/**
	 * Revoke access to the given file for the current user.
	 * Note: This will have no effect if the given file is public
	 */
	public function revokeFile();

	/**
	 * Check if the current user can view the given file.
	 *
	 * @return bool True if the file is verified and grants access to the current session / user.
	 */
	public function canViewFile();
}
