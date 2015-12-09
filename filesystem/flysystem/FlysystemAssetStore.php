<?php

namespace SilverStripe\Filesystem\Flysystem;

use Config;
use Generator;
use Injector;
use League\Flysystem\Directory;
use Session;
use Flushable;
use InvalidArgumentException;
use League\Flysystem\Exception;
use League\Flysystem\Filesystem;
use League\Flysystem\Util;
use SilverStripe\Filesystem\Storage\AssetNameGenerator;
use SilverStripe\Filesystem\Storage\AssetStore;
use SilverStripe\Filesystem\Storage\AssetStoreRouter;
use SS_HTTPResponse;

/**
 * Asset store based on flysystem Filesystem as a backend
 *
 * @package framework
 * @subpackage filesystem
 */
class FlysystemAssetStore implements AssetStore, AssetStoreRouter, Flushable {

	/**
	 * Session key to use for user grants
	 */
	const GRANTS_SESSION = 'AssetStore_Grants';

	/**
	 * @var Filesystem
	 */
	private $publicFilesystem = null;

	/**
	 * Filesystem to use for protected files
	 *
	 * @var Filesystem
	 */
	private $protectedFilesystem = null;

	/**
	 * Enable to use legacy filename behaviour (omits hash)
	 *
	 * Note that if using legacy filenames then duplicate files will not work.
	 *
	 * @config
	 * @var bool
	 */
	private static $legacy_filenames = false;

	/**
	 * Flag if empty folders are allowed.
	 * If false, empty folders are cleared up when their contents are deleted.
	 *
	 * @config
	 * @var bool
	 */
	private static $keep_empty_dirs = false;

	/**
	 * Custom headers to add to all custom file responses
	 *
	 * @config
	 * @var array
	 */
	private static $file_response_headers = array(
		'Cache-Control' => 'private'
	);

	/**
	 * Assign new flysystem backend
	 *
	 * @param Filesystem $filesystem
	 * @return $this
	 */
	public function setPublicFilesystem(Filesystem $filesystem) {
		if(!$filesystem->getAdapter() instanceof PublicAdapter) {
			throw new \InvalidArgumentException("Configured adapter must implement PublicAdapter");
		}
		$this->publicFilesystem = $filesystem;
		return $this;
	}

	/**
	 * Get the currently assigned flysystem backend
	 *
	 * @return Filesystem
	 */
	public function getPublicFilesystem() {
		return $this->publicFilesystem;
	}

	/**
	 * Assign filesystem to use for non-public files
	 *
	 * @param Filesystem $filesystem
	 * @return $this
	 */
	public function setProtectedFilesystem(Filesystem $filesystem) {
		if(!$filesystem->getAdapter() instanceof ProtectedAdapter) {
			throw new \InvalidArgumentException("Configured adapter must implement ProtectedAdapter");
		}
		$this->protectedFilesystem = $filesystem;
		return $this;
	}

	/**
	 * Get filesystem to use for non-public files
	 *
	 * @return Filesystem
	 */
	public function getProtectedFilesystem() {
		return $this->protectedFilesystem;
	}

	/**
	 * Return the store that contains the given fileID
	 *
	 * @param string $fileID Internal file identifier
	 * @return Filesystem
	 */
	protected function getFilesystemFor($fileID) {
		if($this->getPublicFilesystem()->has($fileID)) {
			return $this->getPublicFilesystem();
		}

		if($this->getProtectedFilesystem()->has($fileID)) {
			return $this->getProtectedFilesystem();
		}

		return null;
	}

	public function getCapabilities() {
		return array(
			'visibility' => array(
				self::VISIBILITY_PUBLIC,
				self::VISIBILITY_PROTECTED
			),
			'conflict' => array(
				self::CONFLICT_EXCEPTION,
				self::CONFLICT_OVERWRITE,
				self::CONFLICT_RENAME,
				self::CONFLICT_USE_EXISTING
			)
		);
	}

