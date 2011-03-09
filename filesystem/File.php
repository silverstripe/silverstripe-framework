<?php
/**
 * This class handles the representation of a file on the filesystem within the framework.
 * Most of the methods also handle the {@link Folder} subclass.
 * 
 * Note: The files are stored in the assets/ directory, but sapphire
 * looks at the db object to gather information about a file such as URL
 * It then uses this for all processing functions (like image manipulation).
 * 
 * <b>Security</b>
 * 
 * Caution: It is recommended to disable any script execution in the "assets/"
 * directory in the webserver configuration, to reduce the risk of exploits.
 * See http://doc.silverstripe.org/secure-development#filesystem
 * 
 * <b>Properties</b>
 * 
 * - "Name": File name (including extension) or folder name.
 *   Should be the same as the actual filesystem. 
 * - "Title": Optional title of the file (for display purposes only).
 *   Defaults to "Name".
 * - "Filename": Path of the file or folder, relative to the webroot.
 *   Usually starts with the "assets/" directory, and has no trailing slash.
 *   Defaults to the "assets/" directory plus "Name" property if not set.
 *   Setting the "Filename" property will override the "Name" property.
 *   The value should be in sync with "ParentID".
 * - "Content": Typically unused, but handy for a textual representation of
 *   files, e.g. for fulltext indexing of PDF documents.
 * - "ParentID": Points to a {@link Folder} record. Should be in sync with
 *   "Filename". A ParentID=0 value points to the "assets/" folder, not the webroot.
 * 
 * <b>Synchronization</b>
 * 
 * Changes to a File database record can change the filesystem entry, 
 * but not the other way around. If the filesystem path is renamed outside
 * of SilverStripe, there's no way for the database to recover this linkage.
 * New physical files on the filesystem can be "discovered" via {@link Filesystem::sync()},
 * the equivalent {@link File} and {@link Folder} records are automatically 
 * created by this method.
 * 
 * Certain property changes within the File API that can cause a "delayed" filesystem change:
 * The change is enforced in {@link onBeforeWrite()} later on.
 * - setParentID()
 * - setFilename()
 * - setName()
 * It is recommended that you use {@link write()} directly after setting any of these properties,
 * otherwise getters like {@link getFullPath()} and {@link getRelativePath()}
 * will result paths that are inconsistent with the filesystem.
 * 
 * Caution: Calling {@link delete()} will also delete from the filesystem.
 * Call {@link deleteDatabaseOnly()} if you want to avoid this.
 * 
 * <b>Creating Files and Folders</b>
 * 
 * Typically both files and folders should be created first on the filesystem,
 * and then reflected in as database records. Folders can be created recursively
 * from sapphire both in the database and filesystem through {@link Folder::findOrMake()}.
 * Ensure that you always set a "Filename" property when writing to the database,
 * leaving it out can lead to unexpected results.
 * 
 * @package sapphire
 * @subpackage filesystem
 */
class File extends DataObject {

	static $default_sort = "\"Name\"";

	static $singular_name = "File";

	static $plural_name = "Files";

	static $db = array(
		"Name" => "Varchar(255)",
		"Title" => "Varchar(255)",
		"Filename" => "Text",
		"Content" => "Text",
		"Sort" => "Int"
	);
	
	static $has_one = array(
		"Parent" => "File",
		"Owner" => "Member"
	);
	
	static $has_many = array();
	
	static $many_many = array();
	
	static $belongs_many_many = array(
		"BackLinkTracking" => "SiteTree",
	);
	
	static $defaults = array();
	
	static $extensions = array(
		"Hierarchy",
	);
	
