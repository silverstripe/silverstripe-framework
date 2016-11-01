<?php

namespace SilverStripe\Forms;

use SilverStripe\Assets\Storage\AssetContainer;
use SilverStripe\Assets\File;
use SilverStripe\Assets\FileNameFilter;
use SilverStripe\Assets\Folder;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Object;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\RelationList;
use SilverStripe\ORM\UnsavedRelationList;
use SilverStripe\Security\Permission;
use SilverStripe\View\Requirements;
use SilverStripe\View\ArrayData;
use SilverStripe\View\ViewableData;
use SilverStripe\View\ViewableData_Customised;
use InvalidArgumentException;
use Exception;

/**
 * Field for uploading single or multiple files of all types, including images.
 *
 * <b>Features (some might not be available to old browsers):</b>
 *
 * - File Drag&Drop support
 * - Progressbar
 * - Image thumbnail/file icons even before upload finished
 * - Saving into relations on form submit
 * - Edit file
 * - allowedExtensions is by default File::$allowed_extensions<li>maxFileSize the value of min(upload_max_filesize,
 * post_max_size) from php.ini
 *
 * <>Usage</b>
 *
 * @example <code>
 * $UploadField = new UploadField('AttachedImages', 'Please upload some images <span>(max. 5 files)</span>');
 * $UploadField->setAllowedFileCategories('image');
 * $UploadField->setAllowedMaxFileNumber(5);
 * </code>
 *
 * Caution: The form field does not include any JavaScript or CSS when used outside of the CMS context,
 * since the required frontend dependencies are included through CMS bundling.
 */
class UploadField extends FileField {

	/**
	 * @var array
	 */
	private static $allowed_actions = array(
		'upload',
		'attach',
		'handleItem',
		'handleSelect',
		'fileexists'
	);

	/**
	 * @var array
	 */
	private static $url_handlers = array(
		'item/$ID' => 'handleItem',
		'select' => 'handleSelect',
		'$Action!' => '$Action',
	);

	/**
	 * Template to use for the file button widget
	 *
	 * @var string
	 */
	protected $templateFileButtons = null;

	/**
	 * Template to use for the edit form
	 *
	 * @var string
	 */
	protected $templateFileEdit = null;

	/**
	 * Parent data record. Will be infered from parent form or controller if blank.
	 *
	 * @var DataObject
	 */
	protected $record;

	/**
	 * Items loaded into this field. May be a RelationList, or any other SS_List
	 *
	 * @var SS_List
	 */
	protected $items;

	/**
	 * Config for this field used in the front-end javascript
	 * (will be merged into the config of the javascript file upload plugin).
	 *
	 * @var array
	 */
	protected $ufConfig = array();

	/**
	 * Front end config defaults
	 *
	 * @config
	 * @var array
	 */
	private static $defaultConfig = array(
		/**
		 * Automatically upload the file once selected
		 *
		 * @var boolean
		 */
		'autoUpload' => true,
		/**
		 * Restriction on number of files that may be set for this field. Set to null to allow
		 * unlimited. If record has a has_one and allowedMaxFileNumber is null, it will be set to 1.
		 * The resulting value will be set to maxNumberOfFiles
		 *
		 * @var integer
		 */
		'allowedMaxFileNumber' => null,
		/**
		 * Can the user upload new files, or just select from existing files.
		 * String values are interpreted as permission codes.
		 *
		 * @var boolean|string
		 */
		'canUpload' => true,
		/**
		 * Can the user attach files from the assets archive on the site?
		 * String values are interpreted as permission codes.
		 *
		 * @var boolean|string
		 */
		'canAttachExisting' => "CMS_ACCESS_AssetAdmin",
		/**
		 * Shows the target folder for new uploads in the field UI.
		 * Disable to keep the internal filesystem structure hidden from users.
		 *
		 * @var boolean|string
		 */
		'canPreviewFolder' => true,
		/**
		 * Indicate a change event to the containing form if an upload
		 * or file edit/delete was performed.
		 *
		 * @var boolean
		 */
		'changeDetection' => true,
		/**
		 * Maximum width of the preview thumbnail
		 *
		 * @var integer
		 */
		'previewMaxWidth' => 80,
		/**
		 * Maximum height of the preview thumbnail
		 *
		 * @var integer
		 */
		'previewMaxHeight' => 60,
		/**
		 * javascript template used to display uploading files
		 *
		 * @see javascript/UploadField_uploadtemplate.js
		 * @var string
		 */
		'uploadTemplateName' => 'ss-uploadfield-uploadtemplate',
		/**
		 * javascript template used to display already uploaded files
		 *
		 * @see javascript/UploadField_downloadtemplate.js
		 * @var string
		 */
		'downloadTemplateName' => 'ss-uploadfield-downloadtemplate',
		/**
		 * Show a warning when overwriting a file.
		 * This requires Upload->replaceFile config to be set to true, otherwise
		 * files will be renamed instead of overwritten
		 *
		 * @see Upload
		 * @var boolean
		 */
		'overwriteWarning' => true
	);

