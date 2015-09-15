<?php
/**
 * Represents a logical folder, which may be used to organise assets
 * stored in the configured backend.
 *
 * Unlike {@see File} dataobjects, there is not necessarily a physical filesystem entite which
 * represents a Folder, and it may be purely logical. However, a physical folder may exist
 * if the backend creates one.
 *
 * Additionally, folders do not have URLs (relative or absolute), nor do they have paths.
 *
 * When a folder is moved or renamed, records within it will automatically be copied to the updated
 * location.
 *
 * Deleting a folder will remove all child records, but not any physical files.
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

	public function exists() {
		return $this->isInDB();
	}

	/**
	 *
	 */
	public function populateDefaults() {
		parent::populateDefaults();

		if(!$this->Name) {
			$this->Name = _t('AssetAdmin.NEWFOLDER', "NewFolder");
		}
	}

	/**
	 * Find the given folder or create it as a database record
	 *
	 * @param string $folderPath Directory path relative to assets root
	 * @return Folder|null
	 */
	public static function find_or_make($folderPath) {
		// replace leading and trailing slashes
		$folderPath = preg_replace('/^\/?(.*)\/?$/', '$1', trim($folderPath));
		$parts = explode("/",$folderPath);

		$parentID = 0;
		$item = null;
		$filter = FileNameFilter::create();
		foreach($parts as $part) {
			if(!$part) {
				continue; // happens for paths with a trailing slash
			}

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
			$parentID = $item->ID;
		}

		return $item;
	}

	public function onBeforeDelete() {
		foreach($this->AllChildren() as $child) {
			$child->delete();
		}

		parent::onBeforeDelete();
	}

	/**
	 * Override setting the Title of Folders to that Name and Title are always in sync.
	 * Note that this is not appropriate for files, because someone might want to create a human-readable name
	 * of a file that is different from its name on disk. But folders should always match their name on disk.
	 * 
	 * @param string $title
	 * @return $this
	 */
	public function setTitle($title) {
		$this->setName($title);
		return $this;
	}

	/**
	 * Get the folder title
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->Name;
	}

	/**
	 * Override setting the Title of Folders to that Name and Title are always in sync.
	 * Note that this is not appropriate for files, because someone might want to create a human-readable name
	 * of a file that is different from its name on disk. But folders should always match their name on disk.
	 *
	 * @param string $name
	 * @return $this
	 */
	public function setName($name) {
		parent::setName($name);
		$this->setField('Title', $this->Name);
		return $this;
	}

	/**
	 * A folder doesn't have a (meaningful) file size.
	 *
	 * @return null
	 */
	public function getSize() {
		return null;
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
	 * Return the FieldList used to edit this folder in the CMS.
	 * You can modify this FieldList by subclassing folder, or by creating a {@link DataExtension}
	 * and implemeting updateCMSFields(FieldList $fields) on that extension.
	 *
	 * @return FieldList
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
	 *
	 * @return int
	 */
	public function numChildFolders() {
		return $this->ChildFolders()->count();
	}
	/**
	 * @return string
	 */
	public function CMSTreeClasses() {
		$classes = sprintf('class-%s', $this->class);

		if(!$this->canDelete()) {
			$classes .= " nodelete";
		}

		if(!$this->canEdit()) {
			$classes .= " disabled";
		}

		$classes .= $this->markingClasses('numChildFolders');

		return $classes;
	}

	/**
	 * @return string
	 */
	public function getTreeTitle() {
		return sprintf(
			"<span class=\"jstree-foldericon\"></span><span class=\"item\">%s</span>",
			Convert::raw2att(preg_replace('~\R~u', ' ', $this->Title))
		);
	}

	public function getFilename() {
		return parent::getFilename() . '/';
	}

	/**
	 * Folders do not have public URLs
	 *
	 * @return null
	 */
	public function getURL() {
		return null;
	}

	/**
	 * Folders do not have public URLs
	 *
	 * @return string
	 */
	public function getAbsoluteURL() {
		return null;
	}

	public function onAfterWrite() {
		parent::onAfterWrite();

		// Ensure that children loading $this->Parent() load the refreshed record
		$this->flushCache();
		$this->updateChildFilesystem();
	}

	public function updateFilesystem() {
		// No filesystem changes to update
	}

	/**
	 * If a write is skipped due to no changes, ensure that nested records still get asked to update
	 */
	public function onAfterSkippedWrite() {
		$this->updateChildFilesystem();
	}

	/**
	 * Update filesystem of all children
	 */
	public function updateChildFilesystem() {
		// Writing this record should trigger a write (and potential updateFilesystem) on each child
		foreach($this->AllChildren() as $child) {
			$child->write();
		}
	}

	public function StripThumbnail() {
		return null;
	}
}