	/**
	 * @var array List of allowed file extensions, enforced through {@link validate()}.
	 * 
	 * Note: if you modify this, you should also change a configuration file in the assets directory.
	 * Otherwise, the files will be able to be uploaded but they won't be able to be served by the
	 * webserver.
	 * 
	 *  - If you are running Apahce you will need to change assets/.htaccess
	 *  - If you are running IIS you will need to change assets/web.config 
	 *
	 * Instructions for the change you need to make are included in a comment in the config file.
	 */
	public static $allowed_extensions = array(
		'','html','htm','xhtml','js','css',
		'bmp','png','gif','jpg','jpeg','ico','pcx','tif','tiff',
		'au','mid','midi','mpa','mp3','ogg','m4a','ra','wma','wav','cda',
		'avi','mpg','mpeg','asf','wmv','m4v','mov','mkv','mp4','swf','flv','ram','rm',
		'doc','docx','txt','rtf','xls','xlsx','pages',
		'ppt','pptx','pps','csv',
		'cab','arj','tar','zip','zipx','sit','sitx','gz','tgz','bz2','ace','arc','pkg','dmg','hqx','jar',
		'xml','pdf',
	);
	
	/**
	 * @var If this is true, then restrictions set in {@link $allowed_max_file_size} and
	 * {@link $allowed_extensions} will be applied to users with admin privileges as
	 * well.
	 */
	public static $apply_restrictions_to_admin = true;

	
	/**
	 * Cached result of a "SHOW FIELDS" call
	 * in instance_get() for performance reasons.
	 *
	 * @var array
	 */
	protected static $cache_file_fields = null;
	
	/**
	 * Find a File object by the given filename.
	 * 
	 * @param String $filename Matched against the "Name" property.
	 * @return mixed null if not found, File object of found file
	 */
	static function find($filename) {
		// Get the base file if $filename points to a resampled file
		$filename = ereg_replace('_resampled/[^-]+-','',$filename);

		// Split to folders and the actual filename, and traverse the structure.
		$parts = explode("/", $filename);
		$parentID = 0;
		$item = null;
		foreach($parts as $part) {
			if($part == ASSETS_DIR && !$parentID) continue;
			$SQL_part = Convert::raw2sql($part);
			$item = DataObject::get_one("File", "\"Name\" = '$SQL_part' AND \"ParentID\" = $parentID");
			if(!$item) break;
			$parentID = $item->ID;
		}
		
		return $item;
	}
	
	function Link($action = null) {
		return Director::baseURL() . $this->RelativeLink($action);
	}

	function RelativeLink($action = null){
		return $this->Filename;
	}

	function TreeTitle() {
		return $this->Title;
	}
	
	/**
	 * @todo Unnecessary shortcut for AssetTableField, coupled with cms module.
	 * 
	 * @return Integer
	 */
	function BackLinkTrackingCount() {
		$pages = $this->BackLinkTracking();
		if($pages) {
			return $pages->Count();
		} else {
			return 0;
		}
	}

	/**
	 * Event handler called before deleting from the database.
	 * You can overload this to clean up or otherwise process data before delete this
	 * record.  Don't forget to call {@link parent::onBeforeDelete()}, though!
	 */
	protected function onBeforeDelete() {
		parent::onBeforeDelete();

		// ensure that the record is synced with the filesystem before deleting
		$this->updateFilesystem();

		if($this->Filename && $this->Name && file_exists($this->getFullPath()) && !is_dir($this->getFullPath())) {
			unlink($this->getFullPath());
		}
	}
	
	/**
	 * Updates link tracking.
	 */
	protected function onAfterDelete() {
		parent::onAfterDelete();

		$brokenPages = $this->BackLinkTracking();
		if($brokenPages) {
			$origStage = Versioned::current_stage();

			// This will syncLinkTracking on draft
			Versioned::reading_stage('Stage');
			foreach($brokenPages as $brokenPage) $brokenPage->write();

			// This will syncLinkTracking on published
			Versioned::reading_stage('Live');
			foreach($brokenPages as $brokenPage) $brokenPage->write();

			Versioned::reading_stage($origStage);
		}
	}
	
	/**
	 * @todo Enforce on filesystem URL level via mod_rewrite
	 * 
	 * @return boolean
	 */
	function canView($member = null) {
		if(!$member) $member = Member::currentUser();
		
		$results = $this->extend('canView', $member);
		if($results && is_array($results)) if(!min($results)) return false;
		
		return true;
	}
	
