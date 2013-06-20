<?php
/**
 * A collection of static methods for manipulating the filesystem.
 *
 * @package framework
 * @subpackage filesystem
 */
class Filesystem extends Object {

	/**
	 * @config
	 * @var integer Integer
	 */
	private static $file_create_mask = 02775;

	/**
	 * @config
	 * @var integer Integer
	 */
	private static $folder_create_mask = 02775;

	/**
	 * @var int
	 */
	protected static $cache_folderModTime;

	/**
	 * @config
	 *
	 * Array of file / folder regex expressions to exclude from the
	 * {@link Filesystem::sync()}
	 *
	 * @var array
	 */
	private static $sync_blacklisted_patterns = array(
		"/^\./",
		"/^_combinedfiles$/i",
		"/^_resampled$/i",
		"/^web.config/i",
		"/^Thumbs(.)/"
	);

	/**
	 * Create a folder on the filesystem, recursively.
	 * Uses {@link Filesystem::$folder_create_mask} to set filesystem permissions.
	 * Use {@link Folder::findOrMake()} to create a {@link Folder} database
	 * record automatically.
	 *
	 * @param String $folder Absolute folder path
	 */
	public static function makeFolder($folder) {
		if(!file_exists($base = dirname($folder))) self::makeFolder($base);
		if(!file_exists($folder)) mkdir($folder, Config::inst()->get('Filesystem', 'folder_create_mask'));
	}

	/**
	 * Remove a directory and all subdirectories and files.
	 *
	 * @param String $folder Absolute folder path
	 * @param Boolean $contentsOnly If this is true then the contents of the folder will be removed but not the
	 *                              folder itself
	 */
	public static function removeFolder($folder, $contentsOnly = false) {

		// remove a file encountered by a recursive call.
		if(is_file($folder) || is_link($folder)) {
			unlink($folder);
		} else {
			$dir = opendir($folder);
			while($file = readdir($dir)) {
				if(($file == '.' || $file == '..')) continue;
				else {
					self::removeFolder($folder . '/' . $file);
				}
			}
			closedir($dir);
			if(!$contentsOnly) rmdir($folder);
		}
	}

	/**
	 * Cleanup function to reset all the Filename fields.  Visit File/fixfiles to call.
	 */
	public function fixfiles() {
		if(!Permission::check('ADMIN')) return Security::permissionFailure($this);

		$files = DataObject::get("File");
		foreach($files as $file) {
			$file->updateFilesystem();
			echo "<li>", $file->Filename;
			$file->write();
		}
		echo "<p>Done!";
	}

	/**
	 * Return the most recent modification time of anything in the folder.
	 *
	 * @param $folder The folder, relative to the site root
	 * @param $extensionList An option array of file extensions to limit the search to
	 * @return String Same as filemtime() format.
	 */
	public static function folderModTime($folder, $extensionList = null, $recursiveCall = false) {
		//$cacheID = $folder . ',' . implode(',', $extensionList);
		//if(!$recursiveCall && self::$cache_folderModTime[$cacheID]) return self::$cache_folderModTime[$cacheID];

		$modTime = 0;
		if(!Filesystem::isAbsolute($folder)) $folder = Director::baseFolder() . '/' . $folder;

		$items = scandir($folder);
		foreach($items as $item) {
			if($item[0] != '.') {
				// Recurse into folders
				if(is_dir("$folder/$item")) {
					$modTime = max($modTime, self::folderModTime("$folder/$item", $extensionList, true));

				// Check files
				} else {
					if($extensionList) $extension = strtolower(substr($item,strrpos($item,'.')+1));
					if(!$extensionList || in_array($extension, $extensionList)) {
						$modTime = max($modTime, filemtime("$folder/$item"));
					}
				}
			}
		}

		//if(!$recursiveCall) self::$cache_folderModTime[$cacheID] = $modTime;
		return $modTime;
	}

	/**
	 * Returns true if the given filename is an absolute file reference.
	 * Works on Linux and Windows.
	 *
	 * @param String $filename Absolute or relative filename, with or without path.
	 * @return Boolean
	 */
	public static function isAbsolute($filename) {
		if($_ENV['OS'] == "Windows_NT" || $_SERVER['WINDIR']) return $filename[1] == ':' && $filename[2] == '/';
		else return $filename[0] == '/';
	}

	/**
	 * This function ensures the file table is correct with the files in the assets folder.
	 *
	 * If a Folder record ID is given, all of that folder's children will be synchronised.
	 * If the given Folder ID isn't found, or not specified at all, then everything will
	 * be synchronised from the root folder (singleton Folder).
	 *
	 * See {@link File->updateFilesystem()} to sync properties of a single database record
	 * back to the equivalent filesystem record.
	 *
	 * @param int $folderID Folder ID to sync along with all it's children
	 * @param Boolean $syncLinkTracking Determines if the link tracking data should also
	 *        be updated via {@link SiteTree->syncLinkTracking()}. Setting this to FALSE
	 *        means that broken links inside page content are not noticed, at least until the next
	 *        call to {@link SiteTree->write()} on this page.
	 * @return string Localized status message
	 */
	public static function sync($folderID = null, $syncLinkTracking = true) {
		$folder = DataObject::get_by_id('Folder', (int) $folderID);
		if(!($folder && $folder->exists())) $folder = singleton('Folder');

		$results = $folder->syncChildren();
		$finished = false;
		while(!$finished) {
			$orphans = DB::query('SELECT "ChildFile"."ID" FROM "File" AS "ChildFile"
				LEFT JOIN "File" AS "ParentFile" ON "ChildFile"."ParentID" = "ParentFile"."ID"
				WHERE "ParentFile"."ID" IS NULL AND "ChildFile"."ParentID" > 0');
			$finished = true;
			if($orphans) foreach($orphans as $orphan) {
				$finished = false;
				// Delete the database record but leave the filesystem alone
				$file = DataObject::get_by_id("File", $orphan['ID']);
				$file->deleteDatabaseOnly();
				unset($file);
			}
		}

		// Update the image tracking of all pages
		if($syncLinkTracking) {
			if(class_exists('SiteTree')) {

				// if subsites exist, go through each subsite and sync each subsite's pages.
				// disabling the filter doesn't work reliably, because writing pages that share
				// the same URLSegment between subsites will break, e.g. "home" between two
				// sites will modify one of them to "home-2", thinking it's a duplicate. The
				// check before a write is done in SiteTree::validURLSegment()
				if(class_exists('Subsite')) {
					// loop through each subsite ID, changing the subsite, then query it's pages
					foreach(Subsite::get()->getIDList() as $id) {
						Subsite::changeSubsite($id);
						foreach(SiteTree::get() as $page) {
							// syncLinkTracking is called by SiteTree::onBeforeWrite().
							// Call it without affecting the page version, as this is an internal change.
							$page->writeWithoutVersion();
						}
					}

					// change back to the main site so the foreach below works
					Subsite::changeSubsite(0);
				}

				foreach(SiteTree::get() as $page) {
					// syncLinkTracking is called by SiteTree::onBeforeWrite().
					// Call it without affecting the page version, as this is an internal change.
					$page->writeWithoutVersion();
				}
			}
		}

		return _t(
			'Filesystem.SYNCRESULTS',
			'Sync complete: {createdcount} items created, {deletedcount} items deleted',
			array('createdcount' => (int)$results['added'], 'deletedcount' => (int)$results['deleted'])
		);
	}

}

