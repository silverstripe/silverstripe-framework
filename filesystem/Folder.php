<?php
/**
 * Represents a folder in the assets/ directory.
 * The folder path is stored in the "Filename" property.
 * 
 * Updating the "Name" or "Filename" properties on
 * a folder object also updates all associated children
 * (both {@link File} and {@link Folder} records).
 * 
 * Deleting a folder will also remove the folder from the filesystem,
 * including any subfolders and contained files. Use {@link deleteDatabaseOnly()}
 * to avoid touching the filesystem.
 * 
 * See {@link File} documentation for more details about the
 * relationship between the database and filesystem in the sapphire file APIs.
 * 
 * @package sapphire
 * @subpackage filesystem
 */
class Folder extends File {
	
	static $default_sort = "\"Sort\"";
	
	/**
	 * Find the given folder or create it both as {@link Folder} database records
	 * and on the filesystem. If necessary, creates parent folders as well.
	 * 
	 * @param $folderPath string Absolute or relative path to the file.
	 *  If path is relative, its interpreted relative to the "assets/" directory.
	 * @return Folder
	 */
	static function findOrMake($folderPath) {
		// Create assets directory, if it is missing
		if(!file_exists(ASSETS_PATH)) Filesystem::makeFolder(ASSETS_PATH);

		$folderPath = trim(Director::makeRelative($folderPath));
		// replace leading and trailing slashes
		$folderPath = preg_replace('/^\/?(.*)\/?$/', '$1', $folderPath);
		$parts = explode("/",$folderPath);

		$parentID = 0;
		$item = null;
		foreach($parts as $part) {
			if(!$part) continue; // happens for paths with a trailing slash
			$item = DataObject::get_one("Folder", "\"Name\" = '$part' AND \"ParentID\" = $parentID");
			if(!$item) {
				$item = new Folder();
				$item->ParentID = $parentID;
				$item->Name = $part;
				$item->Title = $part;
				$item->write();
			}
			if(!file_exists($item->getFullPath())) {
				Filesystem::makeFolder($item->getFullPath());
			}
			$parentID = $item->ID;
		}

		return $item;
	}
	