	/**
	 * Returns true if the following conditions are met:
	 * - CMS_ACCESS_AssetAdmin
	 * 
	 * @todo Decouple from CMS view access
	 * 
	 * @return boolean
	 */
	function canEdit($member = null) {
		if(!$member) $member = Member::currentUser();
		
		$result = $this->extendedCan('canEdit', $member);
		if($result !== null) return $result;
		
		return Permission::checkMember($member, 'CMS_ACCESS_AssetAdmin');
	}
	
	/**
	 * @return boolean
	 */
	function canCreate($member = null) {
		if(!$member) $member = Member::currentUser();
		
		$result = $this->extendedCan('canCreate', $member);
		if($result !== null) return $result;
		
		return $this->canEdit($member);
	}
	
	/**
	 * @return boolean
	 */
	function canDelete($member = null) {
		if(!$member) $member = Member::currentUser();
		
		$results = $this->extend('canDelete', $member);
		if($results && is_array($results)) if(!min($results)) return false;
		
		return $this->canEdit($member);
	}
	
	/**
	 * Returns a category based on the file extension.
	 * This can be useful when grouping files by type,
	 * showing icons on filelinks, etc.
	 * Possible group values are: "audio", "mov", "zip", "image".
	 * 
	 * @return String
	 */
	public function appCategory() {
		$ext = $this->Extension;
		switch($ext) {
			case "aif": case "au": case "mid": case "midi": case "mp3": case "ra": case "ram": case "rm":
			case "mp3": case "wav": case "m4a": case "snd": case "aifc": case "aiff": case "wma": case "apl":
			case "avr": case "cda": case "mp4": case "ogg":
				return "audio";
			
			case "mpeg": case "mpg": case "m1v": case "mp2": case "mpa": case "mpe": case "ifo": case "vob":
			case "avi": case "wmv": case "asf": case "m2v": case "qt":
				return "mov";
			
			case "arc": case "rar": case "tar": case "gz": case "tgz": case "bz2": case "dmg": case "jar":
			case "ace": case "arj": case "bz": case "cab":
				return "zip";
				
			case "bmp": case "gif": case "jpg": case "jpeg": case "pcx": case "tif": case "png": case "alpha":
			case "als": case "cel": case "icon": case "ico": case "ps":
				return "image";
		}
	}

	function CMSThumbnail() {
		$filename = $this->Icon();
		return "<div style=\"text-align:center;width: 100px;padding-top: 15px;\"><a target=\"_blank\" href=\"$this->URL\" title=\"Download: $this->URL\"><img src=\"$filename\" alt=\"$filename\" /></a><br /><br /><a style=\"color: #0074C6;\"target=\"_blank\" href=\"$this->URL\" title=\"Download: $this->URL\">Download</a><br /><em>$this->Size</em></div>";
	}

	/**
	 * Return the relative URL of an icon for the file type,
	 * based on the {@link appCategory()} value.
	 * Images are searched for in "sapphire/images/app_icons/".
	 * 
	 * @return String 
	 */
	function Icon() {
		$ext = $this->Extension;
		if(!Director::fileExists(SAPPHIRE_DIR . "/images/app_icons/{$ext}_32.gif")) {
			$ext = $this->appCategory();
		}

		if(!Director::fileExists(SAPPHIRE_DIR . "/images/app_icons/{$ext}_32.gif")) {
			$ext = "generic";
		}

		return SAPPHIRE_DIR . "/images/app_icons/{$ext}_32.gif";
	}
	
	/**
	 * Should be called after the file was uploaded 
	 */ 
	function onAfterUpload() {
		$this->extend('onAfterUpload');
	}

	/**
	 * Delete the database record (recursively for folders) without touching the filesystem
	 */
	public function deleteDatabaseOnly() {
		if(is_numeric($this->ID)) DB::query("DELETE FROM \"File\" WHERE \"ID\" = $this->ID");
	}

	/**
	 * Make sure the file has a name
	 */
	protected function onBeforeWrite() {
		parent::onBeforeWrite();

		// Set default name
		if(!$this->getField('Name')) $this->Name = "new-" . strtolower($this->class);
	}

