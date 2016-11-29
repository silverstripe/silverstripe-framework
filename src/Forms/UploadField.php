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
use SilverStripe\Security\Permission;
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
class UploadField extends FormField
{
    use FileUploadReceiver;

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
    public function __construct($name, $title = null, SS_List $items = null)
    {
        // TODO thats the first thing that came to my head, feel free to change it
        $this->addExtraClass('ss-upload'); // class, used by js
        $this->addExtraClass('ss-uploadfield'); // class, used by css for uploadfield only

        $this->ufConfig = self::config()->defaultConfig;
        $this->constructFileUploadReceiver();

        parent::__construct($name, $title);

        if ($items) {
            $this->setItems($items);
        }
    }

    /**
     * Set name of template used for Buttons on each file (replace, edit, remove, delete) (without path or extension)
     *
     * @param string $template
     * @return $this
     */
    public function setTemplateFileButtons($template)
    {
        $this->templateFileButtons = $template;
        return $this;
    }

    /**
     * @return string
     */
    public function getTemplateFileButtons()
    {
        return $this->_templates($this->templateFileButtons, '_FileButtons');
    }

    /**
     * Set name of template used for the edit (inline & popup) of a file file (without path or extension)
     *
     * @param string $template
     * @return $this
     */
    public function setTemplateFileEdit($template)
    {
        $this->templateFileEdit = $template;
        return $this;
    }

    /**
     * @return string
     */
    public function getTemplateFileEdit()
    {
        return $this->_templates($this->templateFileEdit, '_FileEdit');
    }

