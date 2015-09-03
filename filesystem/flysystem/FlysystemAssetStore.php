<?php

namespace SilverStripe\Filesystem\Flysystem;

use Config;
use Injector;
use InvalidArgumentException;
use League\Flysystem\Exception;
use League\Flysystem\Filesystem;
use League\Flysystem\Util;
use SilverStripe\Filesystem\Storage\AssetNameGenerator;
use SilverStripe\Filesystem\Storage\AssetStore;

/**
 * Asset store based on flysystem Filesystem as a backend
 *
 * @package framework
 * @subpackage filesystem
 */
class FlysystemAssetStore implements AssetStore {

	/**
	 * @var Filesystem
	 */
	private $filesystem = null;

	/**
	 * Enable to use legacy filename behaviour (omits hash)
	 *
	 * @config
	 * @var bool
	 */
	private static $legacy_filenames = false;

	/**
	 * Assign new flysystem backend
	 *
	 * @param Filesystem $filesystem
	 * @return $this
	 */
	public function setFilesystem(Filesystem $filesystem) {
		$this->filesystem = $filesystem;
		return $this;
	}

	/**
	 * Get the currently assigned flysystem backend
	 *
	 * @return Filesystem
	 */
	public function getFilesystem() {
		return $this->filesystem;
	}

	public function getAsStream($hash, $filename, $variant = null) {
		$fileID = $this->getFileID($hash, $filename, $variant);
		return $this->getFilesystem()->readStream($fileID);
	}

	public function getAsString($hash, $filename, $variant = null) {
		$fileID = $this->getFileID($hash, $filename, $variant);
		return $this->getFilesystem()->read($fileID);
	}

	public function getAsURL($hash, $filename, $variant = null) {
		$fileID = $this->getFileID($hash, $filename, $variant);
		return $this->getFilesystem()->getPublicUrl($fileID);
	}

	public function setFromLocalFile($path, $filename = null, $conflictResolution = null) {		
		// Validate this file exists
		if(!file_exists($path)) {
			throw new InvalidArgumentException("$path does not exist");
		}

		// Get filename to save to
		if(empty($filename)) {
			$filename = basename($path);
		}

		// Callback for saving content
		$filesystem = $this->getFilesystem();
		$callback = function($fileID) use ($filesystem, $path) {
			// Read contents as string into flysystem
			$handle = fopen($path, 'r');
			if($handle === false) {
				throw new InvalidArgumentException("$path could not be opened for reading");
			}
			$result = $filesystem->putStream($fileID, $handle);
			fclose($handle);
			return $result;
		};

		// Submit to conflict check
		$hash = sha1_file($path);
		return $this->writeWithCallback($callback, $hash, $filename, $conflictResolution);
	}

	public function setFromString($data, $filename, $conflictResolution = null) {
		// Callback for saving content
		$filesystem = $this->getFilesystem();
		$callback = function($fileID) use ($filesystem, $data) {
			return $filesystem->put($fileID, $data);
		};

		// Submit to conflict check
		$hash = sha1($data);
		return $this->writeWithCallback($callback, $hash, $filename, $conflictResolution);
	}

