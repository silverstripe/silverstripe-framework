<?php
/**
 * This class handles the representation of a File within Sapphire
 * Note: The files are stored in the "/assets/" directory, but sapphire
 * looks at the db object to gather information about a file such as URL
 *
 * It then uses this for all processing functions (like image manipulation)
 * @package sapphire
 * @subpackage filesystem
 */
class File extends DataObject {

	static $default_sort = "Name";

	static $singular_name = "File";

	static $plural_name = "Files";

	static $db = array(
		"Name" => "Varchar(255)",
		"Title" => "Varchar(255)",
		"Filename" => "Varchar(255)",
		"Content" => "Text",
		"Sort" => "Int"
	);
	
	static $indexes = array(
		"SearchFields" => "fulltext (Filename,Title,Content)",
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
	 * Cached result of a "SHOW FIELDS" call
	 * in instance_get() for performance reasons.
	 *
	 * @var array
	 */
	protected static $cache_file_fields = null;
	
	/**
	 * Find a File object by the given filename.
	 * @return mixed null if not found, File object of found file
	 */
	static function find($filename) {
		// Get the base file if $filename points to a resampled file
		$filename = ereg_replace('_resampled/[^-]+-','',$filename);

		$parts = explode("/", $filename);
		$parentID = 0;
		$item = null;

		foreach($parts as $part) {
			if($part == "assets" && !$parentID) continue;
			$SQL_part = Convert::raw2sql($part);
			$item = DataObject::get_one('File', "Name = '$SQL_part' AND ParentID = $parentID");
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
		return $this->Name;
	}

	/**
	 * Event handler called before deleting from the database.
	 * You can overload this to clean up or otherwise process data before delete this
	 * record.  Don't forget to call parent::onBeforeDelete(), though!
	 */
	protected function onBeforeDelete() {
		parent::onBeforeDelete();

		$this->autosetFilename();
		if($this->Filename && $this->Name && file_exists($this->getFullPath()) && !is_dir($this->getFullPath())) {
			unlink($this->getFullPath());
		}

		if($brokenPages = $this->BackLinkTracking()) {
			foreach($brokenPages as $brokenPage) {
				Notifications::event("BrokenLink", $brokenPage, $brokenPage->OwnerID);
				$brokenPage->HasBrokenFile = true;
				$brokenPage->write();
			}
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
		
		$results = $this->extend('canEdit', $member);
		if($results && is_array($results)) if(!min($results)) return false;
		
		return Permission::checkMember($member, 'CMS_ACCESS_AssetAdmin');
	}
	
	/**
	 * @return boolean
	 */
	function canCreate($member = null) {
		if(!$member) $member = Member::currentUser();
		
		$results = $this->extend('canCreate', $member);
		if($results && is_array($results)) if(!min($results)) return false;
		
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
		return "<div style=\"text-align:center;width: 100px;padding-top: 15px;\"><a target=\"_blank\" href=\"$this->URL\" title=\"Download: $this->URL\"><img src=\"$filename\" alt=\"$filename\" /></a><br /><br /><a style=\"color: #0074C6;\"target=\"_blank\" href=\"$this->URL\" title=\"Download: $this->URL\">Download</a><br /><em>$this->Size</e></div>";
	}

	/**
	 * Return the URL of an icon for the file type
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
	 * Save an file passed from a form post into this object.
	 * DEPRECATED Please instanciate an Upload-object instead and pass the file
	 * via {Upload->loadIntoFile()}.
	 * 
	 * @param $tmpFile array Indexed array that PHP generated for every file it uploads.
	 * @return Boolean|string Either success or error-message.
	 */
	function loadUploaded($tmpFile) {
		user_error('File::loadUploaded is deprecated, please use the Upload class directly.', E_USER_NOTICE);
		
		$upload = new Upload();
		$upload->loadIntoFile($tmpFile, $this);
		
		return $upload->isError();
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
		if(is_numeric($this->ID)) DB::query("DELETE FROM File WHERE ID = $this->ID");
	}

	/**
	 * Event handler called before deleting from the database.
	 * You can overload this to clean up or otherwise process data before delete this
	 * record.  Don't forget to call parent::onBeforeDelete(), though!
	 */
	protected function onBeforeWrite() {
		parent::onBeforeWrite();

		if(!$this->Name) $this->Name = "new-" . strtolower($this->class);

		if($brokenPages = $this->BackLinkTracking()) {
			foreach($brokenPages as $brokenPage) {
				Notifications::event("BrokenLink", $brokenPage, $brokenPage->OwnerID);
				$brokenPage->HasBrokenFile = true;
				$brokenPage->write();
			}
		}
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
	 * Setter function for Name.
	 * Automatically sets a default title.
	 */
	function setName($name) {
		$oldName = $this->Name;

		// It can't be blank
		if(!$name) $name = $this->Title;

		// Fix illegal characters
		$name = ereg_replace(' +','-',trim($name));
		$name = ereg_replace('[^A-Za-z0-9.+_\-]','',$name);

		// We might have just turned it blank, so check again.
		if(!$name) $name = 'new-folder';

		// If it's changed, check for duplicates
		if($oldName && $oldName != $name) {
			if($dotPos = strpos($name, '.')) {
				$base = substr($name,0,$dotPos);
				$ext = substr($name,$dotPos);
			} else {
				$base = $name;
				$ext = "";
			}
			$suffix = 1;
			while(DataObject::get_one("File", "Name = '" . addslashes($name) . "' AND ParentID = " . (int)$this->ParentID)) {
				$suffix++;
				$name = "$base-$suffix$ext";
			}
		}

		if(!$this->getField('Title')) $this->__set('Title', str_replace(array('-','_'),' ',ereg_replace('\.[^.]+$','',$name)));
		$this->setField('Name', $name);


		if($oldName && $oldName != $this->Name) {
			$this->resetFilename();
		} else {
			$this->autosetFilename();
		}


		return $this->getField('Name');
	}

	/**
	 * Change a filename, moving the file if appropriate.
	 * @param $renamePhysicalFile Set this to false if you don't want to rename the physical file. Used when calling resetFilename() on the children of a folder.
	 */
	protected function resetFilename($renamePhysicalFile = true) {
		$oldFilename = $this->getField('Filename');
		$newFilename = $this->getRelativePath();

		if($this->Name && $this->Filename && file_exists(Director::getAbsFile($oldFilename)) && strpos($oldFilename, '//') === false) {
			if($renamePhysicalFile) {
				$from = Director::getAbsFile($oldFilename);
				$to = Director::getAbsFile($newFilename);

				// Error checking
				if(!file_exists($from)) user_error("Cannot move $oldFilename to $newFilename - $oldFilename doesn't exist", E_USER_WARNING);
				else if(!file_exists(dirname($to))) user_error("Cannot move $oldFilename to $newFilename - " . dirname($newFilename) . " doesn't exist", E_USER_WARNING);
				else if(!rename($from, $to)) user_error("Cannot move $oldFilename to $newFilename", E_USER_WARNING);

				else $this->updateLinks($oldFilename, $newFilename);

			} else {
				$this->updateLinks($oldFilename, $newFilename);
			}
		} else {
			// If the old file doesn't exist, maybe it's already been renamed.
			if(file_exists(Director::getAbsFile($newFilename))) $this->updateLinks($oldFilename, $newFilename);
		}

		$this->setField('Filename', $newFilename);
	}

	/**
	 * Set the Filename field without manipulating the filesystem.
	 */
	protected function autosetFilename() {
		$this->setField('Filename', $this->getRelativePath());
	}

	function setField( $field, $value ) {
		parent::setField( $field, $value );
	}

	/**
	 * Rewrite links to the $old file to now point to the $new file
	 */
	protected function updateLinks($old, $new) {
		$pages = $this->BackLinkTracking();

		if($pages) {
			foreach($pages as $page) {
				$fieldName = $page->FieldName; // extracted from the many-many join
				if($fieldName) {
					$text = $page->$fieldName;
					$page->$fieldName = str_replace($old, $new, $page->$fieldName);
					$page->write();
				}
			}
		}
	}

	function setParentID($parentID) {
		$this->setField('ParentID', $parentID);

		if($this->Name) $this->resetFilename();
		else $this->autosetFilename();

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
	 * Gets the absolute URL accessible through the web.
	 * 
	 * @uses Director::absoluteBaseURL()
	 * @return string
	 */
	function getURL() {
		return Director::absoluteBaseURL() . $this->getFilename();
	}

	/**
	 * Return the last 50 characters of the URL
	 */
	function getLinkedURL() {
		return "$this->Name";
	}

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

	function getRelativePath() {

		if($this->ParentID) {
			$p = DataObject::get_one('Folder', "ID={$this->ParentID}");

			if($p && $p->ID) return $p->getRelativePath() . $this->getField("Name");
			else return ASSETS_DIR . "/" . $this->getField("Name");

		} else if($this->getField("Name")) {
			return ASSETS_DIR . "/" . $this->getField("Name");

		} else {
			return ASSETS_DIR;
		}
	}

	function DeleteLink() {
		return Director::absoluteBaseURL()."admin/assets/removefile/".$this->ID;
	}

	function getFilename() {
		if($this->getField('Filename')) {
			return $this->getField('Filename');
		} else {
			return ASSETS_DIR . '/';
		}
	}

	function setFilename($val) {
		$this->setField('Filename', $val);
		$this->setField('Name', basename($val));
	}

	/*
	 * FIXME This overrides getExtension() in DataObject, but it does something completely different.
	 * This should be renamed to getFileExtension(), but has not been yet as it may break
	 * legacy code.
	 */
	function getExtension() {
		return self::get_file_extension($this->getField('Filename'));
	}
	
	/**
	 * Gets the extension of a filepath or filename,
	 * by stripping away everything before the last "dot".
	 *
	 * @param string $filename
	 * @return string
	 */
	public static function get_file_extension($filename) {
		return strtolower(substr($filename,strrpos($filename,'.')+1));
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
			if($item == "`File`.*") {
				$fileColumns = DB::query("SHOW FIELDS IN `File`")->column();
				$columnsToAdd = array_diff($fileColumns, $excludeDbColumns);
				foreach($columnsToAdd as $otherItem) $dataobject_select[] = '`File`.' . $otherItem;
			} else {
				$dataobject_select[] = $item;
			}
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
	
}

?>