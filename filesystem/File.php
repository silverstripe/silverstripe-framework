<?php

/**
 * @package sapphire
 * @subpackage filesystem
 */

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

	/**
	 * @var array Key is the extension, which has an array of MaxSize and WarnSize,
	 * e.g. array("jpg" => array("MaxSize"=>1000, "WarnSize=>500"))
	 */
	static $file_size_restrictions = array();

	/**
	 * @var array Collection of extensions, e.g. array("jpg","gif")
	 */
	static $allowed_file_types = array();

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
	static $extensions = array(
		"Hierarchy",
	);
	static $belongs_many_many = array(
		"BackLinkTracking" => "SiteTree",
	);


	/**
	 * Set the maximum
	 */
	static function setMaxFileSize( $maxSize, $warningSize, $extension = '*' ) {
		self::$file_size_restrictions[$extension]['MaxSize'] = $maxSize;
		self::$file_size_restrictions[$extension]['WarnSize'] = $warningSize;
	}
	
	static function getMaxFileSize($extension = '*') {
		if(!isset(self::$file_size_restrictions[$extension])) {
			if(isset(self::$file_size_restrictions['*'])) {
				$extension = '*';
			} else {
				return null;
			}	
		}

		return array( self::$file_size_restrictions[$extension]['MaxSize'], self::$file_size_restrictions[$extension]['WarnSize'] );
	}

	static function allowedFileType( $extension ) {
		return true;
	}

	/*
	 * Find the given file
	 */
	static function find($filename) {
		// Get the base file if $filename points to a resampled file
		$filename = ereg_replace('_resampled/[^-]+-','',$filename);

		$parts = explode("/",$filename);
		$parentID = 0;

		foreach($parts as $part) {
			if($part == "assets" && !$parentID) continue;
			$item = DataObject::get_one("File", "Name = '$part' AND ParentID = $parentID");
			if(!$item) break;
			$parentID = $item->ID;
		}
		return $item;
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
		if(!Director::fileExists("sapphire/images/app_icons/{$ext}_32.gif")) {
			/*switch($ext) {
				case "aif": case "au": case "mid": case "midi": case "mp3": case "ra": case "ram": case "rm":
				case "mp3": case "wav": case "m4a":
					$ext = "audio"; break;

				case "arc": case "rar": case "tar": case "gz": case "tgz": case "bz2": case "dmg":
					$ext = "zip"; break;

				case "bmp": case "gif": case "jpg": case "jpeg": case "pcx": case "tif": case "png":
					$ext = "image"; break;

			}*/

			$ext = $this->appCategory();
		}

		if(!Director::fileExists("sapphire/images/app_icons/{$ext}_32.gif")) {
			$ext = "generic";
		}

		return "sapphire/images/app_icons/{$ext}_32.gif";
	}

	/**
	 * Save an file passed from a form post into this object
	 */
	function loadUploaded($tmpFile, $folderName = 'Uploads') {
		if(!is_array($tmpFile)) user_error("File::loadUploaded() Not passed an array.  Most likely, the form hasn't got the right enctype", E_USER_ERROR);
		if(!$tmpFile['size']) return;
		
		
		// @TODO This puts a HUGE limitation on files especially when lots
		// have been uploaded.
		$base = dirname(dirname($_SERVER['SCRIPT_FILENAME']));
		$class = $this->class;
		$parentFolder = Folder::findOrMake($folderName);

		// Create a folder for uploading.
		if(!file_exists("$base/assets")){
			mkdir("$base/assets", Filesystem::$folder_create_mask);
		}
		if(!file_exists("$base/assets/$folderName")){
			mkdir("$base/assets/$folderName", Filesystem::$folder_create_mask);
		}

		// Generate default filename
		$file = str_replace(' ', '-',$tmpFile['name']);
		$file = ereg_replace('[^A-Za-z0-9+.-]+','',$file);
		$file = ereg_replace('-+', '-',$file);
		$file = basename($file);

		$file = "assets/$folderName/$file";

		while(file_exists("$base/$file")) {
			$i = isset($i) ? ($i+1) : 2;
			$oldFile = $file;
			if(substr($file, strlen($file) - strlen('.tar.gz')) == '.tar.gz' ||
				substr($file, strlen($file) - strlen('.tar.bz2')) == '.tar.bz2') {
					$file = ereg_replace('[0-9]*(\.tar\.[^.]+$)',$i . '\\1', $file);
			} else {
				$file = ereg_replace('[0-9]*(\.[^.]+$)',$i . '\\1', $file);
			}
			if($oldFile == $file && $i > 2) user_error("Couldn't fix $file with $i", E_USER_ERROR);
		}

		if(file_exists($tmpFile['tmp_name']) && copy($tmpFile['tmp_name'], "$base/$file")) {
			// Update with the new image
			/*$this->Filename = */ // $this->Name = null;
			// $this->Filename = $file;

			// This is to prevent it from trying to rename the file
			$this->record['Name'] = null;
			$this->ParentID = $parentFolder->ID;
			$this->Name = basename($file);
			$this->write();
			return true;
		} else {
			user_error("File::loadUploaded: Couldn't copy '$tmpFile[tmp_name]' to '$file'", E_USER_ERROR);
			return false;
		}
	}

	/**
	 * This function ensures the file table is correct with the files in the assets folder.
	 */
	static function sync() {
		singleton('Folder')->syncChildren();
		$finished = false;
		while(!$finished) {
			$orphans = DB::query("SELECT C.ID FROM File AS C LEFT JOIN File AS P ON C.ParentID = P.ID WHERE P.ID IS NULL AND C.ParentID > 0");
			$finished = true;
			if($orphans) foreach($orphans as $orphan) {
				$finished = false;
				// Delete the database record but leave the filesystem alone
				$file = DataObject::get_by_id("File", $orphan['ID']);
				$file->deleteDatabaseOnly();
			}
		}

	}

	/*
	 * Help to load the content of different type of files to File Table Content Field
	 */
	function test() {
		Debug::show(get_defined_functions());
	}

	function loadallcontent() {
		ini_set("max_execution_time", 50000);
		$allFiles = DataObject::get("File");
		$total = $allFiles->TotalItems();

		$i = 0;
		foreach($allFiles as $file) {
			$i++;
			$tmp = TEMP_FOLDER;
			`echo "$i / $total" > $tmp/progress`;
			$file->write();
		}
	}

	/**
	 * Gets the content of this file and puts it in the field Content
	 */
	function loadContent() {
		$filename = escapeshellarg($this->getFullPath());
		switch(strtolower($this->getExtension())){
			case 'pdf':
				$content = `pdftotext $filename -`;

				//echo("<pre>Content for $this->Filename:\n$content</pre>");
				$this->Content = $content;
				break;
			case 'doc':
				$content = `catdoc $filename`;
				$this->Content = $content;
				break;
			case 'ppt':
				$content = `catppt $filename`;
				$this->Content = $content;
				break;
			case 'txt';
				$content = file_get_contents($this->FileName);
				$this->Content = $content;
		}
	}

	function Link($action = null) {
		return Director::baseURL() . $this->RelativeLink($action);
	}

	function RelativeLink($action = null){
		return $this->Filename;
	}

	function TreeTitle() {
		if($this->hasMethod('alternateTreeTitle')) return $this->alternateTreeTitle();
		else return $this->Title;
	}

	/**
	 * Event handler called before deleting from the database.
	 * You can overload this to clean up or otherwise process data before delete this
	 * record.  Don't forget to call parent::onBeforeDelete(), though!
	 */
	protected function onBeforeDelete() {
		parent::onBeforeDelete();

		$this->autosetFilename();
		if($this->Filename && $this->Name && file_exists($this->getFullPath()) && !is_dir($this->getFullPath())) unlink($this->getFullPath());

		if($brokenPages = $this->BackLinkTracking()) {
			foreach($brokenPages as $brokenPage) {
				Notifications::event("BrokenLink", $brokenPage, $brokenPage->OwnerID);
				$brokenPage->HasBrokenFile = true;
				$brokenPage->write();
			}
		}
	}

	/**
	 * Delete the database record (recursively for folders) without touching the filesystem
	 */
	protected function deleteDatabaseOnly() {
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
		
		$this->loadContent();
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
		return Director::baseFolder() . '/' . $this->getFilename();
	}

	function getRelativePath() {

		if($this->ParentID) {
			$p = DataObject::get_one('Folder', "ID={$this->ParentID}");

			if($p->ID) return $p->getRelativePath() . $this->getField("Name");
			else return "assets/" . $this->getField("Name");

		} else if($this->getField("Name")) {
			return "assets/" . $this->getField("Name");

		} else {
			return "assets";
		}
	}

	function DeleteLink() {
		return Director::absoluteBaseURL()."admin/assets/removefile/".$this->ID;
	}

	function getFilename() {
		if($this->getField('Name')) return $this->getField('Filename');
		else return 'assets/';
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
		return strtolower(substr($this->getField('Filename'),strrpos($this->getField('Filename'),'.')+1));
	}
	function getFileType() {
		$types = array(
			'gif' => 'GIF Image - good for diagrams',
			'jpg' => 'JPEG Image - good for photos',
			'jpeg' => 'JPEG Image - good for photos',
			'png' => 'PNG Image - good general-purpose format',
			'doc' => 'Word Document',
			'xls' => 'Excel Spreadsheet',
			'zip' => 'ZIP compressed file',
			'gz' => 'GZIP compressed file',
			'dmg' => 'Apple Disk Image',
			'pdf' => 'Adobe Acrobat PDF file',
		);
		$ext = $this->getExtension();
		return isset($types[$ext]) ? $types[$ext] : 'unknown';
	}

	/**
	 * Returns the size of the file type in an appropriate format.
	 */
	function getSize() {
		$size = $this->getAbsoluteSize();
		if($size){
			if($size < 1024) return $size . ' bytes';
			if($size < 1024*10) return (round($size/1024*10)/10). ' KB';
			if($size < 1024*1024) return round($size/1024) . ' KB';
			if($size < 1024*1024*10) return (round(($size/1024)/1024*10)/10) . ' MB';
			if($size < 1024*1024*1024) return round(($size/1024)/1024) . ' MB';
		}
	}

	/**
	 * returns the size in bytes with no extensions for calculations.
	 */
	function getAbsoluteSize(){
		if(file_exists($this->getFullPath() )) {
			$size = filesize($this->getFullPath());
			return $size;
		}else{
			return 0;
		}
	}

	//--------------------------------------------------------------------------------------------------//
	// Helper control functions
	function moverootfilesto() {
		if($folder = $this->urlParams[ID]) {
			$newParent = Folder::findOrMake($folder);
			$files = DataObject::get("File", "ClassName != 'Folder' AND ParentID = 0");
			foreach($files as $file) {
				echo "<li>" , $file->RelativePath;
				$file->ParentID = $newParent->ID;
				echo " -> " , $file->RelativePath;
			}
		}
	}

	/**
	 * Cleanup function to reset all the Filename fields.  Visit File/fixfiles to call.
	 */
	function fixfiles() {
		$files = DataObject::get("File");
		foreach($files as $file) {
			$file->resetFilename();
			echo "<li>", $file->Filename;
			$file->write();
		}
		echo "<p>Done!";
	}


	/**
	 * Select clause for DataObject::get('File') operations/
	 * Stores an array, suitable for a {@link SQLQuery} object.
	 */
	private static $dataobject_select;

	/**
	 * We've overridden the DataObject::get function for File so that the very large content field
	 * is excluded!
	 *
	 * @todo Admittedly this is a bit of a hack; but we need a way of ensuring that large
	 * TEXT fields don't stuff things up for the rest of us.  Perhaps a separate search table would
	 * be a better way of approaching this?
	 */
	public function instance_get($filter = "", $sort = "", $join = "", $limit="", $containerClass = "DataObjectSet", $having="") {
		if($this->hasMethod('alternative_instance_get')) return $this->alternative_instance_get($filter, $sort, $join, $limit, $containerClass, $having);
		
		$query = $this->extendedSQL($filter, $sort, $limit, $join, $having);
		$baseTable = reset($query->from);

		// Work out which columns we're actually going to select
		// In short, we select everything except File.Content
		$dataobject_select = array();
		foreach($query->select as $item) {
			if($item == "`File`.*") {
				$fileColumns = DB::query("SHOW FIELDS IN `File`")->column();
				$columnsToAdd = array_diff($fileColumns, array('Content'));
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

	/**
	 * Stub, overridden by Folder
	 */
	function userCanEdit() {
		return false;
	}
}


?>