	public function getVisibility($filename, $hash) {
		$fileID = $this->getFileID($filename, $hash);
		if($this->getPublicFilesystem()->has($fileID)) {
			return self::VISIBILITY_PUBLIC;
		}

		if($this->getProtectedFilesystem()->has($fileID)) {
			return self::VISIBILITY_PROTECTED;
		}

		return null;
	}


	public function getAsStream($filename, $hash, $variant = null) {
		$fileID = $this->getFileID($filename, $hash, $variant);
		return $this
			->getFilesystemFor($fileID)
			->readStream($fileID);
	}

	public function getAsString($filename, $hash, $variant = null) {
		$fileID = $this->getFileID($filename, $hash, $variant);
		return $this
			->getFilesystemFor($fileID)
			->read($fileID);
	}

	public function getAsURL($filename, $hash, $variant = null, $grant = true) {
		if($grant) {
			$this->grant($filename, $hash);
		}
		$fileID = $this->getFileID($filename, $hash, $variant);

		// Check with filesystem this asset exists in
		$public = $this->getPublicFilesystem();
		$protected = $this->getProtectedFilesystem();
		if($public->has($fileID) || !$protected->has($fileID)) {
			/** @var PublicAdapter $publicAdapter */
			$publicAdapter = $public->getAdapter();
			return $publicAdapter->getPublicUrl($fileID);
		} else {
			/** @var ProtectedAdapter $protectedAdapter */
			$protectedAdapter = $protected->getAdapter();
			return $protectedAdapter->getProtectedUrl($fileID);
		}
	}

	public function setFromLocalFile($path, $filename = null, $hash = null, $variant = null, $config = array()) {
		// Validate this file exists
		if(!file_exists($path)) {
			throw new InvalidArgumentException("$path does not exist");
		}

		// Get filename to save to
		if(empty($filename)) {
			$filename = basename($path);
		}

		// Callback for saving content
		$callback = function(Filesystem $filesystem, $fileID) use ($path) {
			// Read contents as string into flysystem
			$handle = fopen($path, 'r');
			if($handle === false) {
				throw new InvalidArgumentException("$path could not be opened for reading");
			}
			$result = $filesystem->putStream($fileID, $handle);
			fclose($handle);
			return $result;
		};

		// When saving original filename, generate hash
		if(!$variant) {
			$hash = sha1_file($path);
		}

		// Submit to conflict check
		return $this->writeWithCallback($callback, $filename, $hash, $variant, $config);
	}

	public function setFromString($data, $filename, $hash = null, $variant = null, $config = array()) {
		// Callback for saving content
		$callback = function(Filesystem $filesystem, $fileID) use ($data) {
			return $filesystem->put($fileID, $data);
		};

		// When saving original filename, generate hash
		if(!$variant) {
			$hash = sha1($data);
		}

		// Submit to conflict check
		return $this->writeWithCallback($callback, $filename, $hash, $variant, $config);
	}

	public function setFromStream($stream, $filename, $hash = null, $variant = null, $config = array()) {
		// If the stream isn't rewindable, write to a temporary filename
		if(!$this->isSeekableStream($stream)) {
			$path = $this->getStreamAsFile($stream);
			$result = $this->setFromLocalFile($path, $filename, $hash, $variant, $config);
			unlink($path);
			return $result;
		}

		// Callback for saving content
		$callback = function(Filesystem $filesystem, $fileID) use ($stream) {
			return $filesystem->putStream($fileID, $stream);
		};

		// When saving original filename, generate hash
		if(!$variant) {
			$hash = $this->getStreamSHA1($stream);
		}

		// Submit to conflict check
		return $this->writeWithCallback($callback, $filename, $hash, $variant, $config);
	}

	public function delete($filename, $hash) {
		$fileID = $this->getFileID($filename, $hash);
		$protected = $this->deleteFromFilesystem($fileID, $this->getProtectedFilesystem());
		$public = $this->deleteFromFilesystem($fileID, $this->getPublicFilesystem());
		return $protected || $public;
	}