	/**
	 * Syncronise the file database with the actual content of the assets folder
	 */
	function syncChildren() {
		$parentID = (int)$this->ID; // parentID = 0 on the singleton, used as the 'root node';
		$added = 0;
		$deleted = 0;

		// First, merge any children that are duplicates
		$duplicateChildrenNames = DB::query("SELECT \"Name\" FROM \"File\" WHERE \"ParentID\" = $parentID GROUP BY \"Name\" HAVING count(*) > 1")->column();
		if($duplicateChildrenNames) foreach($duplicateChildrenNames as $childName) {
			$childName = DB::getConn()->addslashes($childName);
			// Note, we do this in the database rather than object-model; otherwise we get all sorts of problems about deleting files
			$children = DB::query("SELECT \"ID\" FROM \"File\" WHERE \"Name\" = '$childName' AND \"ParentID\" = $parentID")->column();
			if($children) {
				$keptChild = array_shift($children);
				foreach($children as $removedChild) {
					DB::query("UPDATE \"File\" SET \"ParentID\" = $keptChild WHERE \"ParentID\" = $removedChild");
					DB::query("DELETE FROM \"File\" WHERE \"ID\" = $removedChild");
				}
			} else {
				user_error("Inconsistent database issue: SELECT ID FROM \"File\" WHERE Name = '$childName' AND ParentID = $parentID should have returned data", E_USER_WARNING);
			}
		}

		
		// Get index of database content
		// We don't use DataObject so that things like subsites doesn't muck with this.
		$dbChildren = DB::query("SELECT * FROM \"File\" WHERE \"ParentID\" = $parentID");
		$hasDbChild = array();
		if($dbChildren) {
			foreach($dbChildren as $dbChild) {
				$className = $dbChild['ClassName'];
				if(!$className) $className = "File";
				$hasDbChild[$dbChild['Name']] = new $className($dbChild);
			}
		}
		$unwantedDbChildren = $hasDbChild;
		
		
		// Iterate through the actual children, correcting the database as necessary
		$baseDir = $this->FullPath;
		
		if(!$this->Filename) die($this->ID . " - " . $this->FullPath);
		

		if(file_exists($baseDir)) {
			$actualChildren = scandir($baseDir);
			foreach($actualChildren as $actualChild) {
				if($actualChild[0] == '.' || $actualChild[0] == '_' || substr($actualChild,0,6) == 'Thumbs' || $actualChild == 'web.config') {
					continue;
				}

				// A record with a bad class type doesn't deserve to exist. It must be purged!
				if(isset($hasDbChild[$actualChild])) {
					$child = $hasDbChild[$actualChild];
					if(( !( $child instanceof Folder ) && is_dir($baseDir . $actualChild) ) 
					|| (( $child instanceof Folder ) && !is_dir($baseDir . $actualChild)) ) {
						DB::query("DELETE FROM \"File\" WHERE \"ID\" = $child->ID");
						unset($hasDbChild[$actualChild]);						
					}
				}
				
				
				if(isset($hasDbChild[$actualChild])) {
					$child = $hasDbChild[$actualChild];
					unset($unwantedDbChildren[$actualChild]);
				} else {
					$added++;
					$childID = $this->constructChild($actualChild);
					$child = DataObject::get_by_id("File", $childID);
				}
					
				if( $child && is_dir($baseDir . $actualChild)) {
					$childResult = $child->syncChildren();
					$added += $childResult['added'];
					$deleted += $childResult['deleted'];
				}
				
				// Clean up the child record from memory after use. Important!
				$child->destroy();
				$child = null;
			}
			
			// Iterate through the unwanted children, removing them all
			if(isset($unwantedDbChildren)) foreach($unwantedDbChildren as $unwantedDbChild) {
				DB::query("DELETE FROM \"File\" WHERE \"ID\" = $unwantedDbChild->ID");
				$deleted++;
			}
		} else {
			DB::query("DELETE FROM \"File\" WHERE \"ID\" = $this->ID");
		}
		
		return array('added' => $added, 'deleted' => $deleted);
	}