	/**
	 * Set name on filesystem. If the current object is a "Folder", will also update references
	 * to subfolders and contained file records (both in database and filesystem)
	 */
	protected function onAfterWrite() {
		parent::onAfterWrite();
		
		$this->updateFilesystem();
	}
	
	/**
	 * Moving the file if appropriate according to updated database content.
	 * Throws an Exception if the new file already exists.
	 * 
	 * Caution: This method should just be called during a {@link write()} invocation,
	 * as it relies on {@link DataObject->isChanged()}, which is reset after a {@link write()} call.
	 * Might be called as {@link File->updateFilesystem()} from within {@link Folder->updateFilesystem()},
	 * so it has to handle both files and folders.
	 * 
	 * Assumes that the "Filename" property was previously updated, either directly or indirectly.
	 * (it might have been influenced by {@link setName()} or {@link setParentID()} before).
	 */
	public function updateFilesystem() {
		// Regenerate "Filename", just to be sure
		$this->setField('Filename', $this->getRelativePath());
		
		// If certain elements are changed, update the filesystem reference
		if(!$this->isChanged('Filename')) return false;
		
		$changedFields = $this->getChangedFields();
		$pathBefore = $changedFields['Filename']['before'];
		$pathAfter = $changedFields['Filename']['after'];
		
		// If the file or folder didn't exist before, don't rename - its created
		if(!$pathBefore) return;
		
		$pathBeforeAbs = Director::getAbsFile($pathBefore);
		$pathAfterAbs = Director::getAbsFile($pathAfter);
		
		// TODO Fix Filetest->testCreateWithFilenameWithSubfolder() to enable this
		// // Create parent folders recursively in database and filesystem
		// if(!is_a($this, 'Folder')) {
		// 	$folder = Folder::findOrMake(dirname($pathAfterAbs));
		// 	if($folder) $this->ParentID = $folder->ID;
		// }
		
		// Check that original file or folder exists, and rename on filesystem if required.
		// The folder of the path might've already been renamed by Folder->updateFilesystem()
		// before any filesystem update on contained file or subfolder records is triggered.
		if(!file_exists($pathAfterAbs)) {
			if(!is_a($this, 'Folder')) {
				// Only throw a fatal error if *both* before and after paths don't exist.
				if(!file_exists($pathBeforeAbs)) throw new Exception("Cannot move $pathBefore to $pathAfter - $pathBefore doesn't exist");
				
				// Check that target directory (not the file itself) exists.
				// Only check if we're dealing with a file, otherwise the folder will need to be created
				if(!file_exists(dirname($pathAfterAbs))) throw new Exception("Cannot move $pathBefore to $pathAfter - Directory " . dirname($pathAfter) . " doesn't exist");
			} 
			
			// Rename file or folder
			$success = rename($pathBeforeAbs, $pathAfterAbs);
			if(!$success) throw new Exception("Cannot move $pathBeforeAbs to $pathAfterAbs");
		}
		
		
		// Update any database references
		$this->updateLinks($pathBefore, $pathAfter);
	}

	/**
	 * Collate selected descendants of this page.
	 * $condition will be evaluated on each descendant, and if it is succeeds, that item will be added
	 * to the $collator array.
	 * @param condition The PHP condition to be evaluated.  The page will be called $item
	 * @param collator An array, passed by reference, to collect all of the matching descendants.
	 */
	public function collateDescendants($condition, &$collator) {
		if($children = $this->Children()) {
			foreach($children as $item) {
				if(!$condition || eval("return $condition;")) $collator[] = $item;
				$item->collateDescendants($condition, $collator);
			}
			return true;
		}
	}

