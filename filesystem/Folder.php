<?php

/**
 * @package sapphire
 * @subpackage filesystem
 */

/**
 * Represents a folder in the assets directory.
 * @package sapphire
 * @subpackage filesystem
 */
class Folder extends File {
	
	static $many_many = array(
		"CanUse" => "Group",
		"CanEdit" => "Group"
	);
	
	/*
	 * Find the given folder or create it, recursively
	 */
	static function findOrMake($folder) {
		$parts = explode("/",$folder);
		$parentID = 0;

		foreach($parts as $part) {
			$item = DataObject::get_one("Folder", "Name = '$part' AND ParentID = $parentID");
			if(!$item) {
				$item = new Folder();
				$item->ParentID = $parentID;
				$item->Name = $part;
				$item->Title = $part;
				$item->write();
				if(!file_exists($item->getFullPath())) mkdir($item->getFullPath(),Filesystem::$folder_create_mask);
			}
			$parentID = $item->ID;
		}
		return $item;
	}
// To Make SSP Gallery structure work	
//	$item->Title = $part;
	
	function userCanUse() {
		if( Member::currentUser()->_isAdmin() )
			return true;
		
		$useGroups = $this->CanUse();	
		
		if( !$useGroups || $useGroups->Count() == 0 )
			return true;
			
		foreach( $useGroups as $useGroup )
			if( Member::currentUser()->inGroup( $useGroup->ID ) )
				return true;
				
		return false;		
	}
	
