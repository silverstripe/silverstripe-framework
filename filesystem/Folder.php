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
 * relationship between the database and filesystem in the SilverStripe file APIs.
 * 
 * @package framework
 * @subpackage filesystem
 */
class Folder extends File {

	static $singular_name = "Folder";

	static $plural_name = "Folders";

	static $default_sort = "\"Name\"";
	
	/**
	 * 
	 */
	public function populateDefaults() {
		parent::populateDefaults();
		
		if(!$this->Name) $this->Name = _t('AssetAdmin.NEWFOLDER',"NewFolder");
	}
	
	/**
	 * @param $folderPath string Absolute or relative path to the file.
	 *  If path is relative, its interpreted relative to the "assets/" directory.
	 * @return Folder
	 * @deprecated in favor of the correct name find_or_make
	 */
	public static function findOrMake($folderPath) {
		Deprecation::notice('3.0', "Use Folder::find_or_make() instead.");
		return self::find_or_make($folderPath);
	}
	
	/**
	 * Find the given folder or create it both as {@link Folder} database records
	 * and on the filesystem. If necessary, creates parent folders as well.
	 * 
	 * @param $folderPath string Absolute or relative path to the file.
	 *  If path is relative, its interpreted relative to the "assets/" directory.
	 * @return Folder
	 */
	public static function find_or_make($folderPath) {
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
			$item = DataObject::get_one(
				"Folder", 
				sprintf(
					"\"Name\" = '%s' AND \"ParentID\" = %d",
					Convert::raw2sql($part), 
					(int)$parentID
				)
			);
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
			$childName = Convert::raw2sql($childName);
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

		// if we're syncing a folder with no ID, we assume we're syncing the root assets folder
		// however the Filename field is populated with "NewFolder", so we need to set this to empty
		// to satisfy the baseDir variable below, which is the root folder to scan for new files in
		if(!$parentID) $this->Filename = '';

		// Iterate through the actual children, correcting the database as necessary
		$baseDir = $this->FullPath;

		// @todo this shouldn't call die() but log instead
		if($parentID && !$this->Filename) die($this->ID . " - " . $this->FullPath);

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
			$className = File::get_class_for_file_extension(pathinfo($name, PATHINFO_EXTENSION));
		}

		if(Member::currentUser()) $ownerID = Member::currentUser()->ID;
		else $ownerID = 0;
		
		$filename = Convert::raw2sql($this->Filename . $name);
		if($className == 'Folder' ) $filename .= '/';

		$name = Convert::raw2sql($name);
		
		DB::query("INSERT INTO \"File\" 
			(\"ClassName\", \"ParentID\", \"OwnerID\", \"Name\", \"Filename\", \"Created\", \"LastEdited\", \"Title\")
			VALUES ('$className', $this->ID, $ownerID, '$name', '$filename', " . DB::getConn()->now() . ',' . DB::getConn()->now() . ", '$name')");
			
		return DB::getGeneratedID("File");
	}

	/**
	 * Take a file uploaded via a POST form, and save it inside this folder.
	 * File names are filtered through {@link FileNameFilter}, see class documentation
	 * on how to influence this behaviour.
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
		$nameFilter = FileNameFilter::create();
		$file = $nameFilter->filter($tmpFile['name']);
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
				$file = preg_replace('/[0-9]*(\.[^.]+$)/', $i . '\\1', $file);
			} elseif(strpos($file, '_') !== false) {
				$file = preg_replace('/_([^_]+$)/', '_' . $i, $file);
			} else {
				$file .= '_'.$i;
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

	/** Override setting the Title of Folders to that Name, Filename and Title are always in sync.
	 * Note that this is not appropriate for files, because someone might want to create a human-readable name
	 * of a file that is different from its name on disk. But folders should always match their name on disk. */
	function setTitle($title) {
		$this->setField('Title',$title);
		parent::setName($title); //set the name and filename to match the title
	}

	function setName($name) {
		$this->setField('Title',$name);
		parent::setName($name);
	}

	function setFilename($filename) {
		$this->setField('Title',pathinfo($filename, PATHINFO_BASENAME));
		parent::setFilename($filename);
	}

	/**
	 * A folder doesn't have a (meaningful) file size.
	 * 
	 * @return Null
	 */
	function getSize() {
		return null;
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
	 * Return the FieldList used to edit this folder in the CMS.
	 * You can modify this FieldList by subclassing folder, or by creating a {@link DataExtension}
	 * and implemeting updateCMSFields(FieldList $fields) on that extension.
	 */
	function getCMSFields() {
		// Hide field on root level, which can't be renamed
		if(!$this->ID || $this->ID === "root") {
			$titleField = new HiddenField("Name");	
		} else {
			$titleField = new TextField("Name", $this->fieldLabel('Name'));
		}
		
		$fields = new FieldList(
			$titleField,
			new HiddenField('ParentID')
		);
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}

	/**
	 * Get the children of this folder that are also folders.
	 */
	function ChildFolders() {
		return DataObject::get("Folder", "\"ParentID\" = " . (int)$this->ID);
	}
	
	/**
	 * @return String
	 */
	function CMSTreeClasses() {
		$classes = sprintf('class-%s', $this->class);

		if(!$this->canDelete())
			$classes .= " nodelete";

		if(!$this->canEdit()) 
			$classes .= " disabled";
			
		$classes .= $this->markingClasses();

		return $classes;
	}
	
	/**
	 * @return string
	 */
	function getTreeTitle() {
		return $treeTitle = sprintf(
			"<span class=\"jstree-foldericon\"></span><span class=\"item\">%s</span>",
			Convert::raw2xml(str_replace(array("\n","\r"),"",$this->Title))
		);
	}
}