	/**
	 * Setter function for Name. Automatically sets a default title,
	 * and removes characters that might be invalid on the filesystem.
	 * Also adds a suffix to the name if the filename already exists
	 * on the filesystem, and is associated to a different {@link File} database record
	 * in the same folder. This means "myfile.jpg" might become "myfile-1.jpg".
	 * 
	 * Does not change the filesystem itself, please use {@link write()} for this.
	 * 
	 * @param String $name
	 */
	function setName($name) {
		$oldName = $this->Name;

		// It can't be blank, default to Title
		if(!$name) $name = $this->Title;

		// Fix illegal characters
		$name = ereg_replace(' +','-',trim($name)); // Replace any spaces
		$name = ereg_replace('[^A-Za-z0-9.+_\-]','',$name); // Replace non alphanumeric characters

		// Remove all leading dots or underscores
		while(!empty($name) && ($name[0] == '_' || $name[0] == '.')) {
			$name = substr($name, 1);
		}

		// We might have just turned it blank, so check again.
		if(!$name) $name = 'new-folder';

		// If it's changed, check for duplicates
		if($oldName && $oldName != $name) {
			$base = pathinfo($name, PATHINFO_BASENAME);
			$ext = self::get_file_extension($name);
			$suffix = 1;
			while(DataObject::get_one("File", "\"Name\" = '" . Convert::raw2sql($name) . "' AND \"ParentID\" = " . (int)$this->ParentID)) {
				$suffix++;
				$name = "$base-$suffix$ext";
			}
		}

		// Update title
		if(!$this->getField('Title')) $this->__set('Title', str_replace(array('-','_'),' ',ereg_replace('\.[^.]+$','',$name)));
		
		// Update actual field value
		$this->setField('Name', $name);
		
		// Ensure that the filename is updated as well (only in-memory)
		// Important: Circumvent the getter to avoid infinite loops
		$this->setField('Filename', $this->getRelativePath());
		
		return $this->getField('Name');
	}

	/**
	 * Rewrite links to the $old file to now point to the $new file.
	 * 
	 * @uses SiteTree->rewriteFileURL()
	 * 
	 * @param String $old File path relative to the webroot
	 * @param String $new File path relative to the webroot
	 */
	protected function updateLinks($old, $new) {
		if(class_exists('Subsite')) Subsite::disable_subsite_filter(true);
	
		$pages = $this->BackLinkTracking();

		$summary = "";
		if($pages) {
			foreach($pages as $page) $page->rewriteFileURL($old,$new);
		}
		
		if(class_exists('Subsite')) Subsite::disable_subsite_filter(false);
	}

	/**
	 * Does not change the filesystem itself, please use {@link write()} for this.
	 */
	function setParentID($parentID) {
		$this->setField('ParentID', $parentID);

		// Don't change on the filesystem, we'll handle that in onBeforeWrite()
		$this->setField('Filename', $this->getRelativePath()); 

		return $this->getField('ParentID');
	}

	/**
	 * Gets the absolute URL accessible through the web.
	 * 
	 * @uses Director::absoluteBaseURL()
	 * @return string
	 */
	function getAbsoluteURL() {
		return Director::absoluteBaseURL() . $this->getFilename();
	}
	
	/**
	 * Gets the relative URL accessible through the web.
	 * 
	 * @uses Director::baseURL()
	 * @return string
	 */
	function getURL() {
		return Director::baseURL() . $this->getFilename();
	}

	/**
	 * Return the last 50 characters of the URL.
	 * 
	 * @deprecated 2.4
	 */
	function getLinkedURL() {
		return "$this->Name";
	}

	/**
	 * Returns an absolute filesystem path to the file.
	 * Use {@link getRelativePath()} to get the same path relative to the webroot.
	 * 
	 * @return String 
	 */
	function getFullPath() {
		$baseFolder = Director::baseFolder();
		
		if(strpos($this->getFilename(), $baseFolder) === 0) {
			// if path is absolute already, just return
			return $this->getFilename();
		} else {
			// otherwise assume silverstripe-basefolder
			return Director::baseFolder() . '/' . $this->getFilename();
		}
	}