	/**
	 * Delete the given file (and any variants) in the given {@see Filesystem}
	 *
	 * @param string $fileID
	 * @param Filesystem $filesystem
	 * @return bool True if a file was deleted
	 */
	protected function deleteFromFilesystem($fileID, Filesystem $filesystem) {
		$deleted = false;
		foreach($this->findVariants($fileID, $filesystem) as $nextID) {
			$filesystem->delete($nextID);
			$deleted = true;
		}

		// Truncate empty dirs
		$this->truncateDirectory(dirname($fileID), $filesystem);

		return $deleted;
	}

	/**
	 * Clear directory if it's empty
	 *
	 * @param string $dirname Name of directory
	 * @param Filesystem $filesystem
	 */
	protected function truncateDirectory($dirname, Filesystem $filesystem) {
		if ($dirname
			&& ! Config::inst()->get(get_class($this), 'keep_empty_dirs')
			&& ! $filesystem->listContents($dirname)
		) {
			$filesystem->deleteDir($dirname);
		}
	}

	/**
	 * Returns an iterable {@see Generator} of all files / variants for the given $fileID in the given $filesystem
	 * This includes the empty (no) variant.
	 *
	 * @param string $fileID ID of original file to compare with.
	 * @param Filesystem $filesystem
	 * @return Generator
	 */
	protected function findVariants($fileID, Filesystem $filesystem) {
		foreach($filesystem->listContents(dirname($fileID)) as $next) {
			if($next['type'] !== 'file') {
				continue;
			}
			$nextID = $next['path'];
			// Compare given file to target, omitting variant
			if($fileID === $this->removeVariant($nextID)) {
				yield $nextID;
			}
		}
	}

	public function publish($filename, $hash) {
		$fileID = $this->getFileID($filename, $hash);
		$protected = $this->getProtectedFilesystem();
		$public = $this->getPublicFilesystem();
		$this->moveBetweenFilesystems($fileID, $protected, $public);
	}

	public function protect($filename, $hash) {
		$fileID = $this->getFileID($filename, $hash);
		$public = $this->getPublicFilesystem();
		$protected = $this->getProtectedFilesystem();
		$this->moveBetweenFilesystems($fileID, $public, $protected);
	}

	/**
	 * Move a file (and its associative variants) between filesystems
	 *
	 * @param string $fileID
	 * @param Filesystem $from
	 * @param Filesystem $to
	 */
	protected function moveBetweenFilesystems($fileID, Filesystem $from, Filesystem $to) {
		foreach($this->findVariants($fileID, $from) as $nextID) {
			// Copy via stream
			$stream = $from->readStream($nextID);
			$to->putStream($nextID, $stream);
			fclose($stream);
			$from->delete($nextID);
		}

		// Truncate empty dirs
		$this->truncateDirectory(dirname($fileID), $from);
	}

	public function grant($filename, $hash) {
		$fileID = $this->getFileID($filename, $hash);
		$granted = Session::get(self::GRANTS_SESSION) ?: array();
		$granted[$fileID] = true;
		Session::set(self::GRANTS_SESSION, $granted);
	}

	public function revoke($filename, $hash) {
		$fileID = $this->getFileID($filename, $hash);
		$granted = Session::get(self::GRANTS_SESSION) ?: array();
		unset($granted[$fileID]);
		if($granted) {
			Session::set(self::GRANTS_SESSION, $granted);
		} else {
			Session::clear(self::GRANTS_SESSION);
		}
	}

	public function canView($filename, $hash) {
		$fileID = $this->getFileID($filename, $hash);
		if($this->getProtectedFilesystem()->has($fileID)) {
			return $this->isGranted($fileID);
		}
		return true;
	}

	/**
	 * Determine if a grant exists for the given FileID
	 *
	 * @param string $fileID
	 * @return bool
	 */
	protected function isGranted($fileID) {
		// Since permissions are applied to the non-variant only,
		// map back to the original file before checking
		$originalID = $this->removeVariant($fileID);
		$granted = Session::get('AssetStore_Grants') ?: array();
		return !empty($granted[$originalID]);
	}

	/**
	 * get sha1 hash from stream
	 *
	 * @param resource $stream
	 * @return string str1 hash
	 */
	protected function getStreamSHA1($stream) {
		Util::rewindStream($stream);
		$context = hash_init('sha1');
		hash_update_stream($context, $stream);
		return hash_final($context);
	}

