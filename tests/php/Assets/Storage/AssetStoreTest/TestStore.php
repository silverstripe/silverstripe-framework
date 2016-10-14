<?php

namespace SilverStripe\Assets\Tests\Storage\AssetStoreTest;

use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;
use SilverStripe\Assets\Filesystem as SSFilesystem;
use SilverStripe\Assets\Flysystem\FlysystemAssetStore;
use SilverStripe\Assets\Flysystem\ProtectedAssetAdapter;
use SilverStripe\Assets\Flysystem\PublicAssetAdapter;
use SilverStripe\Assets\Storage\AssetContainer;
use SilverStripe\Assets\Flysystem\GeneratedAssetHandler;
use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\Requirements;

/**
 * Allows you to mock a backend store in a custom directory beneath assets.
 */
class TestAssetStore extends FlysystemAssetStore
{

	/**
	 * Enable disclosure of secure assets
	 *
	 * @config
	 * @var int
	 */
	private static $denied_response_code = 403;

	/**
	 * Set to true|false to override all isSeekableStream calls
	 *
	 * @var null|bool
	 */
	public static $seekable_override = null;

	/**
	 * Base dir of current file
	 *
	 * @var string
	 */
	public static $basedir = null;

	/**
	 * Set this store as the new asset backend
	 *
	 * @param string $basedir Basedir to store assets, which will be placed beneath 'assets' folder
	 */
	public static function activate($basedir)
	{
		// Assign this as the new store
		$publicAdapter = new PublicAssetAdapter(ASSETS_PATH . '/' . $basedir);
		$publicFilesystem = new Filesystem($publicAdapter, [
			'visibility' => AdapterInterface::VISIBILITY_PUBLIC
		]);
		$protectedAdapter = new ProtectedAssetAdapter(ASSETS_PATH . '/' . $basedir . '/.protected');
		$protectedFilesystem = new Filesystem($protectedAdapter, [
			'visibility' => AdapterInterface::VISIBILITY_PRIVATE
		]);

		$backend = new TestAssetStore();
		$backend->setPublicFilesystem($publicFilesystem);
		$backend->setProtectedFilesystem($protectedFilesystem);
		Injector::inst()->registerService($backend, 'AssetStore');

		// Assign flysystem backend to generated asset handler at the same time
		$generated = new GeneratedAssetHandler();
		$generated->setFilesystem($publicFilesystem);
		Injector::inst()->registerService($generated, 'GeneratedAssetHandler');
		Requirements::backend()->setAssetHandler($generated);

		// Disable legacy and set defaults
		Config::inst()->remove(get_class(new FlysystemAssetStore()), 'legacy_filenames');
		Config::inst()->update('SilverStripe\\Control\\Director', 'alternate_base_url', '/');
		DBFile::config()->force_resample = false;
		File::config()->force_resample = false;
		self::reset();
		self::$basedir = $basedir;

		// Ensure basedir exists
		SSFilesystem::makeFolder(self::base_path());
	}

	/**
	 * Get absolute path to basedir
	 *
	 * @return string
	 */
	public static function base_path()
	{
		if (!self::$basedir) {
			return null;
		}
		return ASSETS_PATH . '/' . self::$basedir;
	}

	/**
	 * Reset defaults for this store
	 */
	public static function reset()
	{
		// Remove all files in this store
		if (self::$basedir) {
			$path = self::base_path();
			if (file_exists($path)) {
				SSFilesystem::removeFolder($path);
			}
		}
		self::$seekable_override = null;
		self::$basedir = null;
	}

	/**
	 * Helper method to get local filesystem path for this file
	 *
	 * @param AssetContainer $asset
	 * @return string
	 */
	public static function getLocalPath(AssetContainer $asset)
	{
		if ($asset instanceof Folder) {
			return self::base_path() . '/' . $asset->getFilename();
		}
		if ($asset instanceof File) {
			$asset = $asset->File;
		}
		// Extract filesystem used to store this object
		/** @var TestAssetStore $assetStore */
		$assetStore = Injector::inst()->get('AssetStore');
		$fileID = $assetStore->getFileID($asset->Filename, $asset->Hash, $asset->Variant);
		$filesystem = $assetStore->getProtectedFilesystem();
		if (!$filesystem->has($fileID)) {
			$filesystem = $assetStore->getPublicFilesystem();
		}
		/** @var Local $adapter */
		$adapter = $filesystem->getAdapter();
		return $adapter->applyPathPrefix($fileID);
	}

	public function cleanFilename($filename)
	{
		return parent::cleanFilename($filename);
	}

	public function getFileID($filename, $hash, $variant = null)
	{
		return parent::getFileID($filename, $hash, $variant);
	}


	public function getOriginalFilename($fileID)
	{
		return parent::getOriginalFilename($fileID);
	}

	public function removeVariant($fileID)
	{
		return parent::removeVariant($fileID);
	}

	public function getDefaultConflictResolution($variant)
	{
		return parent::getDefaultConflictResolution($variant);
	}

	protected function isSeekableStream($stream)
	{
		if (isset(self::$seekable_override)) {
			return self::$seekable_override;
		}
		return parent::isSeekableStream($stream);
	}
}