	/**
	 * @var String Folder to display in "Select files" list.
	 * Defaults to listing all files regardless of folder.
	 * The folder path should be relative to the webroot.
	 * See {@link FileField->folderName} to set the upload target instead.
	 * @example admin/folder/subfolder
	 */
	protected $displayFolderName;

	/**
	 * FieldList $fields or string $name (of a method on File to provide a fields) for the EditForm
	 * @example 'getCMSFields'
	 *
	 * @var FieldList|string
	 */
	protected $fileEditFields = null;

	/**
	 * FieldList $actions or string $name (of a method on File to provide a actions) for the EditForm
	 * @example 'getCMSActions'
	 *
	 * @var FieldList|string
	 */
	protected $fileEditActions = null;

	/**
	 * Validator (eg RequiredFields) or string $name (of a method on File to provide a Validator) for the EditForm
	 * @example 'getCMSValidator'
	 *
	 * @var RequiredFields|string
	 */
	protected $fileEditValidator = null;

	/**
	 * Construct a new UploadField instance
	 *
	 * @param string $name The internal field name, passed to forms.
	 * @param string $title The field label.
	 * @param SS_List $items If no items are defined, the field will try to auto-detect an existing relation on
	 *                       @link $record}, with the same name as the field name.
	 */
	public function __construct($name, $title = null, SS_List $items = null) {

		// TODO thats the first thing that came to my head, feel free to change it
		$this->addExtraClass('ss-upload'); // class, used by js
		$this->addExtraClass('ss-uploadfield'); // class, used by css for uploadfield only

		$this->ufConfig = self::config()->defaultConfig;

		parent::__construct($name, $title);

		if($items) $this->setItems($items);

		// filter out '' since this would be a regex problem on JS end
		$this->getValidator()->setAllowedExtensions(
			array_filter(File::config()->allowed_extensions)
		);

		// get the lower max size
		$maxUpload = File::ini2bytes(ini_get('upload_max_filesize'));
		$maxPost = File::ini2bytes(ini_get('post_max_size'));
		$this->getValidator()->setAllowedMaxFileSize(min($maxUpload, $maxPost));
	}