	public function setFromStream($stream, $filename, $conflictResolution = null) {
		// If the stream isn't rewindable, write to a temporary filename
		if(!$this->isSeekableStream($stream)) {
			$path = $this->getStreamAsFile($stream);
			$result = $this->setFromLocalFile($path, $filename, $conflictResolution);
			unlink($path);
			return $result;
		}

		// Callback for saving content
		$filesystem = $this->getFilesystem();
		$callback = function($fileID) use ($filesystem, $stream) {
			return $filesystem->putStream($fileID, $stream);
		};

		// Submit to conflict check
		$hash = $this->getStreamSHA1($stream);
		return $this->writeWithCallback($callback, $hash, $filename, $conflictResolution);
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
	 * @param string $hash SHA1 of the file content
	 * @param string $filename Name for the resulting file
	 * @param string $conflictResolution {@see AssetStore}. Will default to one chosen by the backend
	 * @return array Tuple associative array (Filename, Hash, Variant)
	 * @throws Exception
	 */
	protected function writeWithCallback($callback, $hash, $filename, $conflictResolution = null) {
		$filename = $this->cleanFilename($filename);
		$fileID = $this->getFileID($hash, $filename);

		// Check conflict resolution scheme
		$resolvedID = $this->resolveConflicts($conflictResolution, $fileID);
		if($resolvedID === false) {
			// If defering to the existing file, return the sha of the existing file
			$stream = $this
				->getFilesystem()
				->readStream($fileID);
			$hash = $this->getStreamSHA1($stream);
		} else {
			// Submit and validate result
			$result = $callback($resolvedID);
			if(!$result) {
				throw new Exception("Could not save {$filename}");
			}
			
			// in case conflict resolution renamed the file, return the renamed
			$filename = $this->getOriginalFilename($resolvedID);
		}

		return array(
			'Hash' => $hash,
			'Filename' => $filename,
			'Variant' => ''
		);
	}

	public function getMetadata($hash, $filename, $variant = null) {
		$fileID = $this->getFileID($hash, $filename, $variant);
		return $this->getFilesystem()->getMetadata($fileID);
	}

	public function getMimeType($hash, $filename, $variant = null) {
		$fileID = $this->getFileID($hash, $filename, $variant);
		return $this->getFilesystem()->getMimetype($fileID);
	}

	/**
	 * Determine the path that should be written to, given the conflict resolution scheme
	 * 
	 * @param string $conflictResolution
	 * @param string $fileID
	 * @return string|false Safe filename to write to. If false, then don't write.
	 * @throws Exception
	 */
	protected function resolveConflicts($conflictResolution, $fileID) {
		// If overwrite is requested, simply put
		if($conflictResolution === AssetStore::CONFLICT_OVERWRITE) {
			return $fileID;
		}

		// Otherwise, check if this exists
		$exists = $this->getFilesystem()->has($fileID);
		if(!$exists) {
			return $fileID;
		}

		// Flysystem defaults to use_existing
		switch($conflictResolution) {
			// Throw tantrum
			case AssetStore::CONFLICT_EXCEPTION: {
				throw new \InvalidArgumentException("File already exists at path {$fileID}");
			}

			// Rename
			case AssetStore::CONFLICT_RENAME: {
				foreach($this->fileGeneratorFor($fileID) as $candidate) {
					// @todo better infinite loop breaking
					if(!$this->getFilesystem()->has($candidate)) {
						return $candidate;
					}
				}

				throw new \InvalidArgumentException("File could not be renamed with path {$fileID}");
			}

			// Default to use existing file
			case AssetStore::CONFLICT_USE_EXISTING:
			default: {
				return false;
			}
		}
	}

	/**
	 * Get an asset renamer for the given filename.
	 *
	 * @param string $fileID Adaptor specific identifier for this file/version
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
	 * Given a FileID, map this back to the original filename, trimming variant
	 *
	 * @param string $fileID Adaptor specific identifier for this file/version
	 * @param string $variant Out parameter for any found variant
	 * @return string
	 */
	protected function getOriginalFilename($fileID, &$variant = '') {
		// Remove variant
		$original = $fileID;
		$variant = '';
		if(preg_match('/^(?<before>((?<!__).)+)__(?<variant>[^\\.]+)(?<after>.*)$/', $fileID, $matches)) {
			$original = $matches['before'].$matches['after'];
			$variant = $matches['variant'];
		}

		// Remove hash
		return preg_replace(
			'/(?<hash>[a-zA-Z0-9]{10}\\/)(?<name>[^\\/]+)$/',
			'$2',
			$original
		);
	}

	/**
	 * Map file tuple (hash, name, variant) to a filename to be used by flysystem
	 *
	 * The resulting file will look something like my/directory/EA775CB4D4/filename__variant.jpg
	 *
	 * @param string $hash
	 * @param string $filename Name of file
	 * @param string $variant (if given)
	 * @return string Adaptor specific identifier for this file/version
	 */
	protected function getFileID($hash, $filename, $variant = null) {
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
		if(Config::inst()->get(__CLASS__, 'legacy_filenames')) {
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

}