	function userCanEdit() {
		if( Member::currentUser()->_isAdmin() )
			return true;
			
		$useGroups = $this->CanEdit();	
		
		if( !$useGroups || $useGroups->Count() == 0 )
			return true;
			
		foreach( $useGroups as $useGroup )
			if( Member::currentUser()->inGroup( $useGroup->ID ) )
				return true;
				
		return false;	
	}

	
	/**
	 * Syncronise the file database with the actual content of the assets folder
	 */
	function syncChildren() {
		$parentID = (int)$this->ID; // parentID = 0 on the singleton, used as the 'root node';
		$added = 0;
		$deleted = 0;

		// First, merge any children that are duplicates
		$duplicateChildrenNames = DB::query("SELECT Name FROM `File` WHERE ParentID = $parentID GROUP BY Name HAVING count(*) > 1")->column();
		if($duplicateChildrenNames) foreach($duplicateChildrenNames as $childName) {
			$childName = addslashes($childName);
			// Note, we do this in the database rather than object-model; otherwise we get all sorts of problems about deleting files
			$children = DB::query("SELECT ID FROM `File` WHERE Name = '$childName' AND ParentID = $parentID")->column();
			
			$keptChild = array_shift($children);
			foreach($children as $removedChild) {
				DB::query("UPDATE `File` SET ParentID = $keptChild WHERE ParentID = $removedChild");
				DB::query("DELETE FROM `File` WHERE ID = $removedChild");
			}
		}

		
		// Get index of database content
		// We don't use DataObject so that things like subsites doesn't muck with this.
		$dbChildren = DB::query("SELECT * FROM File WHERE ParentID = $parentID");
		$hasDbChild = array();
		if($dbChildren) {
			foreach($dbChildren as $dbChild) {
				$className = $dbChild['ClassName'];
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
				if($actualChild[0] == '.') continue; // ignore hidden files
				if(substr($actualChild,0,6) == 'Thumbs') continue; // ignore windows cache stuff
				if($actualChild == '_resampled') continue; // ignore the resampled copies of images
                if($actualChild == '_tmp') continue; // ignore tmp folder for PhotoEditor.
				
				
				// A record with a bad class type doesn't deserve to exist. It must be purged!
				if(isset($hasDbChild[$actualChild])) {
					$child = $hasDbChild[$actualChild];
					if( ($child->class != 'Folder' && is_dir($baseDir . $actualChild)) 
					|| ($child->class == 'Folder' && !is_dir($baseDir . $actualChild)) ) {
						DB::query("DELETE FROM `File` WHERE ID = $child->ID");
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
			}
			
			// Iterate through the unwanted children, removing them all
			if(isset($unwantedDbChildren)) foreach($unwantedDbChildren as $unwantedDbChild) {
				DB::query("DELETE FROM `File` WHERE ID = $unwantedDbChild->ID");
				$deleted++;
			}
		} else {
			DB::query("DELETE FROM `File` WHERE ID = $this->ID");
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
		
		$filename = addslashes($this->Filename . $name);
		if($className == 'Folder' ) $filename .= '/';

		$name = addslashes($name);
		
		DB::query("INSERT INTO `File` SET
			ClassName = '$className', ParentID = $this->ID, OwnerID = $ownerID,
			Name = '$name', Filename = '$filename', Created = NOW(), LastEdited = NOW(),
			Title = '$name'");
			
		return DB::getGeneratedID("File");
	}

	/**
	 * Take a file uploaded via a POST form, and save it inside this folder.
	 */
	function addUploadToFolder($tmpFile) {
		if(!is_array($tmpFile)) {
			user_error("File::loadUploaded() Not passed an array.  Most likely, the form hasn't got the right enctype", E_USER_ERROR);
		}
		
		if(!$tmpFile['size']) {
			return;
		}
		
		$base = dirname(dirname($_SERVER['SCRIPT_FILENAME']));
		// $parentFolder = Folder::findOrMake("Uploads");

		// Generate default filename
		$file = str_replace(' ', '-',$tmpFile['name']);
		$file = ereg_replace('[^A-Za-z0-9+.-]+','',$file);
		$file = ereg_replace('-+', '-',$file);

		$file = $this->RelativePath . $file;
		Filesystem::makeFolder(dirname("$base/$file"));
		
		while(file_exists("$base/$file")) {
			$i = isset($i) ? ($i+1) : 2;
			$oldFile = $file;
			$file = ereg_replace('[0-9]*(\.[^.]+$)',$i . '\\1', $file);
			if($oldFile == $file && $i > 2) user_error("Couldn't fix $file with $i", E_USER_ERROR);
		}
		
		if(file_exists($tmpFile['tmp_name']) && copy($tmpFile['tmp_name'], "$base/$file")) {
			// Update with the new image
			return $this->constructChild(basename($file));
		} else {
			user_error("File::loadUploaded: Couldn't copy '$tmpFile[tmp_name]' to '$file'", E_USER_ERROR);
			return false;
		}
	}
	

	//-------------------------------------------------------------------------------------------------
	// Data Model Definition

	function getRelativePath() {
		return parent::getRelativePath() . "/";
	}
	
	
	function onBeforeDelete() {
		if($children = $this->AllChildren()) {
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
		
		$g = DataObject::get($baseClass, "ParentID = " . $this->ID);
		return $g;
	}
	
	/**
	 * Returns true if this folder has children
	 */
	public function hasChildren() {
		return $this->myChildren() && $this->myChildren()->Count() > 0;	
	}
	
	/**
	 * Overload autosetFilename() to call autosetFilename() on all the children, too
	 */
	public function autosetFilename() {
		parent::autosetFilename();

		if($children = $this->AllChildren()) {
			$this->write();

			foreach($children as $child) {
				$child->autosetFilename();
				$child->write();
			}
		}
	}

	/**
	 * Overload resetFilename() to call resetFilename() on all the children, too.
	 * Pass renamePhysicalFile = false, since the folder renaming will have taken care of this
	 */
	protected function resetFilename($renamePhysicalFile = true) {
		parent::resetFilename($renamePhysicalFile);

		if($children = $this->AllChildren()) {
			$this->write();

			foreach($children as $child) {
				$child->resetFilename(false);
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
		$nameField = ($this->ID > 0) ? new TextField("Name") : new HiddenField("Name");

		$fileList = new AssetTableField(
			$this,
			"Files",
			"File", 
			array("Title" => _t('AssetAdmin.TITLE', "Title"), "LinkedURL" => _t('AssetAdmin.FILENAME', "Filename")), 
			""
		);
		$fileList->setFolder($this);
		$fileList->setPopupCaption(_t('AssetAdmin.VIEWEDITASSET', "View/Edit Asset"));

		$nameField = ($this->ID && $this->ID != "root") ? new TextField("Name", "Folder Name") : new HiddenField("Name");
		if( $this->userCanEdit() ) {
			$deleteButton = new InlineFormAction('deletemarked',_t('AssetAdmin.DELSELECTED','Delete selected files'), 'delete');
			$deleteButton->includeDefaultJS(false);
		} else {
			$deleteButton = new HiddenField('deletemarked');
		}

		$fields = new FieldSet(
			new HiddenField("Title"),
			new TabSet("Root", 
				new Tab(_t('AssetAdmin.FILESTAB', "Files"),
					$nameField,
					$fileList,
					$deleteButton,
					new HiddenField("FileIDs"),
					new HiddenField("DestFolderID")
				),
				new Tab(_t('AssetAdmin.DETAILSTAB', "Details"), 
					new ReadonlyField("URL", _t('AssetAdmin.URL', 'URL')),
					new ReadonlyField("ClassName", _t('AssetAdmin.TYPE','Type')),
					new ReadonlyField("Created", _t('AssetAdmin.CREATED','First Uploaded')),
					new ReadonlyField("LastEdited", _t('AssetAdmin.LASTEDITED','Last Updated'))
				),
				new Tab(_t('AssetAdmin.UPLOADTAB', "Upload"),
					new LiteralField("UploadIframe",
						$this->getUploadIframe()
					)
				)/* This has been disabled for now because of it's mass memory consumption
				,
				new Tab(_t('AssetAdmin.UNUSEDFILESTAB', "Unused files"),
				    new LiteralField("UnusedAssets",
	                    "<div id=\"UnusedAssets\"><h2>"._t('AssetAdmin.UNUSEDFILESTITLE', 'Unused files')."</h2>"
	                ),
				    $this->getAssetList(),
				    new LiteralField("UnusedThumbnails",
	                   "</div>
	                        <div id=\"UnusedThumbnails\">
	                            <h2>"._t('AssetAdmin.UNUSEDTHUMBNAILSTITLE', 'Unused thumbnails')."</h2>
	                            <button class=\"action\">"._t('AssetAdmin.DELETEUNUSEDTHUMBNAILS', 'Delete unused thumbnails')."</button>
	                        </div>"
	                )     
	            )*/
		    ),
			new HiddenField("ID")
		);

		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}

	/**
	 * Display the upload form.  Returns an iframe tag that will show admin/assets/uploadiframe.
	 */
	function getUploadIframe() {
		return <<<HTML
		<iframe name="AssetAdmin_upload" src="admin/assets/uploadiframe/{$this->ID}" id="AssetAdmin_upload" border="0" style="border-style: none; width: 100%; height: 200px">
		</iframe>
HTML;
	}
	/**
	 * Since this is a folder, we don't have any content as such.
	 */
	function loadContent() {
		return;
	}
	
}

?>