    /**
     * Determine if the target folder for new uploads in is visible the field UI.
     *
     * @return boolean
     */
    public function canPreviewFolder()
    {
        if (!$this->isActive()) {
            return false;
        }
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
    public function setCanPreviewFolder($canPreviewFolder)
    {
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
    public function getOverwriteWarning()
    {
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
    public function setOverwriteWarning($overwriteWarning)
    {
        return $this->setConfig('overwriteWarning', $overwriteWarning);
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setDisplayFolderName($name)
    {
        $this->displayFolderName = $name;
        return $this;
    }

    /**
     * @return String
     */
    public function getDisplayFolderName()
    {
        return $this->displayFolderName;
    }



    /**
     * Retrieves a customised list of all File records to ensure they are
     * properly viewable when rendered in the field template.
     *
     * @return SS_List[ViewableData_Customised]
     */
    public function getCustomisedItems()
    {
        $customised = new ArrayList();
        foreach ($this->getItems() as $file) {
            $customised->push($this->customiseFile($file));
        }
        return $customised;
    }

    /**
     * Customises a file with additional details suitable for rendering in the
     * UploadField.ss template
     *
     * @param ViewableData|AssetContainer $file
     * @return ViewableData_Customised
     */
    protected function customiseFile(AssetContainer $file)
    {
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
    public function setConfig($key, $val)
    {
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
    public function getConfig($key)
    {
        if (!isset($this->ufConfig[$key])) {
            return null;
        }
        return $this->ufConfig[$key];
    }

    /**
     * Determine if the field should automatically upload the file.
     *
     * @return boolean
     */
    public function getAutoUpload()
    {
        return $this->getConfig('autoUpload');
    }

    /**
     * Determine if the field should automatically upload the file
     *
     * @param boolean $autoUpload
     * @return UploadField Self reference
     */
    public function setAutoUpload($autoUpload)
    {
        return $this->setConfig('autoUpload', $autoUpload);
    }

    /**
     * Determine maximum number of files allowed to be attached
     * Defaults to 1 for has_one and null (unlimited) for
     * many_many and has_many relations.
     *
     * @return integer|null Maximum limit, or null for no limit
     */
    public function getAllowedMaxFileNumber()
    {
        $allowedMaxFileNumber = $this->getConfig('allowedMaxFileNumber');

        // if there is a has_one relation with that name on the record and
        // allowedMaxFileNumber has not been set, it's wanted to be 1
        if (empty($allowedMaxFileNumber)) {
            $record = $this->getRecord();
            $name = $this->getName();
            if ($record && DataObject::getSchema()->hasOneComponent(get_class($record), $name)) {
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
    public function setAllowedMaxFileNumber($allowedMaxFileNumber)
    {
        return $this->setConfig('allowedMaxFileNumber', $allowedMaxFileNumber);
    }

    /**
     * Determine if the user has permission to upload.
     *
     * @return boolean
     */
    public function canUpload()
    {
        if (!$this->isActive()) {
            return false;
        }
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
    public function setCanUpload($canUpload)
    {
        return $this->setConfig('canUpload', $canUpload);
    }

    /**
     * Determine if the user has permission to attach existing files
     * By default returns true if the user has the CMS_ACCESS_AssetAdmin permission
     *
     * @return boolean
     */
    public function canAttachExisting()
    {
        if (!$this->isActive()) {
            return false;
        }
        $can = $this->getConfig('canAttachExisting');
        return (is_bool($can)) ? $can : Permission::check($can);
    }

    /**
     * Returns true if the field is neither readonly nor disabled
     *
     * @return boolean
     */
    public function isActive()
    {
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
    public function setCanAttachExisting($canAttachExisting)
    {
        return $this->setConfig('canAttachExisting', $canAttachExisting);
    }

    /**
     * Gets thumbnail width. Defaults to 80
     *
     * @return integer
     */
    public function getPreviewMaxWidth()
    {
        return $this->getConfig('previewMaxWidth');
    }

    /**
     * @see UploadField::getPreviewMaxWidth()
     *
     * @param integer $previewMaxWidth
     * @return UploadField Self reference
     */
    public function setPreviewMaxWidth($previewMaxWidth)
    {
        return $this->setConfig('previewMaxWidth', $previewMaxWidth);
    }

    /**
     * Gets thumbnail height. Defaults to 60
     *
     * @return integer
     */
    public function getPreviewMaxHeight()
    {
        return $this->getConfig('previewMaxHeight');
    }

    /**
     * @see UploadField::getPreviewMaxHeight()
     *
     * @param integer $previewMaxHeight
     * @return UploadField Self reference
     */
    public function setPreviewMaxHeight($previewMaxHeight)
    {
        return $this->setConfig('previewMaxHeight', $previewMaxHeight);
    }

    /**
     * javascript template used to display uploading files
     * Defaults to 'ss-uploadfield-uploadtemplate'
     *
     * @see javascript/UploadField_uploadtemplate.js
     * @return string
     */
    public function getUploadTemplateName()
    {
        return $this->getConfig('uploadTemplateName');
    }

    /**
     * @see UploadField::getUploadTemplateName()
     *
     * @param string $uploadTemplateName
     * @return UploadField Self reference
     */
    public function setUploadTemplateName($uploadTemplateName)
    {
        return $this->setConfig('uploadTemplateName', $uploadTemplateName);
    }

    /**
     * javascript template used to display already uploaded files
     * Defaults to 'ss-downloadfield-downloadtemplate'
     *
     * @see javascript/DownloadField_downloadtemplate.js
     * @return string
     */
    public function getDownloadTemplateName()
    {
        return $this->getConfig('downloadTemplateName');
    }

    /**
     * @see Uploadfield::getDownloadTemplateName()
     *
     * @param string $downloadTemplateName
     * @return Uploadfield Self reference
     */
    public function setDownloadTemplateName($downloadTemplateName)
    {
        return $this->setConfig('downloadTemplateName', $downloadTemplateName);
    }

    /**
     * FieldList $fields for the EditForm
     * @example 'getCMSFields'
     *
     * @param DataObject $file File context to generate fields for
     * @return FieldList List of form fields
     */
    public function getFileEditFields(DataObject $file)
    {
        // Empty actions, generate default
        if (empty($this->fileEditFields)) {
            $fields = $file->getCMSFields();
            // Only display main tab, to avoid overly complex interface
            if ($fields->hasTabSet() && ($mainTab = $fields->findOrMakeTab('Root.Main'))) {
                $fields = $mainTab->Fields();
            }
            return $fields;
        }

        // Fields instance
        if ($this->fileEditFields instanceof FieldList) {
            return $this->fileEditFields;
        }

        // Method to call on the given file
        if ($file->hasMethod($this->fileEditFields)) {
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
    public function setFileEditFields($fileEditFields)
    {
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
    public function getFileEditActions(DataObject $file)
    {
        // Empty actions, generate default
        if (empty($this->fileEditActions)) {
            $actions = new FieldList($saveAction = new FormAction('doEdit', _t('UploadField.DOEDIT', 'Save')));
            $saveAction->addExtraClass('ss-ui-action-constructive icon-accept');
            return $actions;
        }

        // Actions instance
        if ($this->fileEditActions instanceof FieldList) {
            return $this->fileEditActions;
        }

        // Method to call on the given file
        if ($file->hasMethod($this->fileEditActions)) {
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
    public function setFileEditActions($fileEditActions)
    {
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
    public function getFileEditValidator(DataObject $file)
    {
        // Empty validator
        if (empty($this->fileEditValidator)) {
            return null;
        }

        // Validator instance
        if ($this->fileEditValidator instanceof Validator) {
            return $this->fileEditValidator;
        }

        // Method to call on the given file
        if ($file->hasMethod($this->fileEditValidator)) {
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
    public function setFileEditValidator($fileEditValidator)
    {
        $this->fileEditValidator = $fileEditValidator;
        return $this;
    }

    /**
     *
     * @param File|AssetContainer $file
     * @return string URL to thumbnail
     */
    protected function getThumbnailURLForFile(AssetContainer $file)
    {
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
        if ($file->hasMethod('getIcon')) {
            return $file->getIcon();
        }
        return null;
    }

    public function getAttributes()
    {
        return array_merge(
            parent::getAttributes(),
            array(
                'type' => 'file',
                'data-selectdialog-url' => $this->Link('select')
            )
        );
    }

    public function extraClass()
    {
        if ($this->isDisabled()) {
            $this->addExtraClass('disabled');
        }
        if ($this->isReadonly()) {
            $this->addExtraClass('readonly');
        }

        return parent::extraClass();
    }

    public function Field($properties = array())
    {
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
            if ($allowedMaxFileNumber > 1) {
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
    public function validate($validator)
    {
        $name = $this->getName();
        $files = $this->getItems();

        // If there are no files then quit
        if ($files->count() == 0) {
            return true;
        }

        // Check max number of files
        $maxFiles = $this->getAllowedMaxFileNumber();
        if ($maxFiles && ($files->count() > $maxFiles)) {
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
        foreach ($files as $file) {
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
        if ($errors = $this->upload->getErrors()) {
            foreach ($errors as $error) {
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
    public function handleItem(HTTPRequest $request)
    {
        return $this->getItemHandler($request->param('ID'));
    }

    /**
     * @param int $itemID
     * @return UploadField_ItemHandler
     */
    public function getItemHandler($itemID)
    {
        return UploadField_ItemHandler::create($this, $itemID);
    }

    /**
     * @param HTTPRequest $request
     * @return UploadField_SelectHandler
     */
    public function handleSelect(HTTPRequest $request)
    {
        if (!$this->canAttachExisting()) {
            return $this->httpError(403);
        }
        return UploadField_SelectHandler::create($this, $this->getFolderName());
    }

    /**
     * Safely encodes the File object with all standard fields required
     * by the front end
     *
     * @param File|AssetContainer $file Object which contains a file
     * @return array Array encoded list of file attributes
     */
    protected function encodeFileAttributes(AssetContainer $file)
    {
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
    public function upload(HTTPRequest $request)
    {
        if ($this->isDisabled() || $this->isReadonly() || !$this->canUpload()) {
            return $this->httpError(403);
        }

        // Protect against CSRF on destructive action
        $token = $this->getForm()->getSecurityToken();
        if (!$token->checkRequest($request)) {
            return $this->httpError(400);
        }

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
            if (empty($file)) {
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
    public function attach(HTTPRequest $request)
    {
        if (!$request->isPOST()) {
            return $this->httpError(403);
        }
        if (!$this->canAttachExisting()) {
            return $this->httpError(403);
        }

        // Retrieve file attributes required by front end
        $return = array();
        $files = File::get()->byIDs($request->postVar('ids'));
        foreach ($files as $file) {
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
    protected function checkFileExists($originalFile)
    {

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
    public function fileexists(HTTPRequest $request)
    {
        // Assert that requested filename doesn't attempt to escape the directory
        $originalFile = $request->requestVar('filename');
        if ($originalFile !== basename($originalFile)) {
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
        if (!empty($return['error'])) {
            $response->setStatusCode(400);
        }
        return $response;
    }

    public function performReadonlyTransformation()
    {
        $clone = clone $this;
        $clone->addExtraClass('readonly');
        $clone->setReadonly(true);
        return $clone;
    }
}