	/**
	 * Construct a child of this Folder with the given name.
	 * It does this without actually using the object model, as this starts messing
	 * with all the data.  Rather, it does a direct database insert.
	 */
	function constructChild($name) {
		// Determine the class name - File, Folder or Image
		$baseDir = $this->FullPath;
		if(is_dir($baseDir . $name)) {
			$className = "Folder";
		} else {
			// Could use getimagesize to get the type of the image
			$ext = strtolower(substr($name,strrpos($name,'.')+1));
			switch($ext) {
				case "gif": case "jpg": case "jpeg": case "png": $className = "Image"; break;
				default: $className = "File";
			}
		}

		if(Member::currentUser()) $ownerID = Member::currentUser()->ID;
		else $ownerID = 0;
		
		$filename = DB::getConn()->addslashes($this->Filename . $name);
		if($className == 'Folder' ) $filename .= '/';

		$name = DB::getConn()->addslashes($name);
		
		DB::query("INSERT INTO \"File\" 
			(\"ClassName\", \"ParentID\", \"OwnerID\", \"Name\", \"Filename\", \"Created\", \"LastEdited\", \"Title\")
			VALUES ('$className', $this->ID, $ownerID, '$name', '$filename', " . DB::getConn()->now() . ',' . DB::getConn()->now() . ", '$name')");
			
		return DB::getGeneratedID("File");
	}

	/**
	 * Take a file uploaded via a POST form, and save it inside this folder.
	 */
	function addUploadToFolder($tmpFile) {
		if(!is_array($tmpFile)) {
			user_error("Folder::addUploadToFolder() Not passed an array.  Most likely, the form hasn't got the right enctype", E_USER_ERROR);
		}
		if(!isset($tmpFile['size'])) {
			return;
		}
		
		$base = BASE_PATH;
		// $parentFolder = Folder::findOrMake("Uploads");

		// Generate default filename
		$file = str_replace(' ', '-',$tmpFile['name']);
		$file = ereg_replace('[^A-Za-z0-9+.-]+','',$file);
		$file = ereg_replace('-+', '-',$file);

		while($file[0] == '_' || $file[0] == '.') {
			$file = substr($file, 1);
		}

		$file = $this->RelativePath . $file;
		Filesystem::makeFolder(dirname("$base/$file"));
		
		$doubleBarrelledExts = array('.gz', '.bz', '.bz2');
		
		$ext = "";
		if(preg_match('/^(.*)(\.[^.]+)$/', $file, $matches)) {
			$file = $matches[1];
			$ext = $matches[2];
			// Special case for double-barrelled 
			if(in_array($ext, $doubleBarrelledExts) && preg_match('/^(.*)(\.[^.]+)$/', $file, $matches)) {
				$file = $matches[1];
				$ext = $matches[2] . $ext;
			}
		}
		$origFile = $file;

		$i = 1;
		while(file_exists("$base/$file$ext")) {
			$i++;
			$oldFile = $file;
			
			if(strpos($file, '.') !== false) {
				$file = ereg_replace('[0-9]*(\.[^.]+$)', $i . '\\1', $file);
			} elseif(strpos($file, '_') !== false) {
				$file = ereg_replace('_([^_]+$)', '_' . $i, $file);
			} else {
				$file .= "_$i";
			}
			
			if($oldFile == $file && $i > 2) user_error("Couldn't fix $file$ext with $i", E_USER_ERROR);
		}
		
		if (move_uploaded_file($tmpFile['tmp_name'], "$base/$file$ext")) {
			// Update with the new image
			return $this->constructChild(basename($file . $ext));
		} else {
			if(!file_exists($tmpFile['tmp_name'])) user_error("Folder::addUploadToFolder: '$tmpFile[tmp_name]' doesn't exist", E_USER_ERROR);
			else user_error("Folder::addUploadToFolder: Couldn't copy '$tmpFile[tmp_name]' to '$base/$file$ext'", E_USER_ERROR);
			return false;
		}
	}
	
	function validate() {
		return new ValidationResult(true);
	}
	
	//-------------------------------------------------------------------------------------------------
	// Data Model Definition

	function getRelativePath() {
		return parent::getRelativePath() . "/";
	}
		
	function onBeforeDelete() {
		if($this->ID && ($children = $this->AllChildren())) {
			foreach($children as $child) {
				if(!$this->Filename || !$this->Name || !file_exists($this->getFullPath())) {
					$child->setField('Name',null);
					$child->Filename = null;
				}
				$child->delete();
			}
		}

		// Do this after so a folder's contents are removed before we delete the folder.
		if($this->Filename && $this->Name && file_exists($this->getFullPath())) {
			$files = glob( $this->getFullPath() . '/*' );
			
			if( !$files || ( count( $files ) == 1 && preg_match( '/\/_resampled$/', $files[0] ) ) )
				Filesystem::removeFolder( $this->getFullPath() );
		}
		
		parent::onBeforeDelete();
	}
	
	/**
	 * Delete the database record (recursively for folders) without touching the filesystem
	 */
	function deleteDatabaseOnly() {
		if($children = $this->myChildren()) {
			foreach($children as $child) $child->deleteDatabaseOnly();
		}

		parent::deleteDatabaseOnly();
	}
	
	public function myChildren() {
		// Ugly, but functional.
		$ancestors = ClassInfo::ancestry($this->class);
		foreach($ancestors as $i => $a) {
			if(isset($baseClass) && $baseClass === -1) {
				$baseClass = $a;
				break;
			}
			if($a == "DataObject") $baseClass = -1;
		}
		
		$g = DataObject::get($baseClass, "\"ParentID\" = " . $this->ID);
		return $g;
	}
	
	/**
	 * Returns true if this folder has children
	 */
	public function hasChildren() {
		return (bool)DB::query("SELECT COUNT(*) FROM \"File\" WHERE ParentID = "
			. (int)$this->ID)->value();
	}

	/**
	 * Returns true if this folder has children
	 */
	public function hasChildFolders() {
		$SQL_folderClasses = Convert::raw2sql(ClassInfo::subclassesFor('Folder'));
		
		return (bool)DB::query("SELECT COUNT(*) FROM \"File\" WHERE \"ParentID\" = " . (int)$this->ID
			. " AND \"ClassName\" IN ('" . implode("','", $SQL_folderClasses) . "')")->value();
	}
	
	/**
	 * Overloaded to call recursively on all contained {@link File} records.
	 */
	public function updateFilesystem() {
		parent::updateFilesystem();

		// Note: Folders will have been renamed on the filesystem already at this point,
		// File->updateFilesystem() needs to take this into account.
		if($this->ID && ($children = $this->AllChildren())) {
			foreach($children as $child) {
				$child->updateFilesystem();
				$child->write();
			}
		}
	}
	
	/**
	 * This isn't a decendant of SiteTree, but needs this in case
	 * the group is "reorganised";
	 */
	function cmsCleanup_parentChanged(){
		
	}

	/**
	 * Return the FieldSet used to edit this folder in the CMS.
	 * You can modify this fieldset by subclassing folder, or by creating a {@link DataObjectDecorator}
	 * and implemeting updateCMSFields(FieldSet $fields) on that decorator.	
	 */
	function getCMSFields() {
		$fileList = new AssetTableField(
			$this,
			"Files",
			"File", 
			array("Title" => _t('Folder.TITLE', "Title"), "Filename" => _t('Folder.FILENAME', "Filename")),
			""
		);
		$fileList->setFolder($this);
		$fileList->setPopupCaption(_t('Folder.VIEWEDITASSET', "View/Edit Asset"));

		$titleField = ($this->ID && $this->ID != "root") ? new TextField("Title", _t('Folder.TITLE')) : new HiddenField("Title");
		if( $this->canEdit() ) {
			$deleteButton = new InlineFormAction('deletemarked',_t('Folder.DELSELECTED','Delete selected files'), 'delete');
			$deleteButton->includeDefaultJS(false);
		} else {
			$deleteButton = new HiddenField('deletemarked');
		}

		$fields = new FieldSet(
			new HiddenField("Name"),
			new TabSet("Root", 
				new Tab("Files", _t('Folder.FILESTAB', "Files"),
					$titleField,
					$fileList,
					$deleteButton,
					new HiddenField("FileIDs"),
					new HiddenField("DestFolderID")
				),
				new Tab("Details", _t('Folder.DETAILSTAB', "Details"), 
					new ReadonlyField("URL", _t('Folder.URL', 'URL')),
					new ReadonlyField("ClassName", _t('Folder.TYPE','Type')),
					new ReadonlyField("Created", _t('Folder.CREATED','First Uploaded')),
					new ReadonlyField("LastEdited", _t('Folder.LASTEDITED','Last Updated'))
				),
				new Tab("Upload", _t('Folder.UPLOADTAB', "Upload"),
					new LiteralField("UploadIframe",
						$this->getUploadIframe()
					)
				)
				/* // commenting out unused files tab till bugs are fixed
				new Tab("UnusedFiles", _t('Folder.UNUSEDFILESTAB', "Unused files"),
					new Folder_UnusedAssetsField($this)
				) */
			),
			new HiddenField("ID")
		);
		
		if(!$this->canEdit()) {
			$fields->removeFieldFromTab("Root", "Upload");
		}

		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	
	/**
     * Looks for files used in system and create where clause which contains all ID's of files.
     * 
     * @returns String where clause which will work as filter.
     */
	public function getUnusedFilesListFilter() {
		$result = DB::query("SELECT DISTINCT \"FileID\" FROM \"SiteTree_ImageTracking\"");
		$usedFiles = array();
		$where = '';
		$classes = ClassInfo::subclassesFor('SiteTree');
		
		if($result->numRecords() > 0) {
			while($nextResult = $result->next()) {
				$where .= $nextResult['FileID'] . ','; 
			}
		}

		foreach($classes as $className) {
			$query = singleton($className)->extendedSQL();
			$ids = $query->execute()->column();
			if(!count($ids)) continue;
			
			foreach(singleton($className)->has_one() as $relName => $joinClass) {
				if($joinClass == 'Image' || $joinClass == 'File') {
					$fieldName = $relName .'ID';
					$query = singleton($className)->extendedSQL("$fieldName > 0");
					$query->distinct = true;
					$query->select = array($fieldName);
					$usedFiles = array_merge($usedFiles, $query->execute()->column());

				} elseif($joinClass == 'Folder') {
 					// @todo
				}
			}
		}
		
		if($usedFiles) {
 			return "\"File\".\"ID\" NOT IN (" . implode(', ', $usedFiles) . ") AND (\"ClassName\" = 'File' OR \"ClassName\" = 'Image')";

		} else {
			return "(\"ClassName\" = 'File' OR \"ClassName\" = 'Image')";
		}
		return $where;
	}

	/**
	 * Display the upload form.  Returns an iframe tag that will show admin/assets/uploadiframe.
	 */
	function getUploadIframe() {
		return <<<HTML
		<iframe name="AssetAdmin_upload" src="admin/assets/uploadiframe/{$this->ID}" id="AssetAdmin_upload" border="0" style="border-style none !important; width: 97%; min-height: 300px; height: 100%; height: expression(document.body.clientHeight) !important;">
		</iframe>
HTML;
	}
	
	/**
	 * Get the children of this folder that are also folders.
	 */
	function ChildFolders() {
		return DataObject::get("Folder", "\"ParentID\" = " . (int)$this->ID);
	}
}

/**
 * @package sapphire
 * @subpackage filesystem
 */
class Folder_UnusedAssetsField extends CompositeField {
	protected $folder;
	
	public function __construct($folder) {
		$this->folder = $folder;
		parent::__construct(new FieldSet());
	}
		
	public function getChildren() {
		if($this->children->Count() == 0) {
			$inlineFormAction = new InlineFormAction("delete_unused_thumbnails", _t('Folder.DELETEUNUSEDTHUMBNAILS', 'Delete unused thumbnails'));
			$inlineFormAction->includeDefaultJS(false) ;

			$this->children = new FieldSet(
				new LiteralField( "UnusedAssets", "<h2>"._t('Folder.UNUSEDFILESTITLE', 'Unused files')."</h2>" ),
				$this->getAssetList(),
				new FieldGroup(
					new LiteralField( "UnusedThumbnails", "<h2>"._t('Folder.UNUSEDTHUMBNAILSTITLE', 'Unused thumbnails')."</h2>"),
					$inlineFormAction
				)
			);
			$this->children->setForm($this->form);
		}
		return $this->children;
	}
	
	public function FieldHolder() {
		$output = "";
		foreach($this->getChildren() as $child) {
			$output .= $child->FieldHolder();
		}
		return $output;
	}


	/**
     * Creates table for displaying unused files.
     *
     * @returns AssetTableField
     */
	protected function getAssetList() {
		$where = $this->folder->getUnusedFilesListFilter();
        $assetList = new AssetTableField(
            $this->folder,
            "AssetList",
            "File", 
			array("Title" => _t('Folder.TITLE', "Title"), "LinkedURL" => _t('Folder.FILENAME', "Filename")), 
            "",
            $where
        );
		$assetList->setPopupCaption(_t('Folder.VIEWASSET', "View Asset"));
        $assetList->setPermissions(array("show","delete"));
        $assetList->Markable = false;
        return $assetList;
	}
}
?>
