<?php

namespace SilverStripe\Assets\Flysystem;

use Exception;
use League\Flysystem\Filesystem;

/**
 * Simple Flysystem implementation of GeneratedAssetHandler for storing generated content
 */
class GeneratedAssetHandler implements \SilverStripe\Assets\Storage\GeneratedAssetHandler {

	/**
	 * Flysystem store for files
	 *
	 * @var Filesystem
	 */
	protected $assetStore = null;

	/**
	 * Assign the asset backend. This must be a filesystem
	 * with an adapter of type {@see PublicAdapter}.
	 *
	 * @param Filesystem $store
	 * @return $this
	 */
	public function setFilesystem(Filesystem $store) {
		$this->assetStore = $store;
		return $this;
	}

	/**
	 * Get the asset backend
	 *
	 * @return Filesystem
	 * @throws Exception
	 */
	public function getFilesystem() {
		if(!$this->assetStore) {
			throw new Exception("Filesystem misconfiguration error");
		}
		return $this->assetStore;
	}

    public function getContentURL($filename, $callback = null) {
		$result = $this->checkOrCreate($filename, $callback);
		if(!$result) {
			return null;
		}
		/** @var PublicAdapter $adapter */
		$adapter = $this
			->getFilesystem()
			->getAdapter();
		return $adapter->getPublicUrl($filename);
	}

	public function getContent($filename, $callback = null) {
		$result = $this->checkOrCreate($filename, $callback);
		if(!$result) {
			return null;
		}
		return $this
			->getFilesystem()
			->read($filename);
	}

	/**
	 * Check if the file exists or that the $callback provided was able to regenerate it.
	 *
	 * @param string $filename
	 * @param callable $callback
	 * @return bool Whether or not the file exists
	 * @throws Exception If an error has occurred during save
	 */
	protected function checkOrCreate($filename, $callback = null) {
		// Check if there is an existing asset
		if ($this->getFilesystem()->has($filename)) {
			return true;
		}

		if (!$callback) {
			return false;
		}

		// Invoke regeneration and save
		$content = call_user_func($callback);
		$this->setContent($filename, $content);
		return true;
	}

	public function setContent($filename, $content) {
		// Store content
		$result = $this
				->getFilesystem()
				->put($filename, $content);

		if(!$result) {
			throw new Exception("Error regenerating file \"{$filename}\"");
		}
	}

	public function removeContent($filename) {
		if($this->getFilesystem()->has($filename)) {
			$handler = $this->getFilesystem()->get($filename);
			$handler->delete();
		}

	}


}