	/**
	 * Set name of template used for Buttons on each file (replace, edit, remove, delete) (without path or extension)
	 *
	 * @param string $template
	 * @return $this
	 */
	public function setTemplateFileButtons($template) {
		$this->templateFileButtons = $template;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTemplateFileButtons() {
		return $this->_templates($this->templateFileButtons, '_FileButtons');
	}

	/**
	 * Set name of template used for the edit (inline & popup) of a file file (without path or extension)
	 *
	 * @param string $template
	 * @return $this
	 */
	public function setTemplateFileEdit($template) {
		$this->templateFileEdit = $template;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTemplateFileEdit() {
		return $this->_templates($this->templateFileEdit, '_FileEdit');
	}

	/**
	 * Determine if the target folder for new uploads in is visible the field UI.
	 *
	 * @return boolean
	 */
	public function canPreviewFolder() {
		if(!$this->isActive()) return false;
		$can = $this->getConfig('canPreviewFolder');
		return (is_bool($can)) ? $can : Permission::check($can);
	}

	/**
	 * Determine if the target folder for new uploads in is visible the field UI.
	 * Disable to keep the internal filesystem structure hidden from users.
	 *
	 * @param boolean|string $canPreviewFolder Either a boolean flag, or a
	 * required permission code
	 * @return UploadField Self reference
	 */
	public function setCanPreviewFolder($canPreviewFolder) {
		return $this->setConfig('canPreviewFolder', $canPreviewFolder);
	}

	/**
	 * Determine if the field should show a warning when overwriting a file.
	 * This requires Upload->replaceFile config to be set to true, otherwise
	 * files will be renamed instead of overwritten (although the warning will
	 * still be displayed)
	 *
	 * @return boolean
	 */
	public function getOverwriteWarning() {
		return $this->getConfig('overwriteWarning');
	}

	/**
	 * Determine if the field should show a warning when overwriting a file.
	 * This requires Upload->replaceFile config to be set to true, otherwise
	 * files will be renamed instead of overwritten (although the warning will
	 * still be displayed)
	 *
	 * @param boolean $overwriteWarning
	 * @return UploadField Self reference
	 */
	public function setOverwriteWarning($overwriteWarning) {
		return $this->setConfig('overwriteWarning', $overwriteWarning);
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setDisplayFolderName($name) {
		$this->displayFolderName = $name;
		return $this;
	}

	/**
	 * @return String
	 */
	public function getDisplayFolderName() {
		return $this->displayFolderName;
	}

	/**
	 * Force a record to be used as "Parent" for uploaded Files (eg a Page with a has_one to File)
	 *
	 * @param DataObject $record
	 * @return $this
	 */
	public function setRecord($record) {
		$this->record = $record;
		return $this;
	}
	/**
	 * Get the record to use as "Parent" for uploaded Files (eg a Page with a has_one to File) If none is set, it will
	 * use Form->getRecord() or Form->Controller()->data()
	 *
	 * @return DataObject
	 */
	public function getRecord() {
		if (!$this->record && $this->form) {
			if (($record = $this->form->getRecord()) && ($record instanceof DataObject)) {
				$this->record = $record;
			} elseif (($controller = $this->form->getController())
				&& $controller->hasMethod('data')
				&& ($record = $controller->data())
				&& ($record instanceof DataObject)
			) {
				$this->record = $record;
			}
		}
		return $this->record;
	}

	/**
	 * Loads the related record values into this field. UploadField can be uploaded
	 * in one of three ways:
	 *
	 *  - By passing in a list of file IDs in the $value parameter (an array with a single
	 *    key 'Files', with the value being the actual array of IDs).
	 *  - By passing in an explicit list of File objects in the $record parameter, and
	 *    leaving $value blank.
	 *  - By passing in a dataobject in the $record parameter, from which file objects
	 *    will be extracting using the field name as the relation field.
	 *
	 * Each of these methods will update both the items (list of File objects) and the
	 * field value (list of file ID values).
	 *
	 * @param array $value Array of submitted form data, if submitting from a form
	 * @param array|DataObject|SS_List $record Full source record, either as a DataObject,
	 * SS_List of items, or an array of submitted form data
	 * @return $this Self reference
	 * @throws ValidationException
	 */
	public function setValue($value, $record = null) {

		// If we're not passed a value directly, we can attempt to infer the field
		// value from the second parameter by inspecting its relations
		$items = new ArrayList();

		// Determine format of presented data
		if(empty($value) && $record) {
			// If a record is given as a second parameter, but no submitted values,
			// then we should inspect this instead for the form values

			if(($record instanceof DataObject) && $record->hasMethod($this->getName())) {
				// If given a dataobject use reflection to extract details

				$data = $record->{$this->getName()}();
				if($data instanceof DataObject) {
					// If has_one, add sole item to default list
					$items->push($data);
				} elseif($data instanceof SS_List) {
					// For many_many and has_many relations we can use the relation list directly
					$items = $data;
				}
			} elseif($record instanceof SS_List) {
				// If directly passing a list then save the items directly
				$items = $record;
			}
		} elseif(!empty($value['Files'])) {
			// If value is given as an array (such as a posted form), extract File IDs from this
			$class = $this->getRelationAutosetClass();
			$items = DataObject::get($class)->byIDs($value['Files']);
		}

		// If javascript is disabled, direct file upload (non-html5 style) can
		// trigger a single or multiple file submission. Note that this may be
		// included in addition to re-submitted File IDs as above, so these
		// should be added to the list instead of operated on independently.
		if($uploadedFiles = $this->extractUploadedFileData($value)) {
			foreach($uploadedFiles as $tempFile) {
				$file = $this->saveTemporaryFile($tempFile, $error);
				if($file) {
					$items->add($file);
				} else {
					throw new ValidationException($error);
				}
			}
		}

		// Filter items by what's allowed to be viewed
		$filteredItems = new ArrayList();
		$fileIDs = array();
		foreach($items as $file) {
			if($file->exists() && $file->canView()) {
				$filteredItems->push($file);
				$fileIDs[] = $file->ID;
			}
		}

		// Filter and cache updated item list
		$this->items = $filteredItems;
		// Same format as posted form values for this field. Also ensures that
		// $this->setValue($this->getValue()); is non-destructive
		$value = $fileIDs ? array('Files' => $fileIDs) : null;

		// Set value using parent
		parent::setValue($value, $record);
		return $this;
	}

	/**
	 * Sets the items assigned to this field as an SS_List of File objects.
	 * Calling setItems will also update the value of this field, as well as
	 * updating the internal list of File items.
	 *
	 * @param SS_List $items
	 * @return UploadField self reference
	 */
	public function setItems(SS_List $items) {
		return $this->setValue(null, $items);
	}

	/**
	 * Retrieves the current list of files
	 *
	 * @return SS_List
	 */
	public function getItems() {
		return $this->items ? $this->items : new ArrayList();
	}

	/**
	 * Retrieves a customised list of all File records to ensure they are
	 * properly viewable when rendered in the field template.
	 *
	 * @return SS_List[ViewableData_Customised]
	 */
	public function getCustomisedItems() {
		$customised = new ArrayList();
		foreach($this->getItems() as $file) {
			$customised->push($this->customiseFile($file));
		}
		return $customised;
	}

	/**
	 * Retrieves the list of selected file IDs
	 *
	 * @return array
	 */
	public function getItemIDs() {
		$value = $this->Value();
		return empty($value['Files']) ? array() : $value['Files'];
	}

	public function Value() {
		// Re-override FileField Value to use data value
		return $this->dataValue();
	}

	/**
	 * @param DataObject|DataObjectInterface $record
	 * @return $this
	 */
	public function saveInto(DataObjectInterface $record) {
		// Check required relation details are available
		$fieldname = $this->getName();
		if(!$fieldname) return $this;

		// Get details to save
		$idList = $this->getItemIDs();

		// Check type of relation
		$relation = $record->hasMethod($fieldname) ? $record->$fieldname() : null;
		if($relation && ($relation instanceof RelationList || $relation instanceof UnsavedRelationList)) {
			// has_many or many_many
			$relation->setByIDList($idList);
		} elseif(DataObject::getSchema()->hasOneComponent(get_class($record), $fieldname)) {
			// has_one
			$record->{"{$fieldname}ID"} = $idList ? reset($idList) : 0;
		}
		return $this;
	}

	/**
	 * Customises a file with additional details suitable for rendering in the
	 * UploadField.ss template
	 *
	 * @param ViewableData|AssetContainer $file
	 * @return ViewableData_Customised
	 */
	protected function customiseFile(AssetContainer $file) {
		$file = $file->customise(array(
			'UploadFieldThumbnailURL' => $this->getThumbnailURLForFile($file),
			'UploadFieldDeleteLink' => $this->getItemHandler($file->ID)->DeleteLink(),
			'UploadFieldEditLink' => $this->getItemHandler($file->ID)->EditLink(),
			'UploadField' => $this
		));
		// we do this in a second customise to have the access to the previous customisations
		return $file->customise(array(
			'UploadFieldFileButtons' => $file->renderWith($this->getTemplateFileButtons())
		));
	}

	/**
	 * Assign a front-end config variable for the upload field
	 *
	 * @see https://github.com/blueimp/jQuery-File-Upload/wiki/Options for the list of front end options available
	 *
	 * @param string $key
	 * @param mixed $val
	 * @return UploadField self reference
	 */
	public function setConfig($key, $val) {
		$this->ufConfig[$key] = $val;
		return $this;
	}

	/**
	 * Gets a front-end config variable for the upload field
	 *
	 * @see https://github.com/blueimp/jQuery-File-Upload/wiki/Options for the list of front end options available
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getConfig($key) {
		if(!isset($this->ufConfig[$key])) return null;
		return $this->ufConfig[$key];
	}

	/**
	 * Determine if the field should automatically upload the file.
	 *
	 * @return boolean
	 */
	public function getAutoUpload() {
		return $this->getConfig('autoUpload');
	}

	/**
	 * Determine if the field should automatically upload the file
	 *
	 * @param boolean $autoUpload
	 * @return UploadField Self reference
	 */
	public function setAutoUpload($autoUpload) {
		return $this->setConfig('autoUpload', $autoUpload);
	}

	/**
	 * Determine maximum number of files allowed to be attached
	 * Defaults to 1 for has_one and null (unlimited) for
	 * many_many and has_many relations.
	 *
	 * @return integer|null Maximum limit, or null for no limit
	 */
	public function getAllowedMaxFileNumber() {
		$allowedMaxFileNumber = $this->getConfig('allowedMaxFileNumber');

		// if there is a has_one relation with that name on the record and
		// allowedMaxFileNumber has not been set, it's wanted to be 1
		if(empty($allowedMaxFileNumber)) {
			$record = $this->getRecord();
			$name = $this->getName();
			if($record && DataObject::getSchema()->hasOneComponent(get_class($record), $name)) {
				return 1; // Default for has_one
			} else {
				return null; // Default for has_many and many_many
			}
		} else {
			return $allowedMaxFileNumber;
		}
	}

	/**
	 * Determine maximum number of files allowed to be attached.
	 *
	 * @param integer|null $allowedMaxFileNumber Maximum limit. 0 or null will be treated as unlimited
	 * @return UploadField Self reference
	 */
	public function setAllowedMaxFileNumber($allowedMaxFileNumber) {
		return $this->setConfig('allowedMaxFileNumber', $allowedMaxFileNumber);
	}

	/**
	 * Determine if the user has permission to upload.
	 *
	 * @return boolean
	 */
	public function canUpload() {
		if(!$this->isActive()) return false;
		$can = $this->getConfig('canUpload');
		return (is_bool($can)) ? $can : Permission::check($can);
	}

	/**
	 * Specify whether the user can upload files.
	 * String values will be treated as required permission codes
	 *
	 * @param boolean|string $canUpload Either a boolean flag, or a required
	 * permission code
	 * @return UploadField Self reference
	 */
	public function setCanUpload($canUpload) {
		return $this->setConfig('canUpload', $canUpload);
	}

	/**
	 * Determine if the user has permission to attach existing files
	 * By default returns true if the user has the CMS_ACCESS_AssetAdmin permission
	 *
	 * @return boolean
	 */
	public function canAttachExisting() {
		if(!$this->isActive()) return false;
		$can = $this->getConfig('canAttachExisting');
		return (is_bool($can)) ? $can : Permission::check($can);
	}

	/**
	 * Returns true if the field is neither readonly nor disabled
	 *
	 * @return boolean
	 */
	public function isActive() {
		return !$this->isDisabled() && !$this->isReadonly();
	}

	/**
	 * Specify whether the user can attach existing files
	 * String values will be treated as required permission codes
	 *
	 * @param boolean|string $canAttachExisting Either a boolean flag, or a
	 * required permission code
	 * @return UploadField Self reference
	 */
	public function setCanAttachExisting($canAttachExisting) {
		return $this->setConfig('canAttachExisting', $canAttachExisting);
	}

	/**
	 * Gets thumbnail width. Defaults to 80
	 *
	 * @return integer
	 */
	public function getPreviewMaxWidth() {
		return $this->getConfig('previewMaxWidth');
	}

	/**
	 * @see UploadField::getPreviewMaxWidth()
	 *
	 * @param integer $previewMaxWidth
	 * @return UploadField Self reference
	 */
	public function setPreviewMaxWidth($previewMaxWidth) {
		return $this->setConfig('previewMaxWidth', $previewMaxWidth);
	}

	/**
	 * Gets thumbnail height. Defaults to 60
	 *
	 * @return integer
	 */
	public function getPreviewMaxHeight() {
		return $this->getConfig('previewMaxHeight');
	}

	/**
	 * @see UploadField::getPreviewMaxHeight()
	 *
	 * @param integer $previewMaxHeight
	 * @return UploadField Self reference
	 */
	public function setPreviewMaxHeight($previewMaxHeight) {
		return $this->setConfig('previewMaxHeight', $previewMaxHeight);
	}

	/**
	 * javascript template used to display uploading files
	 * Defaults to 'ss-uploadfield-uploadtemplate'
	 *
	 * @see javascript/UploadField_uploadtemplate.js
	 * @return string
	 */
	public function getUploadTemplateName() {
		return $this->getConfig('uploadTemplateName');
	}

	/**
	 * @see UploadField::getUploadTemplateName()
	 *
	 * @param string $uploadTemplateName
	 * @return UploadField Self reference
	 */
	public function setUploadTemplateName($uploadTemplateName) {
		return $this->setConfig('uploadTemplateName', $uploadTemplateName);
	}

	/**
	 * javascript template used to display already uploaded files
	 * Defaults to 'ss-downloadfield-downloadtemplate'
	 *
	 * @see javascript/DownloadField_downloadtemplate.js
	 * @return string
	 */
	public function getDownloadTemplateName() {
		return $this->getConfig('downloadTemplateName');
	}

	/**
	 * @see Uploadfield::getDownloadTemplateName()
	 *
	 * @param string $downloadTemplateName
	 * @return Uploadfield Self reference
	 */
	public function setDownloadTemplateName($downloadTemplateName) {
		return $this->setConfig('downloadTemplateName', $downloadTemplateName);
	}

	/**
	 * FieldList $fields for the EditForm
	 * @example 'getCMSFields'
	 *
	 * @param DataObject $file File context to generate fields for
	 * @return FieldList List of form fields
	 */
	public function getFileEditFields(DataObject $file) {
		// Empty actions, generate default
		if(empty($this->fileEditFields)) {
			$fields = $file->getCMSFields();
			// Only display main tab, to avoid overly complex interface
			if($fields->hasTabSet() && ($mainTab = $fields->findOrMakeTab('Root.Main'))) {
				$fields = $mainTab->Fields();
			}
			return $fields;
		}

		// Fields instance
		if ($this->fileEditFields instanceof FieldList) {
			return $this->fileEditFields;
		}

		// Method to call on the given file
		if($file->hasMethod($this->fileEditFields)) {
			return $file->{$this->fileEditFields}();
		}

		throw new InvalidArgumentException("Invalid value for UploadField::fileEditFields");
	}

	/**
	 * FieldList $fields or string $name (of a method on File to provide a fields) for the EditForm
	 * @example 'getCMSFields'
	 *
	 * @param FieldList|string
	 * @return Uploadfield Self reference
	 */
	public function setFileEditFields($fileEditFields) {
		$this->fileEditFields = $fileEditFields;
		return $this;
	}

	/**
	 * FieldList $actions or string $name (of a method on File to provide a actions) for the EditForm
	 * @example 'getCMSActions'
	 *
	 * @param DataObject $file File context to generate form actions for
	 * @return FieldList Field list containing FormAction
	 */
	public function getFileEditActions(DataObject $file) {
		// Empty actions, generate default
		if(empty($this->fileEditActions)) {
			$actions = new FieldList($saveAction = new FormAction('doEdit', _t('UploadField.DOEDIT', 'Save')));
			$saveAction->addExtraClass('ss-ui-action-constructive icon-accept');
			return $actions;
		}

		// Actions instance
		if ($this->fileEditActions instanceof FieldList) {
			return $this->fileEditActions;
		}

		// Method to call on the given file
		if($file->hasMethod($this->fileEditActions)) {
			return $file->{$this->fileEditActions}();
		}

		throw new InvalidArgumentException("Invalid value for UploadField::fileEditActions");
	}

	/**
	 * FieldList $actions or string $name (of a method on File to provide a actions) for the EditForm
	 * @example 'getCMSActions'
	 *
	 * @param FieldList|string
	 * @return Uploadfield Self reference
	 */
	public function setFileEditActions($fileEditActions) {
		$this->fileEditActions = $fileEditActions;
		return $this;
	}

	/**
	 * Determines the validator to use for the edit form
	 * @example 'getCMSValidator'
	 *
	 * @param DataObject $file File context to generate validator from
	 * @return Validator Validator object
	 */
	public function getFileEditValidator(DataObject $file) {
		// Empty validator
		if(empty($this->fileEditValidator)) {
			return null;
		}

		// Validator instance
		if($this->fileEditValidator instanceof Validator) {
			return $this->fileEditValidator;
		}

		// Method to call on the given file
		if($file->hasMethod($this->fileEditValidator)) {
			return $file->{$this->fileEditValidator}();
		}

		throw new InvalidArgumentException("Invalid value for UploadField::fileEditValidator");
	}

	/**
	 * Validator (eg RequiredFields) or string $name (of a method on File to provide a Validator) for the EditForm
	 * @example 'getCMSValidator'
	 *
	 * @param Validator|string
	 * @return Uploadfield Self reference
	 */
	public function setFileEditValidator($fileEditValidator) {
		$this->fileEditValidator = $fileEditValidator;
		return $this;
	}

	/**
	 *
	 * @param File|AssetContainer $file
	 * @return string URL to thumbnail
	 */
	protected function getThumbnailURLForFile(AssetContainer $file) {
		if (!$file->exists()) {
			return null;
		}

		// Attempt to generate image at given size
		$width = $this->getPreviewMaxWidth();
		$height = $this->getPreviewMaxHeight();
		if ($file->hasMethod('ThumbnailURL')) {
			return $file->ThumbnailURL($width, $height);
		}
		if ($file->hasMethod('Thumbnail')) {
			return $file->Thumbnail($width, $height)->getURL();
		}
		if ($file->hasMethod('Fit')) {
			return $file->Fit($width, $height)->getURL();
		}

		// Check if unsized icon is available
		if($file->hasMethod('getIcon')) {
			return $file->getIcon();
		}
		return null;
	}

	public function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array('data-selectdialog-url', $this->Link('select'))
		);
	}

	public function extraClass() {
		if($this->isDisabled()) {
			$this->addExtraClass('disabled');
		}
		if($this->isReadonly()) {
			$this->addExtraClass('readonly');
		}

		return parent::extraClass();
	}

	public function Field($properties = array()) {
		// Calculated config as per jquery.fileupload-ui.js
		$allowedMaxFileNumber = $this->getAllowedMaxFileNumber();
		$config = array(
			'url' => $this->Link('upload'),
			'urlSelectDialog' => $this->Link('select'),
			'urlAttach' => $this->Link('attach'),
			'urlFileExists' => $this->Link('fileexists'),
			'acceptFileTypes' => '.+$',
			// Fileupload treats maxNumberOfFiles as the max number of _additional_ items allowed
			'maxNumberOfFiles' => $allowedMaxFileNumber ? ($allowedMaxFileNumber - count($this->getItemIDs())) : null,
			'replaceFile' => $this->getUpload()->getReplaceFile(),
		);

		// Validation: File extensions
		if ($allowedExtensions = $this->getAllowedExtensions()) {
			$config['acceptFileTypes'] = '(\.|\/)(' . implode('|', $allowedExtensions) . ')$';
			$config['errorMessages']['acceptFileTypes'] = _t(
				'File.INVALIDEXTENSIONSHORT',
				'Extension is not allowed'
			);
		}

		// Validation: File size
		if ($allowedMaxFileSize = $this->getValidator()->getAllowedMaxFileSize()) {
			$config['maxFileSize'] = $allowedMaxFileSize;
			$config['errorMessages']['maxFileSize'] = _t(
				'File.TOOLARGESHORT',
				'File size exceeds {size}',
				array('size' => File::format_size($config['maxFileSize']))
			);
		}

		// Validation: Number of files
		if ($allowedMaxFileNumber) {
			if($allowedMaxFileNumber > 1) {
				$config['errorMessages']['maxNumberOfFiles'] = _t(
					'UploadField.MAXNUMBEROFFILESSHORT',
					'Can only upload {count} files',
					array('count' => $allowedMaxFileNumber)
				);
			} else {
				$config['errorMessages']['maxNumberOfFiles'] = _t(
					'UploadField.MAXNUMBEROFFILESONE',
					'Can only upload one file'
				);
			}
		}

		// add overwrite warning error message to the config object sent to Javascript
		if ($this->getOverwriteWarning()) {
			$config['errorMessages']['overwriteWarning'] =
				_t('UploadField.OVERWRITEWARNING', 'File with the same name already exists');
		}

		$mergedConfig = array_merge($config, $this->ufConfig);
		return parent::Field(array(
			'configString' => Convert::raw2json($mergedConfig),
			'config' => new ArrayData($mergedConfig),
			'multiple' => $allowedMaxFileNumber !== 1
		));
	}

	/**
	 * Validation method for this field, called when the entire form is validated
	 *
	 * @param Validator $validator
	 * @return boolean
	 */
	public function validate($validator) {
		$name = $this->getName();
		$files = $this->getItems();

		// If there are no files then quit
		if($files->count() == 0) return true;

		// Check max number of files
		$maxFiles = $this->getAllowedMaxFileNumber();
		if($maxFiles && ($files->count() > $maxFiles)) {
			$validator->validationError(
				$name,
				_t(
					'UploadField.MAXNUMBEROFFILES',
					'Max number of {count} file(s) exceeded.',
					array('count' => $maxFiles)
				),
				"validation"
			);
			return false;
		}

		// Revalidate each file against nested validator
		$this->upload->clearErrors();
		foreach($files as $file) {
			// Generate $_FILES style file attribute array for upload validator
			$tmpFile = array(
				'name' => $file->Name,
				'type' => null, // Not used for type validation
				'size' => $file->AbsoluteSize,
				'tmp_name' => null, // Should bypass is_uploaded_file check
				'error' => UPLOAD_ERR_OK,
			);
			$this->upload->validate($tmpFile);
		}

		// Check all errors
		if($errors = $this->upload->getErrors()) {
			foreach($errors as $error) {
				$validator->validationError($name, $error, "validation");
			}
			return false;
		}

		return true;
	}

	/**
	 * @param HTTPRequest $request
	 * @return UploadField_ItemHandler
	 */
	public function handleItem(HTTPRequest $request) {
		return $this->getItemHandler($request->param('ID'));
	}

	/**
	 * @param int $itemID
	 * @return UploadField_ItemHandler
	 */
	public function getItemHandler($itemID) {
		return UploadField_ItemHandler::create($this, $itemID);
	}

	/**
	 * @param HTTPRequest $request
	 * @return UploadField_SelectHandler
	 */
	public function handleSelect(HTTPRequest $request) {
		if(!$this->canAttachExisting()) {
			return $this->httpError(403);
		}
		return UploadField_SelectHandler::create($this, $this->getFolderName());
	}

	/**
	 * Given an array of post variables, extract all temporary file data into an array
	 *
	 * @param array $postVars Array of posted form data
	 * @return array List of temporary file data
	 */
	protected function extractUploadedFileData($postVars) {

		// Note: Format of posted file parameters in php is a feature of using
		// <input name='{$Name}[Uploads][]' /> for multiple file uploads
		$tmpFiles = array();
		if(	!empty($postVars['tmp_name'])
			&& is_array($postVars['tmp_name'])
			&& !empty($postVars['tmp_name']['Uploads'])
		) {
			for($i = 0; $i < count($postVars['tmp_name']['Uploads']); $i++) {
				// Skip if "empty" file
				if(empty($postVars['tmp_name']['Uploads'][$i])) continue;
				$tmpFile = array();
				foreach(array('name', 'type', 'tmp_name', 'error', 'size') as $field) {
					$tmpFile[$field] = $postVars[$field]['Uploads'][$i];
				}
				$tmpFiles[] = $tmpFile;
			}
		} elseif(!empty($postVars['tmp_name'])) {
			// Fallback to allow single file uploads (method used by AssetUploadField)
			$tmpFiles[] = $postVars;
		}

		return $tmpFiles;
	}

	/**
	 * Loads the temporary file data into a File object
	 *
	 * @param array $tmpFile Temporary file data
	 * @param string $error Error message
	 * @return AssetContainer File object, or null if error
	 */
	protected function saveTemporaryFile($tmpFile, &$error = null) {
		// Determine container object
		$error = null;
		$fileObject = null;

		if (empty($tmpFile)) {
			$error = _t('UploadField.FIELDNOTSET', 'File information not found');
			return null;
		}

		if($tmpFile['error']) {
			$error = $tmpFile['error'];
			return null;
		}

		// Search for relations that can hold the uploaded files, but don't fallback
		// to default if there is no automatic relation
		if ($relationClass = $this->getRelationAutosetClass(null)) {
			// Allow File to be subclassed
			if($relationClass === 'SilverStripe\\Assets\\File' && isset($tmpFile['name'])) {
				$relationClass = File::get_class_for_file_extension(
					File::get_file_extension($tmpFile['name'])
				);
			}
			// Create new object explicitly. Otherwise rely on Upload::load to choose the class.
			$fileObject = Object::create($relationClass);
			if(! ($fileObject instanceof DataObject) || !($fileObject instanceof AssetContainer)) {
				throw new InvalidArgumentException("Invalid asset container $relationClass");
			}
		}

		// Get the uploaded file into a new file object.
		try {
			$this->upload->loadIntoFile($tmpFile, $fileObject, $this->getFolderName());
		} catch (Exception $e) {
			// we shouldn't get an error here, but just in case
			$error = $e->getMessage();
			return null;
		}

		// Check if upload field has an error
		if ($this->upload->isError()) {
			$error = implode(' ' . PHP_EOL, $this->upload->getErrors());
			return null;
		}

		// return file
		return $this->upload->getFile();
	}

	/**
	 * Safely encodes the File object with all standard fields required
	 * by the front end
	 *
	 * @param File|AssetContainer $file Object which contains a file
	 * @return array Array encoded list of file attributes
	 */
	protected function encodeFileAttributes(AssetContainer $file) {
		// Collect all output data.
		$customised =  $this->customiseFile($file);
		return array(
			'id' => $file->ID,
			'name' => basename($file->getFilename()),
			'url' => $file->getURL(),
			'thumbnail_url' => $customised->UploadFieldThumbnailURL,
			'edit_url' => $customised->UploadFieldEditLink,
			'size' => $file->getAbsoluteSize(),
			'type' => File::get_file_type($file->getFilename()),
			'buttons' => (string)$customised->UploadFieldFileButtons,
			'fieldname' => $this->getName()
		);
	}

	/**
	 * Action to handle upload of a single file
	 *
	 * @param HTTPRequest $request
	 * @return HTTPResponse
	 * @return HTTPResponse
	 */
	public function upload(HTTPRequest $request) {
		if($this->isDisabled() || $this->isReadonly() || !$this->canUpload()) {
			return $this->httpError(403);
		}

		// Protect against CSRF on destructive action
		$token = $this->getForm()->getSecurityToken();
		if(!$token->checkRequest($request)) return $this->httpError(400);

		// Get form details
		$name = $this->getName();
		$postVars = $request->postVar($name);

		// Extract uploaded files from Form data
		$uploadedFiles = $this->extractUploadedFileData($postVars);
		$return = array();

		// Save the temporary files into a File objects
		// and save data/error on a per file basis
		foreach ($uploadedFiles as $tempFile) {
			$file = $this->saveTemporaryFile($tempFile, $error);
			if(empty($file)) {
				array_push($return, array('error' => $error));
			} else {
				array_push($return, $this->encodeFileAttributes($file));
			}
			$this->upload->clearErrors();
		}

		// Format response with json
		$response = new HTTPResponse(Convert::raw2json($return));
		$response->addHeader('Content-Type', 'text/plain');
		return $response;
	}

	/**
	 * Retrieves details for files that this field wishes to attache to the
	 * client-side form
	 *
	 * @param HTTPRequest $request
	 * @return HTTPResponse
	 */
	public function attach(HTTPRequest $request) {
		if(!$request->isPOST()) return $this->httpError(403);
		if(!$this->canAttachExisting()) return $this->httpError(403);

		// Retrieve file attributes required by front end
		$return = array();
		$files = File::get()->byIDs($request->postVar('ids'));
		foreach($files as $file) {
			$return[] = $this->encodeFileAttributes($file);
		}
		$response = new HTTPResponse(Convert::raw2json($return));
		$response->addHeader('Content-Type', 'application/json');
		return $response;
	}

	/**
	 * Check if file exists, both checking filtered filename and exact filename
	 *
	 * @param string $originalFile Filename
	 * @return bool
	 */
	protected function checkFileExists($originalFile) {

		// Check both original and safely filtered filename
		$nameFilter = FileNameFilter::create();
		$filteredFile = $nameFilter->filter($originalFile);

		// Resolve expected folder name
		$folderName = $this->getFolderName();
		$folder = Folder::find_or_make($folderName);
		$parentPath = $folder ? $folder->getFilename() : '';

		// check if either file exists
		return File::find($parentPath.$originalFile) || File::find($parentPath.$filteredFile);
	}

	/**
	 * Determines if a specified file exists
	 *
	 * @param HTTPRequest $request
	 * @return HTTPResponse
	 */
	public function fileexists(HTTPRequest $request) {
		// Assert that requested filename doesn't attempt to escape the directory
		$originalFile = $request->requestVar('filename');
		if($originalFile !== basename($originalFile)) {
			$return = array(
				'error' => _t('File.NOVALIDUPLOAD', 'File is not a valid upload')
			);
		} else {
			$return = array(
				'exists' => $this->checkFileExists($originalFile)
			);
		}

		// Encode and present response
		$response = new HTTPResponse(Convert::raw2json($return));
		$response->addHeader('Content-Type', 'application/json');
		if (!empty($return['error'])) $response->setStatusCode(400);
		return $response;
	}

	public function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->addExtraClass('readonly');
		$clone->setReadonly(true);
		return $clone;
	}

	/**
	 * Gets the foreign class that needs to be created, or 'File' as default if there
	 * is no relationship, or it cannot be determined.
	 *
	 * @param string $default Default value to return if no value could be calculated
	 * @return string Foreign class name.
	 */
	public function getRelationAutosetClass($default = 'SilverStripe\\Assets\\File') {

		// Don't autodetermine relation if no relationship between parent record
		if(!$this->relationAutoSetting) {
			return $default;
		}

		// Check record and name
		$name = $this->getName();
		$record = $this->getRecord();
		if(empty($name) || empty($record)) {
			return $default;
		} else {
			$class = $record->getRelationClass($name);
			return empty($class) ? $default : $class;
		}
	}

}