	/**
	 * Get stream as a file
	 *
	 * @param resource $stream
	 * @return string Filename of resulting stream content
	 * @throws Exception
	 */
	protected function getStreamAsFile($stream) {
		// Get temporary file and name
		$file = tempnam(sys_get_temp_dir(), 'ssflysystem');
		$buffer = fopen($file, 'w');
		if (!$buffer) {
			throw new Exception("Could not create temporary file");
		}

		// Transfer from given stream
		Util::rewindStream($stream);
		stream_copy_to_stream($stream, $buffer);
		if (! fclose($buffer)) {
			throw new Exception("Could not write stream to temporary file");
		}

		return $file;
	}

	/**
	 * Determine if this stream is seekable
	 *
	 * @param resource $stream
	 * @return bool True if this stream is seekable
	 */
	protected function isSeekableStream($stream) {
		return Util::isSeekableStream($stream);
	}

	/**
	 * Invokes the conflict resolution scheme on the given content, and invokes a callback if
	 * the storage request is approved.
	 *
	 * @param callable $callback Will be invoked and passed a fileID if the file should be stored
	 * @param string $filename Name for the resulting file
	 * @param string $hash SHA1 of the original file content
	 * @param string $variant Variant to write
	 * @param array $config Write options. {@see AssetStore}
	 * @return array Tuple associative array (Filename, Hash, Variant)
	 * @throws Exception
	 */
	protected function writeWithCallback($callback, $filename, $hash, $variant = null, $config = array()) {
		// Set default conflict resolution
		if(empty($config['conflict'])) {
			$conflictResolution = $this->getDefaultConflictResolution($variant);
		} else {
			$conflictResolution = $config['conflict'];
		}
		
		// Validate parameters
		if($variant && $conflictResolution === AssetStore::CONFLICT_RENAME) {
			// As variants must follow predictable naming rules, they should not be dynamically renamed
			throw new InvalidArgumentException("Rename cannot be used when writing variants");
		}
		if(!$filename) {
			throw new InvalidArgumentException("Filename is missing");
		}
		if(!$hash) {
			throw new InvalidArgumentException("File hash is missing");
		}

		$filename = $this->cleanFilename($filename);
		$fileID = $this->getFileID($filename, $hash, $variant);

		// Check conflict resolution scheme
		$resolvedID = $this->resolveConflicts($conflictResolution, $fileID);
		if($resolvedID !== false) {
			// Check if source file already exists on the filesystem
			$mainID = $this->getFileID($filename, $hash);
			$filesystem = $this->getFilesystemFor($mainID);

			// If writing a new file use the correct visibility
			if(!$filesystem) {
				// Default to public store unless requesting protected store
				if(isset($config['visibility']) && $config['visibility'] === self::VISIBILITY_PROTECTED) {
					$filesystem = $this->getProtectedFilesystem();
				} else {
					$filesystem = $this->getPublicFilesystem();
				}
			}

			// Submit and validate result
			$result = $callback($filesystem, $resolvedID);
			if(!$result) {
				throw new Exception("Could not save {$filename}");
			}

			// in case conflict resolution renamed the file, return the renamed
			$filename = $this->getOriginalFilename($resolvedID);
			
		} elseif(empty($variant)) {
			// If deferring to the existing file, return the sha of the existing file,
			// unless we are writing a variant (which has the same hash value as its original file)
			$stream = $this
				->getFilesystemFor($fileID)
				->readStream($fileID);
			$hash = $this->getStreamSHA1($stream);
		}

		return array(
			'Filename' => $filename,
			'Hash' => $hash,
			'Variant' => $variant
		);
	}

	/**
	 * Choose a default conflict resolution
	 *
	 * @param string $variant
	 * @return string
	 */
	protected function getDefaultConflictResolution($variant) {
		// If using new naming scheme (segment by hash) it's normally safe to overwrite files.
		// Variants are also normally safe to overwrite, since lazy-generation is implemented at a higher level.
		$legacy = $this->useLegacyFilenames();
		if(!$legacy || $variant) {
			return AssetStore::CONFLICT_OVERWRITE;
		}
		
		// Legacy behaviour is to rename
		return AssetStore::CONFLICT_RENAME;
	}