	/**
	 * Returns path relative to webroot.
	 * Serves as a "fallback" method to create the "Filename" property if it isn't set.
	 * If no {@link Folder} is set ("ParentID" property),
	 * defaults to a filename relative to the ASSETS_DIR (usually "assets/").
	 * 
	 * @return String
	 */
	function getRelativePath() {
		if($this->ParentID) {
			$p = DataObject::get_by_id('Folder', $this->ParentID, false); // Don't use the cache, the parent has just been changed
			if($p && $p->exists()) return $p->getRelativePath() . $this->getField("Name");
			else return ASSETS_DIR . "/" . $this->getField("Name");
		} else if($this->getField("Name")) {
			return ASSETS_DIR . "/" . $this->getField("Name");
		} else {
			return ASSETS_DIR;
		}
	}

	/**
	 * @todo Coupling with cms module, remove this method.
	 */
	function DeleteLink() {
		return Director::absoluteBaseURL()."admin/assets/removefile/".$this->ID;
	}

	function getFilename() {
		// Default behaviour: Return field if its set
		if($this->getField('Filename')) {
			return $this->getField('Filename');
		} else {
			return ASSETS_DIR . '/';
		}
	}

	/**
	 * Does not change the filesystem itself, please use {@link write()} for this.
	 */
	function setFilename($val) {
		$this->setField('Filename', $val);
		
		// "Filename" is the "master record" (existing on the filesystem), 
		// meaning we have to adjust the "Name" property in the database as well.
		$this->setField('Name', basename($val));
	}

	/**
	 * Returns the file extension
	 * 
	 * @todo This overrides getExtension() in DataObject, but it does something completely different.
	 * This should be renamed to getFileExtension(), but has not been yet as it may break
	 * legacy code.
	 * 
	 * @return String
	 */
	function getExtension() {
		return self::get_file_extension($this->getField('Filename'));
	}
	
	/**
	 * Gets the extension of a filepath or filename,
	 * by stripping away everything before the last "dot".
	 * Caution: Only returns the last extension in "double-barrelled"
	 * extensions (e.g. "gz" for "tar.gz").
	 * 
	 * Examples:
	 * - "myfile" returns ""
	 * - "myfile.txt" returns "txt"
	 * - "myfile.tar.gz" returns "gz"
	 *
	 * @param string $filename
	 * @return string
	 */
	public static function get_file_extension($filename) {
		return pathinfo($filename, PATHINFO_EXTENSION);
	}
	
	/**
	 * Return the type of file for the given extension
	 * on the current file name.
	 *
	 * @return string
	 */
	function getFileType() {
		$types = array(
			'gif' => 'GIF image - good for diagrams',
			'jpg' => 'JPEG image - good for photos',
			'jpeg' => 'JPEG image - good for photos',
			'png' => 'PNG image - good general-purpose format',
			'ico' => 'Icon image',
			'tiff' => 'Tagged image format',
			'doc' => 'Word document',
			'xls' => 'Excel spreadsheet',
			'zip' => 'ZIP compressed file',
			'gz' => 'GZIP compressed file',
			'dmg' => 'Apple disk image',
			'pdf' => 'Adobe Acrobat PDF file',
			'mp3' => 'MP3 audio file',
			'wav' => 'WAV audo file',
			'avi' => 'AVI video file',
			'mpg' => 'MPEG video file',
			'mpeg' => 'MPEG video file',
			'js' => 'Javascript file',
			'css' => 'CSS file',
			'html' => 'HTML file',
			'htm' => 'HTML file'
		);
		
		$ext = $this->getExtension();
		
		return isset($types[$ext]) ? $types[$ext] : 'unknown';
	}

	/**
	 * Returns the size of the file type in an appropriate format.
	 */
	function getSize() {
		$size = $this->getAbsoluteSize();
		
		return ($size) ? self::format_size($size) : false;
	}
	
	public static function format_size($size) {
		if($size < 1024) return $size . ' bytes';
		if($size < 1024*10) return (round($size/1024*10)/10). ' KB';
		if($size < 1024*1024) return round($size/1024) . ' KB';
		if($size < 1024*1024*10) return (round(($size/1024)/1024*10)/10) . ' MB';
		if($size < 1024*1024*1024) return round(($size/1024)/1024) . ' MB';
		return round($size/(1024*1024*1024)*10)/10 . ' GB';
	}

