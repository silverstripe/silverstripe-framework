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

	private static $singular_name = "Folder";

	private static $plural_name = "Folders";

	private static $default_sort = "\"Name\"";

	/**
	 *
	 */
	public function populateDefaults() {
		parent::populateDefaults();

		if(!$this->Name) $this->Name = _t('AssetAdmin.NEWFOLDER',"NewFolder");
	}

	/**
	 * Find the given folder or create it both as {@link Folder} database records
	 * and on the filesystem. If necessary, creates parent folders as well. If it's
	 * unable to find or make the folder, it will return null (as /assets is unable
	 * to be represented by a Folder DataObject)
	 *
	 * @param $folderPath string Absolute or relative path to the file.
	 *  If path is relative, its interpreted relative to the "assets/" directory.
	 * @return Folder|null
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
		$filter = FileNameFilter::create();
		foreach($parts as $part) {
			if(!$part) continue; // happens for paths with a trailing slash

			// Ensure search includes folders with illegal characters removed, but
			// err in favour of matching existing folders if $folderPath
			// includes illegal characters itself.
			$partSafe = $filter->filter($part);
			$item = Folder::get()->filter(array(
				'ParentID' => $parentID,
				'Name' => array($partSafe, $part)
			))->first();

			if(!$item) {
				$item = new Folder();
				$item->ParentID = $parentID;
				$item->Name = $partSafe;
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
	 * Synchronize the file database with the actual content of the assets
	 * folder.
	 */
	public function syncChildren() {
		$parentID = (int)$this->ID; // parentID = 0 on the singleton, used as the 'root node';
		$added = 0;
		$deleted = 0;
		$skipped = 0;

		// First, merge any children that are duplicates
		$duplicateChildrenNames = DB::prepared_query(
			'SELECT "Name" FROM "File" WHERE "ParentID" = ? GROUP BY "Name" HAVING count(*) > 1',
			array($parentID)
		)->column();
		if($duplicateChildrenNames) foreach($duplicateChildrenNames as $childName) {
			// Note, we do this in the database rather than object-model; otherwise we get all sorts of problems
			// about deleting files
			$children = DB::prepared_query(
				'SELECT "ID" FROM "File" WHERE "Name" = ? AND "ParentID" = ?',
				array($childName, $parentID)
			)->column();
			if($children) {
				$keptChild = array_shift($children);
				foreach($children as $removedChild) {
					DB::prepared_query('UPDATE "File" SET "ParentID" = ? WHERE "ParentID" = ?',
										array($keptChild, $removedChild));
					DB::prepared_query('DELETE FROM "File" WHERE "ID" = ?', array($removedChild));
				}
			} else {
				user_error("Inconsistent database issue: SELECT ID FROM \"File\" WHERE Name = '$childName'"
					. " AND ParentID = $parentID should have returned data", E_USER_WARNING);
			}
		}


		// Get index of database content
		// We don't use DataObject so that things like subsites doesn't muck with this.
		$dbChildren = DB::prepared_query('SELECT * FROM "File" WHERE "ParentID" = ?', array($parentID));
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
			$ignoreRules = Filesystem::config()->sync_blacklisted_patterns;
			$allowedExtensions = File::config()->allowed_extensions;
			$checkExtensions = $this->config()->apply_restrictions_to_admin || !Permission::check('ADMIN');

			foreach($actualChildren as $actualChild) {
				$skip = false;

				// Check ignore patterns
				if($ignoreRules) foreach($ignoreRules as $rule) {
					if(preg_match($rule, $actualChild)) {
						$skip = true;
						break;
					}
				}

				// Check allowed extensions, unless admin users are allowed to bypass these exclusions
				if($checkExtensions
					&& ($extension = self::get_file_extension($actualChild))
					&& !in_array(strtolower($extension), $allowedExtensions)
				) {
					$skip = true;
				}

				if($skip) {
					$skipped++;
					continue;
				}

				// A record with a bad class type doesn't deserve to exist. It must be purged!
				if(isset($hasDbChild[$actualChild])) {
					$child = $hasDbChild[$actualChild];
					if(( !( $child instanceof Folder ) && is_dir($baseDir . $actualChild) )
					|| (( $child instanceof Folder ) && !is_dir($baseDir . $actualChild)) ) {
						DB::prepared_query('DELETE FROM "File" WHERE "ID" = ?', array($child->ID));
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
					$skipped += $childResult['skipped'];
				}

				// Clean up the child record from memory after use. Important!
				$child->destroy();
				$child = null;
			}

			// Iterate through the unwanted children, removing them all
			if(isset($unwantedDbChildren)) foreach($unwantedDbChildren as $unwantedDbChild) {
				DB::prepared_query('DELETE FROM "File" WHERE "ID" = ?', array($unwantedDbChild->ID));
				$deleted++;
			}
		} else {
			DB::prepared_query('DELETE FROM "File" WHERE "ID" = ?', array($this->ID));
		}

		return array(
			'added' => $added,
			'deleted' => $deleted,
			'skipped' => $skipped
		);
	}

	/**
	 * Construct a child of this Folder with the given name.
	 * It does this without actually using the object model, as this starts messing
	 * with all the data.  Rather, it does a direct database insert.
	 *
	 * @param string $name Name of the file or folder
	 * @return integer the ID of the newly saved File record
	 */
	public function constructChild($name) {
		// Determine the class name - File, Folder or Image
		$baseDir = $this->FullPath;
		if(is_dir($baseDir . $name)) {
			$className = "Folder";
		} else {
			$className = File::get_class_for_file_extension(pathinfo($name, PATHINFO_EXTENSION));
		}

		$ownerID = Member::currentUserID();

		$filename = $this->Filename . $name;
		if($className == 'Folder' ) $filename .= '/';

		$nowExpression = DB::get_conn()->now();
		DB::prepared_query("INSERT INTO \"File\"
			(\"ClassName\", \"ParentID\", \"OwnerID\", \"Name\", \"Filename\", \"Created\", \"LastEdited\", \"Title\")
			VALUES (?, ?, ?, ?, ?, $nowExpression, $nowExpression, ?)",
			array($className, $this->ID, $ownerID, $name, $filename, $name)
		);

		return DB::get_generated_id("File");
	}

	/**
	 * Take a file uploaded via a POST form, and save it inside this folder.
	 * File names are filtered through {@link FileNameFilter}, see class documentation
	 * on how to influence this behaviour.
	 */
	public function addUploadToFolder($tmpFile) {
		if(!is_array($tmpFile)) {
			user_error("Folder::addUploadToFolder() Not passed an array."
				. " Most likely, the form hasn't got the right enctype", E_USER_ERROR);
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
			if(!file_exists($tmpFile['tmp_name'])) {
				user_error("Folder::addUploadToFolder: '$tmpFile[tmp_name]' doesn't exist", E_USER_ERROR);
			} else {
				user_error("Folder::addUploadToFolder: Couldn't copy '$tmpFile[tmp_name]' to '$base/$file$ext'",
					E_USER_ERROR);
			}
			return false;
		}
	}

	protected function validate() {
		return new ValidationResult(true);
	}

	//-------------------------------------------------------------------------------------------------
	// Data Model Definition

	public function getRelativePath() {
		return parent::getRelativePath() . "/";
	}

	public function onBeforeDelete() {
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
	public function setTitle($title) {
		$this->setName($title);
	}

	public function getTitle() {
		return $this->Name;
	}

	public function setName($name) {
		parent::setName($name);
		$this->setField('Title', $this->Name);
	}

	public function setFilename($filename) {
		$this->setField('Title',pathinfo($filename, PATHINFO_BASENAME));
		parent::setFilename($filename);
	}

	/**
	 * A folder doesn't have a (meaningful) file size.
	 *
	 * @return Null
	 */
	public function getSize() {
		return null;
	}

	/**
	 * Delete the database record (recursively for folders) without touching the filesystem
	 */
	public function deleteDatabaseOnly() {
		if($children = $this->myChildren()) {
			foreach($children as $child) $child->deleteDatabaseOnly();
		}

		parent::deleteDatabaseOnly();
	}

	/**
	 * Returns all children of this folder
	 *
	 * @return DataList
	 */
	public function myChildren() {
		return File::get()->filter("ParentID", $this->ID);
	}

	/**
	 * Returns true if this folder has children
	 *
	 * @return bool
	 */
	public function hasChildren() {
		return $this->myChildren()->exists();
	}

	/**
	 * Returns true if this folder has children
	 *
	 * @return bool
	 */
	public function hasChildFolders() {
		return $this->ChildFolders()->exists();
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
	public function getCMSFields() {
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
	 *
	 * @return DataList
	 */
	public function ChildFolders() {
		return Folder::get()->filter('ParentID', $this->ID);
	}

	/**
	 * Get the number of children of this folder that are also folders.
	 */
	public function numChildFolders() {
		return $this->ChildFolders()->count();
	}
	/**
	 * @return String
	 */
	public function CMSTreeClasses() {
		$classes = sprintf('class-%s', $this->class);

		if(!$this->canDelete())
			$classes .= " nodelete";

		if(!$this->canEdit())
			$classes .= " disabled";

		$classes .= $this->markingClasses('numChildFolders');

		return $classes;
	}

	/**
	 * @return string
	 */
	public function getTreeTitle() {
		return $treeTitle = sprintf(
			"<span class=\"jstree-foldericon\"></span><span class=\"item\">%s</span>",
			Convert::raw2xml(str_replace(array("\n","\r"),"",$this->Title))
		);
	}
}