	/**
	 * Determine if legacy filenames should be used. These do not have hash path parts.
	 *
	 * @return bool
	 */
	protected function useLegacyFilenames() {
		return Config::inst()->get(get_class($this), 'legacy_filenames');
	}

	public function getMetadata($filename, $hash, $variant = null) {
		$fileID = $this->getFileID($filename, $hash, $variant);
		$filesystem = $this->getFilesystemFor($fileID);
		if($filesystem) {
			return $filesystem->getMetadata($fileID);
		}
		return null;
	}

	public function getMimeType($filename, $hash, $variant = null) {
		$fileID = $this->getFileID($filename, $hash, $variant);
		$filesystem = $this->getFilesystemFor($fileID);
		if($filesystem) {
			return $filesystem->getMimetype($fileID);
		}
		return null;
	}

	public function exists($filename, $hash, $variant = null) {
		$fileID = $this->getFileID($filename, $hash, $variant);
		$filesystem = $this->getFilesystemFor($fileID);
		return !empty($filesystem);
	}

	/**
	 * Determine the path that should be written to, given the conflict resolution scheme
	 * 
	 * @param string $conflictResolution
	 * @param string $fileID
	 * @return string|false Safe filename to write to. If false, then don't write, and use existing file.
	 * @throws Exception
	 */
	protected function resolveConflicts($conflictResolution, $fileID) {
		// If overwrite is requested, simply put
		if($conflictResolution === AssetStore::CONFLICT_OVERWRITE) {
			return $fileID;
		}

		// Otherwise, check if this exists
		$exists = $this->getFilesystemFor($fileID);
		if(!$exists) {
			return $fileID;
		}

		// Flysystem defaults to use_existing
		switch($conflictResolution) {
			// Throw tantrum
			case static::CONFLICT_EXCEPTION: {
				throw new \InvalidArgumentException("File already exists at path {$fileID}");
			}

			// Rename
			case static::CONFLICT_RENAME: {
				foreach($this->fileGeneratorFor($fileID) as $candidate) {
					if(!$this->getFilesystemFor($candidate)) {
						return $candidate;
					}
				}

				throw new \InvalidArgumentException("File could not be renamed with path {$fileID}");
			}

			// Use existing file
			case static::CONFLICT_USE_EXISTING:
			default: {
				return false;
			}
		}
	}

	/**
	 * Get an asset renamer for the given filename.
	 *
	 * @param string $fileID Adapter specific identifier for this file/version
	 * @return AssetNameGenerator
	 */
	protected function fileGeneratorFor($fileID){
		return Injector::inst()->createWithArgs('AssetNameGenerator', array($fileID));
	}

	/**
	 * Performs filename cleanup before sending it back.
	 *
	 * This name should not contain hash or variants.
	 *
	 * @param string $filename
	 * @return string
	 */
	protected function cleanFilename($filename) {
		// Since we use double underscore to delimit variants, eradicate them from filename
		return preg_replace('/_{2,}/', '_', $filename);
	}

	/**
	 * Given a FileID, map this back to the original filename, trimming variant and hash
	 *
	 * @param string $fileID Adapter specific identifier for this file/version
	 * @return string Filename for this file, omitting hash and variant
	 */
	protected function getOriginalFilename($fileID) {
		// Remove variant
		$originalID = $this->removeVariant($fileID);

		// Remove hash (unless using legacy filenames, without hash)
		if($this->useLegacyFilenames()) {
			return $originalID;
		} else {
			return preg_replace(
				'/(?<hash>[a-zA-Z0-9]{10}\\/)(?<name>[^\\/]+)$/',
				'$2',
				$originalID
			);
		}
	}

	/**
	 * Remove variant from a fileID
	 *
	 * @param string $fileID
	 * @return string FileID without variant
	 */
	protected function removeVariant($fileID) {
		// Check variant
		if (preg_match('/^(?<before>((?<!__).)+)__(?<variant>[^\\.]+)(?<after>.*)$/', $fileID, $matches)) {
			return $matches['before'] . $matches['after'];
		}
		// There is no variant, so return original value
		return $fileID;
	}

