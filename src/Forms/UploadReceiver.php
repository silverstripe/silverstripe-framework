<?php


namespace SilverStripe\Forms;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Upload;
use SilverStripe\Assets\Upload_Validator;
use SilverStripe\Core\Convert;

/**
 * Represents a form field which has an Upload() instance and can upload to a folder
 *
 * Note that this trait expects to be applied to a {@see FormField} class
 *
 * @mixin FormField
 */
trait UploadReceiver
{
    /**
     * Upload object (needed for validation
     * and actually moving the temporary file
     * created by PHP).
     *
     * @var Upload
     */
    protected $upload;

    /**
     * Partial filesystem path relative to /assets directory.
     * Defaults to Upload::$uploads_folder.
     *
     * @var string
     */
    protected $folderName = false;

    /**
     * Bootstrap Uploadable field
     */
    protected function constructUploadReceiver()
    {
        // Set Upload instance
        $this->setUpload(Upload::create());

        // filter out '' since this would be a regex problem on JS end
        $this->getValidator()->setAllowedExtensions(
            array_filter(File::config()->allowed_extensions ?? [])
        );
    }

    /**
     * Retrieves the Upload handler
     *
     * @return Upload
     */
    public function getUpload()
    {
        return $this->upload;
    }

    /**
     * Sets the upload handler
     *
     * @param Upload $upload
     * @return $this Self reference
     */
    public function setUpload(Upload $upload)
    {
        $this->upload = $upload;
        return $this;
    }

    /**
     * Limit allowed file extensions. Empty by default, allowing all extensions.
     * To allow files without an extension, use an empty string.
     * See {@link File::$allowed_extensions} to get a good standard set of
     * extensions that are typically not harmful in a webserver context.
     * See {@link setAllowedMaxFileSize()} to limit file size by extension.
     *
     * @param array $rules List of extensions
     * @return $this
     */
    public function setAllowedExtensions($rules)
    {
        $this->getValidator()->setAllowedExtensions($rules);
        return $this;
    }

    /**
     * Limit allowed file extensions by specifying categories of file types.
     * These may be 'image', 'image/supported', 'audio', 'video', 'archive', 'flash', or 'document'
     * See {@link File::$allowed_extensions} for details of allowed extensions
     * for each of these categories
     *
     * @param string $category Category name
     * @param string ...$categories Additional category names
     * @return $this
     */
    public function setAllowedFileCategories($category)
    {
        $extensions = File::get_category_extensions(func_get_args());
        return $this->setAllowedExtensions($extensions);
    }

    /**
     * Returns list of extensions allowed by this field, or an empty array
     * if there is no restriction
     *
     * @return array
     */
    public function getAllowedExtensions()
    {
        return $this->getValidator()->getAllowedExtensions();
    }

    /**
     * Get custom validator for this field
     *
     * @return Upload_Validator
     */
    public function getValidator()
    {
        return $this->getUpload()->getValidator();
    }

    /**
     * Set custom validator for this field
     *
     * @param Upload_Validator $validator
     * @return $this
     */
    public function setValidator(Upload_Validator $validator)
    {
        $this->getUpload()->setValidator($validator);
        return $this;
    }

    /**
     * Sets the upload folder name
     *
     * @param string $folderName
     * @return $this
     */
    public function setFolderName($folderName)
    {
        $this->folderName = $folderName;
        return $this;
    }

    /**
     * Gets the upload folder name
     *
     * @return string
     */
    public function getFolderName()
    {
        return ($this->folderName !== false)
            ? $this->folderName
            : Upload::config()->uploads_folder;
    }
}
