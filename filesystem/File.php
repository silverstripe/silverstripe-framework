<?php
/**
 * This class handles the representation of a file on the filesystem within the framework.
 * Most of the methods also handle the {@link Folder} subclass.
 * 
 * Note: The files are stored in the assets/ directory, but SilverStripe
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
 *   Defaults to "Name". Note that the Title field of Folder (subclass of File)
 *   is linked to Name, so Name and Title will always be the same.
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
 * from SilverStripe both in the database and filesystem through {@link Folder::findOrMake()}.
 * Ensure that you always set a "Filename" property when writing to the database,
 * leaving it out can lead to unexpected results.
 * 
 * @package framework
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
		// Only applies to files, doesn't inherit for folder
		'ShowInSearch' => 'Boolean(1)',
	);
	
	static $has_one = array(
		"Parent" => "File",
		"Owner" => "Member"
	);
	
	static $has_many = array();
	
	static $many_many = array();
	
	static $defaults = array(
		"ShowInSearch" => 1,
	);
	
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
	 * @var array Category identifiers mapped to commonly used extensions.
	 */
	static $app_categories = array(
		'audio' => array(
			"aif" ,"au" ,"mid" ,"midi" ,"mp3" ,"ra" ,"ram" ,"rm","mp3" ,"wav" ,"m4a" ,"snd" ,"aifc" ,"aiff" ,"wma" ,"apl",
			"avr" ,"cda" ,"mp4" ,"ogg"
		),
		'mov' => array(
			"mpeg" ,"mpg" ,"m1v" ,"mp2" ,"mpa" ,"mpe" ,"ifo" ,"vob","avi" ,"wmv" ,"asf" ,"m2v" ,"qt"
		),
		'zip' => array(
			"arc" ,"rar" ,"tar" ,"gz" ,"tgz" ,"bz2" ,"dmg" ,"jar","ace" ,"arj" ,"bz" ,"cab"
		),
		'image' => array(
			"bmp" ,"gif" ,"jpg" ,"jpeg" ,"pcx" ,"tif" ,"png" ,"alpha","als" ,"cel" ,"icon" ,"ico" ,"ps"
		),
		'flash' => array(
			'swf', 'fla'
		)
	);

	/**
	 * @var If this is true, then restrictions set in {@link $allowed_max_file_size} and
	 * {@link $allowed_extensions} will be applied to users with admin privileges as
	 * well.
	 */
	public static $apply_restrictions_to_admin = true;

	public static $update_filesystem = true;

	/**
	 * Cached result of a "SHOW FIELDS" call
	 * in instance_get() for performance reasons.
	 *
	 * @var array
	 */
	protected static $cache_file_fields = null;

	/**
	 * Replace "[file_link id=n]" shortcode with an anchor tag or link to the file.
	 * @param $arguments array Arguments to the shortcode
	 * @param $content string Content of the returned link (optional)
	 * @param $parser object Specify a parser to parse the content (see {@link ShortCodeParser})
	 * @return string anchor HTML tag if content argument given, otherwise file path link
	 */
	public static function link_shortcode_handler($arguments, $content = null, $parser = null) {
		if(!isset($arguments['id']) || !is_numeric($arguments['id'])) return;

		$record = DataObject::get_by_id('File', $arguments['id']);

		if (!$record) {
			if(class_exists('ErrorPage')) {
				$record = DataObject::get_one('ErrorPage', '"ErrorCode" = \'404\'');
			}

			if (!$record) return; // There were no suitable matches at all.
		}

		// build the HTML tag
		if($content) {
			// build some useful meta-data (file type and size) as data attributes
			$attrs = ' ';
			if($record instanceof File) {
				foreach(array(
					'class' => 'file',
					'data-type' => $record->getExtension(),
					'data-size' => $record->getSize()
				) as $name => $value) {
					$attrs .= sprintf('%s="%s" ', $name, $value);
				}
			}

			return sprintf('<a href="%s"%s>%s</a>', $record->Link(), rtrim($attrs), $parser->parse($content));
		} else {
			return $record->Link();
		}
	}

	/**
	 * Find a File object by the given filename.
	 * 
	 * @param String $filename Matched against the "Name" property.
	 * @return mixed null if not found, File object of found file
	 */
	static function find($filename) {
		// Get the base file if $filename points to a resampled file
		$filename = preg_replace('/_resampled\/[^-]+-/', '', $filename);

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
	
	function Link() {
		return Director::baseURL() . $this->RelativeLink();
	}

	function RelativeLink() {
		return $this->Filename;
	}

	/**
	 * @deprecated 3.0 Use getTreeTitle()
	 */
	function TreeTitle() {
		Deprecation::notice('3.0', 'Use getTreeTitle() instead.');
		return $this->getTreeTitle();
	}

	/**
	 * @return string
	 */
	function getTreeTitle() {
		return Convert::raw2xml($this->Title);
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
	 * Returns the fields to power the edit screen of files in the CMS.
	 * You can modify this FieldList by subclassing folder, or by creating a {@link DataExtension}
	 * and implemeting updateCMSFields(FieldList $fields) on that extension.
	 * 
	 * @return FieldList
	 */
	function getCMSFields() {
		// Preview
		if($this instanceof Image) {
			$formattedImage = $this->getFormattedImage('SetWidth', Image::$asset_preview_width);
			$thumbnail = $formattedImage ? $formattedImage->URL : '';
			$previewField = new LiteralField("ImageFull",
				"<img id='thumbnailImage' class='thumbnail-preview' src='{$thumbnail}?r=" . rand(1,100000)  . "' alt='{$this->Name}' />\n"
			);
		} else {
			$previewField = new LiteralField("ImageFull", $this->CMSThumbnail());
		}

		// Upload
		$uploadField = new UploadField('UploadField','Upload Field');
		$uploadField->setConfig('previewMaxWidth', 40);
		$uploadField->setConfig('previewMaxHeight', 30);
		$uploadField->setConfig('allowedMaxFileNumber', 1);
		//$uploadField->setTemplate('FileEditUploadField');
		if ($this->ParentID) {
			$parent = $this->Parent();
			if ($parent) {  //set the parent that the Upload field should use for uploads
				$uploadField->setFolderName($parent->getFilename());
				$uploadField->setRecord($parent);
			}
		}

		//create the file attributes in a FieldGroup
		$filePreview = CompositeField::create(
			CompositeField::create(
				$previewField
			)->setName("FilePreviewImage")->addExtraClass('cms-file-info-preview'),
			CompositeField::create(
				CompositeField::create(
					new ReadonlyField("FileType", _t('AssetTableField.TYPE','File type') . ':'),
					new ReadonlyField("Size", _t('AssetTableField.SIZE','File size') . ':', $this->getSize()),
					$urlField = new ReadonlyField('ClickableURL', _t('AssetTableField.URL','URL'),
						sprintf('<a href="%s" target="_blank">%s</a>', $this->Link(), $this->RelativeLink())
					),
					new DateField_Disabled("Created", _t('AssetTableField.CREATED','First uploaded') . ':'),
					new DateField_Disabled("LastEdited", _t('AssetTableField.LASTEDIT','Last changed') . ':')
				)
			)->setName("FilePreviewData")->addExtraClass('cms-file-info-data')
		)->setName("FilePreview")->addExtraClass('cms-file-info');
		$urlField->dontEscape = true;

		//get a tree listing with only folder, no files
		$folderTree = new TreeDropdownField("ParentID", _t('AssetTableField.FOLDER','Folder'), 'Folder');
		$folderTree->setChildrenMethod('ChildFolders');

		$fields = new FieldList(
			new TabSet('Root',
				new Tab('Main',
					$filePreview,
					//TODO: make the uploadField replace the existing file
					// $uploadField,
					new TextField("Title", _t('AssetTableField.TITLE','Title')),
					new TextField("Name", _t('AssetTableField.FILENAME','Filename')),
					new DropdownField("OwnerID", _t('AssetTableField.OWNER','Owner'), Member::mapInCMSGroups()),
					$folderTree
				)
			)
		);

		// Folder has its own updateCMSFields hook
		if(!($this instanceof Folder)) $this->extend('updateCMSFields', $fields);

		return $fields;
	}

	/**
	 * Returns a category based on the file extension.
	 * This can be useful when grouping files by type,
	 * showing icons on filelinks, etc.
	 * Possible group values are: "audio", "mov", "zip", "image".
	 * 
	 * @return String
	 */
	public static function get_app_category($ext) {
		$ext = strtolower($ext);
		foreach(self::$app_categories as $category => $exts) {
			if(in_array($ext, $exts)) return $category;
		}
		return false;
	}
	
	/**
	 * Returns a category based on the file extension.
	 * 
	 * @return String
	 */
	public function appCategory() {
		return self::get_app_category($this->Extension);
	}

	function CMSThumbnail() {
		return '<img src="' . $this->Icon() . '" />';
	}

	/**
	 * Return the relative URL of an icon for the file type,
	 * based on the {@link appCategory()} value.
	 * Images are searched for in "framework/images/app_icons/".
	 * 
	 * @return String 
	 */
	function Icon() {
		$ext = $this->Extension;
		if(!Director::fileExists(FRAMEWORK_DIR . "/images/app_icons/{$ext}_32.gif")) {
			$ext = $this->appCategory();
		}

		if(!Director::fileExists(FRAMEWORK_DIR . "/images/app_icons/{$ext}_32.gif")) {
			$ext = "generic";
		}

		return FRAMEWORK_DIR . "/images/app_icons/{$ext}_32.gif";
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

		// Set default owner
		if(!$this->ID) $this->OwnerID = (Member::currentUser() ? Member::currentUser()->ID : 0);

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
		if(!self::$update_filesystem) return false;

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
		$filter = FileNameFilter::create();
		$name = $filter->filter($name);

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
		if(!$this->getField('Title')) $this->__set('Title', str_replace(array('-','_'),' ', preg_replace('/\.[^.]+$/', '', $name)));
		
		// Update actual field value
		$this->setField('Name', $name);
		
		// Ensure that the filename is updated as well (only in-memory)
		// Important: Circumvent the getter to avoid infinite loops
		$this->setField('Filename', $this->getRelativePath());
		
		return $this->getField('Name');
	}

	/**
	 * @param String $old File path relative to the webroot
	 * @param String $new File path relative to the webroot
	 */
	protected function updateLinks($old, $new) {
		$this->extend('updateLinks', $old, $new);
	}

	/**
	 * Does not change the filesystem itself, please use {@link write()} for this.
	 */
	function setParentID($parentID) {
		$this->setField('ParentID', (int)$parentID);

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
			'gif' => _t('File.GifType', 'GIF image - good for diagrams'),
			'jpg' => _t('File.JpgType', 'JPEG image - good for photos'),
			'jpeg' => _t('File.JpgType', 'JPEG image - good for photos'),
			'png' => _t('File.PngType', 'PNG image - good general-purpose format'),
			'ico' => _t('File.IcoType', 'Icon image'),
			'tiff' => _t('File.TiffType', 'Tagged image format'),
			'doc' => _t('File.DocType', 'Word document'),
			'xls' => _t('File.XlsType', 'Excel spreadsheet'),
			'zip' => _t('File.ZipType', 'ZIP compressed file'),
			'gz' => _t('File.GzType', 'GZIP compressed file'),
			'dmg' => _t('File.DmgType', 'Apple disk image'),
			'pdf' => _t('File.PdfType', 'Adobe Acrobat PDF file'),
			'mp3' => _t('File.Mp3Type', 'MP3 audio file'),
			'wav' => _t('File.WavType', 'WAV audo file'),
			'avi' => _t('File.AviType', 'AVI video file'),
			'mpg' => _t('File.MpgType', 'MPEG video file'),
			'mpeg' => _t('File.MpgType', 'MPEG video file'),
			'js' => _t('File.JsType', 'Javascript file'),
			'css' => _t('File.CssType', 'CSS file'),
			'html' => _t('File.HtmlType', 'HTML file'),
			'htm' => _t('File.HtlType', 'HTML file')
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
	
	/**
	 * Formats a file size (eg: (int)42 becomes string '42 bytes')
	 * @param int $size
	 * @return string
	 */
	public static function format_size($size) {
		if($size < 1024) return $size . ' bytes';
		if($size < 1024*10) return (round($size/1024*10)/10). ' KB';
		if($size < 1024*1024) return round($size/1024) . ' KB';
		if($size < 1024*1024*10) return (round(($size/1024)/1024*10)/10) . ' MB';
		if($size < 1024*1024*1024) return round(($size/1024)/1024) . ' MB';
		return round($size/(1024*1024*1024)*10)/10 . ' GB';
	}
	
	/**
	 * Convert a php.ini value (eg: 512M) to bytes
	 * 
	 * @param string $phpIniValue
	 * @return int
	 */
	public static function ini2bytes($PHPiniValue) {
		switch(strtolower(substr(trim($PHPiniValue), -1))) {
			case 'g':
				$PHPiniValue *= 1024;
			case 'm':
				$PHPiniValue *= 1024;
			case 'k':
				$PHPiniValue *= 1024;
		}
		return $PHPiniValue;
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
	
	public function flushCache($persistant = true) {
		parent::flushCache($persistant);
		
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
	 * @var Array Only use lowercase extensions in here.
	 */
	static $class_for_file_extension = array(
		'*' => 'File',
		'jpg' => 'Image',
		'jpeg' => 'Image',
		'png' => 'Image',
		'gif' => 'Image',
	);

	/**
	 * Maps a {@link File} subclass to a specific extension.
	 * By default, files with common image extensions will be created
	 * as {@link Image} instead of {@link File} when using 
	 * {@link Folder::constructChild}, {@link Folder::addUploadToFolder}),
	 * and the {@link Upload} class (either directly or through {@link FileField}).
	 * For manually instanciated files please use this mapping getter.
	 * 
	 * Caution: Changes to mapping doesn't apply to existing file records in the database.
	 * Also doesn't hook into {@link Object::getCustomClass()}.
	 * 
	 * @param String File extension, without dot prefix. Use an asterisk ('*')
	 * to specify a generic fallback if no mapping is found for an extension.
	 * @return String Classname for a subclass of {@link File}
	 */
	static function get_class_for_file_extension($ext) {
		$map = array_change_key_case(self::$class_for_file_extension, CASE_LOWER);
		return (array_key_exists(strtolower($ext), $map)) ? $map[strtolower($ext)] : $map['*'];
	}
	
	/**
	 * See {@link get_class_for_file_extension()}.
	 * 
	 * @param String|array
	 * @param String
	 */
	static function set_class_for_file_extension($exts, $class) {
		if(!is_array($exts)) $exts = array($exts);
		
		foreach($exts as $ext) {
			if(is_subclass_of($ext, 'File')) {
				throw new InvalidArgumentException(
					sprintf('Class "%s" (for extension "%s") is not a valid subclass of File', $class, $ext)
				);
			}
			self::$class_for_file_extension[$ext] = $class;
		}
	}
	
}