	/**
	 * Map file tuple (hash, name, variant) to a filename to be used by flysystem
	 *
	 * The resulting file will look something like my/directory/EA775CB4D4/filename__variant.jpg
	 *
	 * @param string $filename Name of file
	 * @param string $hash Hash of original file
	 * @param string $variant (if given)
	 * @return string Adapter specific identifier for this file/version
	 */
	protected function getFileID($filename, $hash, $variant = null) {
		// Since we use double underscore to delimit variants, eradicate them from filename
		$filename = $this->cleanFilename($filename);
		$name = basename($filename);

		// Split extension
		$extension = null;
		if(($pos = strpos($name, '.')) !== false) {
			$extension = substr($name, $pos);
			$name = substr($name, 0, $pos);
		}

		// Unless in legacy mode, inject hash just prior to the filename
		if($this->useLegacyFilenames()) {
			$fileID = $name;
		} else {
			$fileID = substr($hash, 0, 10) . '/' . $name;
		}

		// Add directory
		$dirname = ltrim(dirname($filename), '.');
		if($dirname) {
			$fileID = $dirname . '/' . $fileID;
		}

		// Add variant
		if($variant) {
			$fileID .= '__' . $variant;
		}

		// Add extension
		if($extension) {
			$fileID .= $extension;
		}

		return $fileID;
	}

	/**
	 * Ensure each adapter re-generates its own server configuration files
	 */
	public static function flush() {
		// Ensure that this instance is constructed on flush, thus forcing
		// bootstrapping of necessary .htaccess / web.config files
		$instance = singleton('AssetStore');
		if ($instance instanceof FlysystemAssetStore) {
			$publicAdapter = $instance->getPublicFilesystem()->getAdapter();
			if($publicAdapter instanceof AssetAdapter) {
				$publicAdapter->flush();
			}
			$protectedAdapter = $instance->getProtectedFilesystem()->getAdapter();
			if($protectedAdapter instanceof AssetAdapter) {
				$protectedAdapter->flush();
			}
		}
	}

	public function getResponseFor($asset) {
		// Check if file exists
		$filesystem = $this->getFilesystemFor($asset);
		if(!$filesystem) {
			return $this->createInvalidResponse();
		}

		// Block directory access
		if($filesystem->get($asset) instanceof Directory) {
			return $this->createDeniedResponse();
		}

		// Deny if file is protected and denied
		if($filesystem === $this->getProtectedFilesystem() && !$this->isGranted($asset)) {
			return $this->createDeniedResponse();
		}

		// Serve up file response
		return $this->createResponseFor($filesystem, $asset);
	}

	/**
	 * Generate an {@see SS_HTTPResponse} for the given file from the source filesystem
	 * @param Filesystem $flysystem
	 * @param string $fileID
	 * @return SS_HTTPResponse
	 */
	protected function createResponseFor(Filesystem $flysystem, $fileID) {
		// Build response body
		// @todo: gzip / buffer response?
		$body = $flysystem->read($fileID);
		$mime = $flysystem->getMimetype($fileID);
		$response = new SS_HTTPResponse($body, 200);

		// Add headers
		$response->addHeader('Content-Type', $mime);
		$headers = Config::inst()->get(get_class($this), 'file_response_headers');
		foreach($headers as $header => $value) {
			$response->addHeader($header, $value);
		}
		return $response;
	}

	/**
	 * Generate a 403 response for the given file
	 *
	 * @return SS_HTTPResponse
	 */
	protected function createDeniedResponse() {
		$response = new SS_HTTPResponse(null, 403);
		return $response;
	}

	/**
	 * Generate 404 response for missing file requests
	 *
	 * @return SS_HTTPResponse
	 */
	protected function createInvalidResponse() {
		$response = new SS_HTTPResponse('', 404);

		// Show message in dev
		if(!\Director::isLive()) {
			$response->setBody($response->getStatusDescription());
		}

		return $response;
	}
}
