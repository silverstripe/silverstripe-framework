<?php

use SilverStripe\Filesystem\Thumbnail;
use SilverStripe\Filesystem\ImageManipulation;
use SilverStripe\Filesystem\Storage\AssetContainer;

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
 * Caution: It is recommended to disable any script execution in the"assets/"
 * directory in the webserver configuration, to reduce the risk of exploits.
 * See http://doc.silverstripe.org/secure-development#filesystem
 *
 * <b>Asset storage</b>
 *
 * As asset storage is configured separately to any File DataObject records, this class
 * does not make any assumptions about how these records are saved. They could be on
 * a local filesystem, remote filesystem, or a virtual record container (such as in local memory).
 *
 * The File dataobject simply represents an externally facing view of shared resources
 * within this asset store.
 *
 * Internally individual files are referenced by a"Filename" parameter, which represents a File, extension,
 * and is optionally prefixed by a list of custom directories. This path is root-agnostic, so it does not
 * automatically have a direct url mapping (even to the site's base directory).
 *
 * Additionally, individual files may have several versions distinguished by sha1 hash,
 * of which a File DataObject can point to a single one. Files can also be distinguished by
 * variants, which may be resized images or format-shifted documents.
 *
 * <b>Properties</b>
 *
 * -"Title": Optional title of the file (for display purposes only).
 *   Defaults to"Name". Note that the Title field of Folder (subclass of File)
 *   is linked to Name, so Name and Title will always be the same.
 * -"File": Physical asset backing this DB record. This is a composite DB field with
 *   its own list of properties. {@see DBFile} for more information
 * -"Content": Typically unused, but handy for a textual representation of
 *   files, e.g. for fulltext indexing of PDF documents.
 * -"ParentID": Points to a {@link Folder} record. Should be in sync with
 *  "Filename". A ParentID=0 value points to the"assets/" folder, not the webroot.
 * -"ShowInSearch": True if this file is searchable
 *
 * @package framework
 * @subpackage filesystem
 *
 * @property string $Name Basename of the file
 * @property string $Title Title of the file
 * @property DBFile $File asset stored behind this File record
 * @property string $Content
 * @property string $ShowInSearch Boolean that indicates if file is shown in search. Doesn't apply to Folders
 * @property int $ParentID ID of parent File/Folder
 * @property int $OwnerID ID of Member who owns the file
 *
 * @method File Parent() Returns parent File
 * @method Member Owner() Returns Member object of file owner.
 *
 * @mixin Hierarchy
 * @mixin Versioned
 */
class File extends DataObject implements ShortcodeHandler, AssetContainer, Thumbnail {

	use ImageManipulation;

	private static $default_sort = "\"Name\"";

	private static $singular_name = "File";

	private static $plural_name = "Files";

	/**
	 * Permissions necessary to view files outside of the live stage (e.g. archive / draft stage).
	 *
	 * @config
	 * @var array
	 */
	private static $non_live_permissions = array('CMS_ACCESS_LeftAndMain', 'CMS_ACCESS_AssetAdmin', 'VIEW_DRAFT_CONTENT');

	private static $db = array(
		"Name" =>"Varchar(255)",
		"Title" =>"Varchar(255)",
		"File" =>"DBFile",
		// Only applies to files, doesn't inherit for folder
		'ShowInSearch' => 'Boolean(1)',
	);

	private static $has_one = array(
		"Parent" => "File",
		"Owner" => "Member"
	);

	private static $defaults = array(
		"ShowInSearch" => 1,
	);

	private static $extensions = array(
		"Hierarchy",
		"Versioned"
	);

	private static $casting = array(
		'TreeTitle' => 'HTMLText'
	);

	/**
	 * @config
	 * @var array List of allowed file extensions, enforced through {@link validate()}.
	 *
	 * Note: if you modify this, you should also change a configuration file in the assets directory.
	 * Otherwise, the files will be able to be uploaded but they won't be able to be served by the
	 * webserver.
	 *
	 *  - If you are running Apache you will need to change assets/.htaccess
	 *  - If you are running IIS you will need to change assets/web.config
	 *
	 * Instructions for the change you need to make are included in a comment in the config file.
	 */
	private static $allowed_extensions = array(
		'', 'ace', 'arc', 'arj', 'asf', 'au', 'avi', 'bmp', 'bz2', 'cab', 'cda', 'css', 'csv', 'dmg', 'doc',
		'docx', 'dotx', 'dotm', 'flv', 'gif', 'gpx', 'gz', 'hqx', 'ico', 'jar', 'jpeg', 'jpg', 'js', 'kml',
		'm4a', 'm4v', 'mid', 'midi', 'mkv', 'mov', 'mp3', 'mp4', 'mpa', 'mpeg', 'mpg', 'ogg', 'ogv', 'pages',
		'pcx', 'pdf', 'png', 'pps', 'ppt', 'pptx', 'potx', 'potm', 'ra', 'ram', 'rm', 'rtf', 'sit', 'sitx',
		'tar', 'tgz', 'tif', 'tiff', 'txt', 'wav', 'webm', 'wma', 'wmv', 'xls', 'xlsx', 'xltx', 'xltm', 'zip',
		'zipx',
	);

	/**
	 * @config
	 * @var array Category identifiers mapped to commonly used extensions.
	 */
	private static $app_categories = array(
		'archive' => array(
			'ace', 'arc', 'arj', 'bz', 'bz2', 'cab', 'dmg', 'gz', 'hqx', 'jar', 'rar', 'sit', 'sitx', 'tar', 'tgz',
			'zip', 'zipx',
		),
		'audio' => array(
			'aif', 'aifc', 'aiff', 'apl', 'au', 'avr', 'cda', 'm4a', 'mid', 'midi', 'mp3', 'ogg', 'ra',
			'ram', 'rm', 'snd', 'wav', 'wma',
		),
		'document' => array(
			'css', 'csv', 'doc', 'docx', 'dotm', 'dotx', 'htm', 'html', 'gpx', 'js', 'kml', 'pages', 'pdf',
			'potm', 'potx', 'pps', 'ppt', 'pptx', 'rtf', 'txt', 'xhtml', 'xls', 'xlsx', 'xltm', 'xltx', 'xml',
		),
		'image' => array(
			'alpha', 'als', 'bmp', 'cel', 'gif', 'ico', 'icon', 'jpeg', 'jpg', 'pcx', 'png', 'ps', 'tif', 'tiff',
		),
		'image/supported' => array(
			'gif', 'jpeg', 'jpg', 'png'
		),
		'flash' => array(
			'fla', 'swf'
		),
		'video' => array(
			'asf', 'avi', 'flv', 'ifo', 'm1v', 'm2v', 'm4v', 'mkv', 'mov', 'mp2', 'mp4', 'mpa', 'mpe', 'mpeg',
			'mpg', 'ogv', 'qt', 'vob', 'webm', 'wmv',
		),
	);

	/**
	 * Map of file extensions to class type
	 *
	 * @config
	 * @var
	 */
	private static $class_for_file_extension = array(
		'*' => 'File',
		'jpg' => 'Image',
		'jpeg' => 'Image',
		'png' => 'Image',
		'gif' => 'Image',
	);

	/**
	 * @config
	 * @var If this is true, then restrictions set in {@link $allowed_max_file_size} and
	 * {@link $allowed_extensions} will be applied to users with admin privileges as
	 * well.
	 */
	private static $apply_restrictions_to_admin = true;

	/**
	 * If enabled, legacy file dataobjects will be automatically imported into the APL
	 *
	 * @config
	 * @var bool
	 */
	private static $migrate_legacy_file = false;

	/**
	 * @config
	 * @var boolean
	 */
	private static $update_filesystem = true;

	public static function get_shortcodes() {
		return 'file_link';
	}

	/**
	 * Replace"[file_link id=n]" shortcode with an anchor tag or link to the file.
	 *
	 * @param array $arguments Arguments passed to the parser
	 * @param string $content Raw shortcode
	 * @param ShortcodeParser $parser Parser
	 * @param string $shortcode Name of shortcode used to register this handler
	 * @param array $extra Extra arguments
	 * @return string Result of the handled shortcode
	 */
	public static function handle_shortcode($arguments, $content, $parser, $shortcode, $extra = array()) {
		// Find appropriate record, with fallback for error handlers
		$record = static::find_shortcode_record($arguments, $errorCode);
		if($errorCode) {
			$record = static::find_error_record($errorCode);
		}
		if (!$record) {
			return null; // There were no suitable matches at all.
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
	 * Find the record to use for a given shortcode.
	 *
	 * @param array $args Array of input shortcode arguments
	 * @param int $errorCode If the file is not found, or is inaccessible, this will be assigned to a HTTP error code.
	 * @return File|null The File DataObject, if it can be found.
	 */
	public static function find_shortcode_record($args, &$errorCode = null) {
		// Validate shortcode
		if(!isset($args['id']) || !is_numeric($args['id'])) {
			return null;
		}

		// Check if the file is found
		$file = File::get()->byID($args['id']);
		if (!$file) {
			$errorCode = 404;
			return null;
		}

		// Check if the file is viewable
		if(!$file->canView()) {
			$errorCode = 403;
			return null;
		}

		// Success
		return $file;
	}

	/**
	 * Given a HTTP Error, find an appropriate substitute File or SiteTree data object instance.
	 *
	 * @param int $errorCode HTTP Error value
	 * @return File|SiteTree File or SiteTree object to use for the given error
	 */
	protected static function find_error_record($errorCode) {
		$result = static::singleton()->invokeWithExtensions('getErrorRecordFor', $errorCode);
		$result = array_filter($result);
		if($result) {
			return reset($result);
		}
		return null;
	}

	/**
	 * A file only exists if the file_exists() and is in the DB as a record
	 *
	 * Use $file->isInDB() to only check for a DB record
	 * Use $file->File->exists() to only check if the asset exists
	 *
	 * @return bool
	 */
	public function exists() {
		return parent::exists() && $this->File->exists();
	}

	/**
	 * Find a File object by the given filename.
	 *
	 * @param string $filename Filename to search for, including any custom parent directories.
	 * @return File
	 */
	public static function find($filename) {
		// Split to folders and the actual filename, and traverse the structure.
		$parts = explode("/", $filename);
		$parentID = 0;
		$item = null;
		foreach($parts as $part) {
			$item = File::get()->filter(array(
				'Name' => $part,
				'ParentID' => $parentID
			))->first();
			if(!$item) break;
			$parentID = $item->ID;
		}

		return $item;
	}

	/**
	 * Just an alias function to keep a consistent API with SiteTree
	 *
	 * @return string The link to the file
	 */
	public function Link() {
		return $this->getURL();
	}

	/**
	 * @deprecated 4.0
	 */
	public function RelativeLink() {
		Deprecation::notice('4.0', 'Use getURL instead, as not all files will be relative to the site root.');
		return Director::makeRelative($this->getURL());
	}

	/**
	 * Just an alias function to keep a consistent API with SiteTree
	 *
	 * @return string The absolute link to the file
	 */
	public function AbsoluteLink() {
		return $this->getAbsoluteURL();
	}

	/**
	 * @return string
	 */
	public function getTreeTitle() {
		return Convert::raw2xml($this->Title);
	}

	/**
	 * @param Member $member
	 * @return bool
	 */
	public function canView($member = null) {
		if(!$member) {
			$member = Member::currentUser();
		}

		$result = $this->extendedCan('canView', $member);
		if($result !== null) {
			return $result;
		}

		return true;
	}

	/**
	 * Check if this file can be modified
	 *
	 * @param Member $member
	 * @return boolean
	 */
	public function canEdit($member = null) {
		if(!$member) {
			$member = Member::currentUser();
		}

		$result = $this->extendedCan('canEdit', $member);
		if($result !== null) {
			return $result;
		}

		return Permission::checkMember($member, array('CMS_ACCESS_AssetAdmin', 'CMS_ACCESS_LeftAndMain'));
	}

	/**
	 * Check if a file can be created
	 *
	 * @param Member $member
	 * @param array $context
	 * @return boolean
	 */
	public function canCreate($member = null, $context = array()) {
		if(!$member) {
			$member = Member::currentUser();
		}

		$result = $this->extendedCan('canCreate', $member, $context);
		if($result !== null) {
			return $result;
		}

		return $this->canEdit($member);
	}

	/**
	 * Check if this file can be deleted
	 *
	 * @param Member $member
	 * @return boolean
	 */
	public function canDelete($member = null) {
		if(!$member) {
			$member = Member::currentUser();
		}

		$result = $this->extendedCan('canDelete', $member);
		if($result !== null) {
			return $result;
		}

		return $this->canEdit($member);
	}

	/**
	 * Returns the fields to power the edit screen of files in the CMS.
	 * You can modify this FieldList by subclassing folder, or by creating a {@link DataExtension}
	 * and implemeting updateCMSFields(FieldList $fields) on that extension.
	 *
	 * @return FieldList
	 */
	public function getCMSFields() {
		// Preview
		$filePreview = CompositeField::create(
			CompositeField::create(new LiteralField("ImageFull", $this->PreviewThumbnail()))
				->setName("FilePreviewImage")
				->addExtraClass('cms-file-info-preview'),
			CompositeField::create(
				CompositeField::create(
					new ReadonlyField("FileType", _t('AssetTableField.TYPE','File type') . ':'),
					new ReadonlyField("Size", _t('AssetTableField.SIZE','File size') . ':', $this->getSize()),
					ReadonlyField::create(
						'ClickableURL',
						_t('AssetTableField.URL','URL'),
						sprintf('<a href="%s" target="_blank">%s</a>', $this->Link(), $this->Link())
					)
						->setDontEscape(true),
					new DateField_Disabled("Created", _t('AssetTableField.CREATED','First uploaded') . ':'),
					new DateField_Disabled("LastEdited", _t('AssetTableField.LASTEDIT','Last changed') . ':')
				)
			)
				->setName("FilePreviewData")
				->addExtraClass('cms-file-info-data')
		)
			->setName("FilePreview")
			->addExtraClass('cms-file-info');

		//get a tree listing with only folder, no files
		$fields = new FieldList(
			new TabSet('Root',
				new Tab('Main',
					$filePreview,
					new TextField("Title", _t('AssetTableField.TITLE','Title')),
					new TextField("Name", _t('AssetTableField.FILENAME','Filename')),
					DropdownField::create("OwnerID", _t('AssetTableField.OWNER','Owner'), Member::mapInCMSGroups())
						->setHasEmptyDefault(true),
					new TreeDropdownField("ParentID", _t('AssetTableField.FOLDER','Folder'), 'Folder')
				)
			)
		);

		$this->extend('updateCMSFields', $fields);
		return $fields;
	}

	/**
	 * Returns a category based on the file extension.
	 * This can be useful when grouping files by type,
	 * showing icons on filelinks, etc.
	 * Possible group values are:"audio","mov","zip","image".
	 *
	 * @param string $ext Extension to check
	 * @return string
	 */
	public static function get_app_category($ext) {
		$ext = strtolower($ext);
		foreach(Config::inst()->get('File', 'app_categories') as $category => $exts) {
			if(in_array($ext, $exts)) return $category;
		}
		return false;
	}

	/**
	 * For a category or list of categories, get the list of file extensions
	 *
	 * @param array|string $categories List of categories, or single category
	 * @return array
	 */
	public static function get_category_extensions($categories) {
		if(empty($categories)) {
			return array();
		}

		// Fix arguments into a single array
		if(!is_array($categories)) {
			$categories = array($categories);
		} elseif(count($categories) === 1 && is_array(reset($categories))) {
			$categories = reset($categories);
		}

		// Check configured categories
		$appCategories = self::config()->app_categories;

		// Merge all categories into list of extensions
		$extensions = array();
		foreach(array_filter($categories) as $category) {
			if(isset($appCategories[$category])) {
				$extensions = array_merge($extensions, $appCategories[$category]);
			} else {
				throw new InvalidArgumentException("Unknown file category: $category");
			}
		}
		$extensions = array_unique($extensions);
		sort($extensions);
		return $extensions;
	}

	/**
	 * Returns a category based on the file extension.
	 *
	 * @return string
	 */
	public function appCategory() {
		return self::get_app_category($this->getExtension());
	}


	/**
	 * Should be called after the file was uploaded
	 */
	public function onAfterUpload() {
		$this->extend('onAfterUpload');
	}

	/**
	 * Make sure the file has a name
	 */
	protected function onBeforeWrite() {
		// Set default owner
		if(!$this->isInDB() && !$this->OwnerID) {
			$this->OwnerID = Member::currentUserID();
		}

		// Set default name
		if(!$this->getField('Name')) {
			$this->Name ="new-" . strtolower($this->class);
		}

		// Propegate changes to the AssetStore and update the DBFile field
		$this->updateFilesystem();

		parent::onBeforeWrite();
	}

	/**
	 * This will check if the parent record and/or name do not match the name on the underlying
	 * DBFile record, and if so, copy this file to the new location, and update the record to
	 * point to this new file.
	 *
	 * This method will update the File {@see DBFile} field value on success, so it must be called
	 * before writing to the database
	 *
	 * @return bool True if changed
	 */
	public function updateFilesystem() {
		if(!$this->config()->update_filesystem) {
			return false;
		}

		// Check the file exists
		if(!$this->File->exists()) {
			return false;
		}

		// Avoid moving files on live; Rely on this being done on stage prior to publish.
		if(Versioned::get_stage() !== Versioned::DRAFT) {
			return false;
		}

		// Check path updated record will point to
		// If no changes necessary, skip
		$pathBefore = $this->File->getFilename();
		$pathAfter = $this->generateFilename();
		if($pathAfter === $pathBefore) {
			return false;
		}

		// Copy record to new location via stream
		$stream = $this->File->getStream();
		$this->File->setFromStream($stream, $pathAfter);
		return true;
	}

	/**
	 * Collate selected descendants of this page.
	 * $condition will be evaluated on each descendant, and if it is succeeds, that item will be added
	 * to the $collator array.
	 *
	 * @param string $condition The PHP condition to be evaluated.  The page will be called $item
	 * @param array $collator An array, passed by reference, to collect all of the matching descendants.
	 * @return true|null
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
	 * in the same folder. This means"myfile.jpg" might become"myfile-1.jpg".
	 *
	 * Does not change the filesystem itself, please use {@link write()} for this.
	 *
	 * @param string $name
	 * @return $this
	 */
	public function setName($name) {
		$oldName = $this->Name;

		// It can't be blank, default to Title
		if(!$name) {
			$name = $this->Title;
		}

		// Fix illegal characters
		$filter = FileNameFilter::create();
		$name = $filter->filter($name);

		// We might have just turned it blank, so check again.
		if(!$name) {
			$name = 'new-folder';
		}

		// If it's changed, check for duplicates
		if($oldName && $oldName != $name) {
			$base = pathinfo($name, PATHINFO_FILENAME);
			$ext = self::get_file_extension($name);
			$suffix = 1;

			while(File::get()->filter(array(
					'Name' => $name,
					'ParentID' => (int) $this->ParentID
				))->exclude(array(
					'ID' => $this->ID
				))->first()
			) {
				$suffix++;
				$name ="$base-$suffix.$ext";
			}
		}

		// Update actual field value
		$this->setField('Name', $name);

		// Update title
		if(!$this->Title) {
			$this->Title = str_replace(array('-','_'),' ', preg_replace('/\.[^.]+$/', '', $name));
		}

		return $this;
	}

	/**
	 * Gets the URL of this file
	 *
	 * @return string
	 */
	public function getAbsoluteURL() {
		$url = $this->getURL();
		if($url) {
			return Director::absoluteURL($url);
		}
	}

	/**
	 * Gets the URL of this file
	 *
	 * @uses Director::baseURL()
	 * @param bool $grant Ensures that the url for any protected assets is granted for the current user.
	 * @return string
	 */
	public function getURL($grant = true) {
		if($this->File->exists()) {
			return $this->File->getURL($grant);
		}
	}

	/**
	 * Get URL, but without resampling.
	 *
	 * @param bool $grant Ensures that the url for any protected assets is granted for the current user.
	 * @return string
	 */
	public function getSourceURL($grant = true) {
		if($this->File->exists()) {
			return $this->File->getSourceURL($grant);
		}
	}

	/**
	 * @todo Coupling with cms module, remove this method.
	 *
	 * @return string
	 */
	public function DeleteLink() {
		return Director::absoluteBaseURL()."admin/assets/removefile/".$this->ID;
	}

	/**
	 * Get expected value of Filename tuple value. Will be used to trigger
	 * a file move on draft stage.
	 *
	 * @return string
	 */
	public function generateFilename() {
		// Check if this file is nested within a folder
		$parent = $this->Parent();
		if($parent && $parent->exists()) {
			return $this->join_paths($parent->getFilename(), $this->Name);
		}
		return $this->Name;
	}

	/**
	 * Ensure that parent folders are published before this one is published
	 *
	 * @todo Solve this via triggered publishing / ownership in the future
	 */
	public function onBeforePublish() {
		// Relies on Parent() returning the stage record
		$parent = $this->Parent();
		if($parent && $parent->exists()) {
			$parent->publishRecursive();
		}
	}

	/**
	 * Update the ParentID and Name for the given filename.
	 *
	 * On save, the underlying DBFile record will move the underlying file to this location.
	 * Thus it will not update the underlying Filename value until this is done.
	 *
	 * @param string $filename
	 * @return $this
	 */
	public function setFilename($filename) {
		// Check existing folder path
		$folder = '';
		$parent = $this->Parent();
		if($parent && $parent->exists()) {
			$folder = $parent->Filename;
		}

		// Detect change in foldername
		$newFolder = ltrim(dirname(trim($filename, '/')), '.');
		if($folder !== $newFolder) {
			if(!$newFolder) {
				$this->ParentID = 0;
			} else {
				$parent = Folder::find_or_make($newFolder);
				$this->ParentID = $parent->ID;
			}
		}

		// Update base name
		$this->Name = basename($filename);
		return $this;
	}

	/**
	 * Returns the file extension
	 *
	 * @return string
	 */
	public function getExtension() {
		return self::get_file_extension($this->Name);
	}

	/**
	 * Gets the extension of a filepath or filename,
	 * by stripping away everything before the last"dot".
	 * Caution: Only returns the last extension in"double-barrelled"
	 * extensions (e.g."gz" for"tar.gz").
	 *
	 * Examples:
	 * -"myfile" returns""
	 * -"myfile.txt" returns"txt"
	 * -"myfile.tar.gz" returns"gz"
	 *
	 * @param string $filename
	 * @return string
	 */
	public static function get_file_extension($filename) {
		return pathinfo($filename, PATHINFO_EXTENSION);
	}

	/**
	 * Given an extension, determine the icon that should be used
	 *
	 * @param string $extension
	 * @return string Icon filename relative to base url
	 */
	public static function get_icon_for_extension($extension) {
		$extension = strtolower($extension);

		// Check if exact extension has an icon
		if(!file_exists(FRAMEWORK_PATH ."/images/app_icons/{$extension}_32.gif")) {
			$extension = static::get_app_category($extension);

			// Fallback to category specific icon
			if(!file_exists(FRAMEWORK_PATH ."/images/app_icons/{$extension}_32.gif")) {
				$extension ="generic";
			}
		}

		return FRAMEWORK_DIR ."/images/app_icons/{$extension}_32.gif";
	}

	/**
	 * Return the type of file for the given extension
	 * on the current file name.
	 *
	 * @return string
	 */
	public function getFileType() {
		return self::get_file_type($this->getFilename());
	}

	/**
	 * Get descriptive type of file based on filename
	 *
	 * @param string $filename
	 * @return string Description of file
	 */
	public static function get_file_type($filename) {
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
			'htm' => _t('File.HtmlType', 'HTML file')
		);

		// Get extension
		$extension = strtolower(self::get_file_extension($filename));
		return isset($types[$extension]) ? $types[$extension] : 'unknown';
	}

	/**
	 * Returns the size of the file type in an appropriate format.
	 *
	 * @return string|false String value, or false if doesn't exist
	 */
	public function getSize() {
		$size = $this->getAbsoluteSize();
		if($size) {
			return static::format_size($size);
		}
		return false;
	}

	/**
	 * Formats a file size (eg: (int)42 becomes string '42 bytes')
	 *
	 * @todo unit tests
	 *
	 * @param int $size
	 * @return string
	 */
	public static function format_size($size) {
		if($size < 1024) {
			return $size . ' bytes';
		}
		if($size < 1024*10) {
			return (round($size/1024*10)/10). ' KB';
		}
		if($size < 1024*1024) {
			return round($size/1024) . ' KB';
		}
		if($size < 1024*1024*10) {
			return (round(($size/1024)/1024*10)/10) . ' MB';
		}
		if($size < 1024*1024*1024) {
			return round(($size/1024)/1024) . ' MB';
		}
		return round($size/(1024*1024*1024)*10)/10 . ' GB';
	}

	/**
	 * Convert a php.ini value (eg: 512M) to bytes
	 *
	 * @todo unit tests
	 *
	 * @param string $iniValue
	 * @return int
	 */
	public static function ini2bytes($iniValue) {
		switch(strtolower(substr(trim($iniValue), -1))) {
			case 'g':
				$iniValue *= 1024;
			case 'm':
				$iniValue *= 1024;
			case 'k':
				$iniValue *= 1024;
		}
		return $iniValue;
	}

	/**
	 * Return file size in bytes.
	 *
	 * @return int
	 */
	public function getAbsoluteSize(){
		return $this->File->getAbsoluteSize();
	}

	public function validate() {
		$result = new ValidationResult();
		$this->File->validate($result, $this->Name);
		$this->extend('validate', $result);
		return $result;
	}

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
	public static function get_class_for_file_extension($ext) {
		$map = array_change_key_case(self::config()->class_for_file_extension, CASE_LOWER);
		return (array_key_exists(strtolower($ext), $map)) ? $map[strtolower($ext)] : $map['*'];
	}

	/**
	 * See {@link get_class_for_file_extension()}.
	 *
	 * @param String|array
	 * @param String
	 */
	public static function set_class_for_file_extension($exts, $class) {
		if(!is_array($exts)) $exts = array($exts);
		foreach($exts as $ext) {
			if(!is_subclass_of($class, 'File')) {
				throw new InvalidArgumentException(
					sprintf('Class"%s" (for extension"%s") is not a valid subclass of File', $class, $ext)
				);
			}
			self::config()->class_for_file_extension = array($ext => $class);
		}
	}

	public function getMetaData() {
		if($this->File->exists()) {
			return $this->File->getMetaData();
		}
	}

	public function getMimeType() {
		if($this->File->exists()) {
			return $this->File->getMimeType();
		}
	}

	public function getStream() {
		if($this->File->exists()) {
			return $this->File->getStream();
		}
	}

	public function getString() {
		if($this->File->exists()) {
			return $this->File->getString();
		}
	}

	public function setFromLocalFile($path, $filename = null, $hash = null, $variant = null, $config = array()) {
		$result = $this->File->setFromLocalFile($path, $filename, $hash, $variant, $config);

		// Update File record to name of the uploaded asset
		if($result) {
			$this->setFilename($result['Filename']);
		}
		return $result;
	}

	public function setFromStream($stream, $filename, $hash = null, $variant = null, $config = array()) {
		$result = $this->File->setFromStream($stream, $filename, $hash, $variant, $config);

		// Update File record to name of the uploaded asset
		if($result) {
			$this->setFilename($result['Filename']);
		}
		return $result;
	}

	public function setFromString($data, $filename, $hash = null, $variant = null, $config = array()) {
		$result = $this->File->setFromString($data, $filename, $hash, $variant, $config);

		// Update File record to name of the uploaded asset
		if($result) {
			$this->setFilename($result['Filename']);
		}
		return $result;
	}

	public function getIsImage() {
		return false;
	}

	public function getFilename() {
		return $this->File->Filename;
	}

	public function getHash() {
		return $this->File->Hash;
	}

	public function getVariant() {
		return $this->File->Variant;
	}

	/**
	 * Return a html5 tag of the appropriate for this file (normally img or a)
	 *
	 * @return string
	 */
	public function forTemplate() {
		return $this->getTag() ?: '';
	}

	/**
	 * Return a html5 tag of the appropriate for this file (normally img or a)
	 *
	 * @return string
	 */
	public function getTag() {
		$template = $this->File->getFrontendTemplate();
		if(empty($template)) {
			return '';
		}
		return (string)$this->renderWith($template);
	}

	public function requireDefaultRecords() {
		parent::requireDefaultRecords();

		// Check if old file records should be migrated
		if(!$this->config()->migrate_legacy_file) {
			return;
		}

		$migrated = FileMigrationHelper::singleton()->run();
		if($migrated) {
			DB::alteration_message("{$migrated} File DataObjects upgraded","changed");
		}
	}

	/**
	 * Joins one or more segments together to build a Filename identifier.
	 *
	 * Note that the result will not have a leading slash, and should not be used
	 * with local file paths.
	 *
	 * @param string $part,... Parts
	 * @return string
	 */
	public static function join_paths() {
		$args = func_get_args();
		if(count($args) === 1 && is_array($args[0])) {
			$args = $args[0];
		}

		$parts = array();
		foreach($args as $arg) {
			$part = trim($arg, ' \\/');
			if($part) {
				$parts[] = $part;
			}
		}

		return implode('/', $parts);
	}

	public function deleteFile() {
		return $this->File->deleteFile();
	}

	public function getVisibility() {
		return $this->File->getVisibility();
	}

	public function publishFile() {
		$this->File->publishFile();
	}

	public function protectFile() {
		$this->File->protectFile();
	}

	public function grantFile() {
		$this->File->grantFile();
	}

	public function revokeFile() {
		$this->File->revokeFile();
	}

	public function canViewFile() {
		return $this->File->canViewFile();
	}
}