	/**
	 * Return file size in bytes.
	 * @return int
	 */
	function getAbsoluteSize(){
		if(file_exists($this->getFullPath())) {
			$size = filesize($this->getFullPath());
			return $size;
		} else {
			return 0;
		}
	}

	/**
	 * We've overridden the DataObject::get function for File so that the very large content field
	 * is excluded!
	 *
	 * @todo Admittedly this is a bit of a hack; but we need a way of ensuring that large
	 * TEXT fields don't stuff things up for the rest of us.  Perhaps a separate search table would
	 * be a better way of approaching this?
	 * @deprecated alternative_instance_get()
	 */
	public function instance_get($filter = "", $sort = "", $join = "", $limit="", $containerClass = "DataObjectSet", $having="") {
		$query = $this->extendedSQL($filter, $sort, $limit, $join, $having);
		$baseTable = reset($query->from);

		$excludeDbColumns = array('Content');
		
		// Work out which columns we're actually going to select
		// In short, we select everything except File.Content
		$dataobject_select = array();
		foreach($query->select as $item) {
			/*
			if($item == "\"File\".*") {
				$fileColumns = DB::query("SHOW FIELDS IN \"File\"")->column();
				$columnsToAdd = array_diff($fileColumns, $excludeDbColumns);
				foreach($columnsToAdd as $otherItem) $dataobject_select[] = '"File".' . $otherItem;
			} else {
			*/
				$dataobject_select[] = $item;
			//}
		}

		$query->select = $dataobject_select;

		$records = $query->execute();
		$ret = $this->buildDataObjectSet($records, $containerClass);
		if($ret) $ret->parseQueryLimit($query);
	
		return $ret;
	}
	
	public function flushCache() {
		parent::flushCache();
		
		self::$cache_file_fields = null;
	}
	
	/**
	 *
	 * @param boolean $includerelations a boolean value to indicate if the labels returned include relation fields
	 * 
	 */
	function fieldLabels($includerelations = true) {
		$labels = parent::fieldLabels($includerelations);
		$labels['Name'] = _t('File.Name', 'Name');
		$labels['Title'] = _t('File.Title', 'Title');
		$labels['Filename'] = _t('File.Filename', 'Filename');
		$labels['Filename'] = _t('File.Filename', 'Filename');
		$labels['Content'] = _t('File.Content', 'Content');
		$labels['Sort'] = _t('File.Sort', 'Sort Order');
		
		return $labels;
	}
	
	function validate() {
		if(File::$apply_restrictions_to_admin || !Permission::check('ADMIN')) {
			// Extension validation
			// TODO Merge this with Upload_Validator
			$extension = $this->getExtension();
			$allowed = array_map('strtolower', self::$allowed_extensions);
			if($extension && !in_array(strtolower($extension), $allowed)) {
				$exts =  $allowed;
				sort($exts);
				$message = sprintf(
					_t(
						'File.INVALIDEXTENSION', 
						'Extension is not allowed (valid: %s)',
						PR_MEDIUM,
						'Argument 1: Comma-separated list of valid extensions'
					),
					wordwrap(implode(', ',$exts))
				);
				return new ValidationResult(false, $message);
			}
		}
		
		// We aren't validating for an existing "Filename" on the filesystem.
		// A record should still be saveable even if the underlying record has been removed.
		
		return new ValidationResult(true);
	}

	/**
	 * Allow custom fields for uploads in {@link AssetAdmin}.
	 * Similar to {@link getCMSFields()}, but a more restricted
	 * set of fields which can be reliably set on any file type.
	 * 
	 * Needs to be enabled through {@link AssetAdmin::$metadata_upload_enabled}
	 * 
	 * @return FieldSet
	 */
	function uploadMetadataFields() {
		$fields = new FieldSet();
		$fields->push(new TextField('Title', $this->fieldLabel('Title')));
		$this->extend('updateUploadMetadataFields', $fields);
		
		return $fields;
	}
	
}

?>
