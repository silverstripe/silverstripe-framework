<?php

namespace SilverStripe\Filesystem\Storage;

use SilverStripe\Filesystem\Thumbnail;
use SilverStripe\Filesystem\ImageManipulation;


use Injector;
use AssetField;
use File;
use Director;



use SilverStripe\ORM\ValidationResult;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\FieldType\DBComposite;
use SilverStripe\Security\Permission;



/**
 * Represents a file reference stored in a database
 *
 * @property string $Hash SHA of the file
 * @property string $Filename Name of the file, including directory
 * @property string $Variant Variant of the file
 *
 * @package framework
 * @subpackage filesystem
 */
class DBFile extends DBComposite implements AssetContainer, Thumbnail {

	use ImageManipulation;

	/**
	 * List of allowed file categories.
	 *
	 * {@see File::$app_categories}
	 *
	 * @var array
	 */
	protected $allowedCategories = array();

	/**
	 * List of image mime types supported by the image manipulations API
	 *
	 * {@see File::app_categories} for matching extensions.
	 *
	 * @config
	 * @var array
	 */
	private static $supported_images = array(
		'image/jpeg',
		'image/gif',
		'image/png'
	);

	/**
	 * Create a new image manipulation
	 *
	 * @param string $name
	 * @param array|string $allowed List of allowed file categories (not extensions), as per File::$app_categories
	 */
	public function __construct($name = null, $allowed = array()) {
		parent::__construct($name);
		$this->setAllowedCategories($allowed);
	}

	/**
	 * Determine if a valid non-empty image exists behind this asset, which is a format
	 * compatible with image manipulations
	 *
	 * @return boolean
	 */
	public function getIsImage() {
		// Check file type
		$mime = $this->getMimeType();
		return $mime && in_array($mime, $this->config()->supported_images);
	}

	/**
	 * @return AssetStore
	 */
	protected function getStore() {
		return Injector::inst()->get('AssetStore');
	}

	private static $composite_db = array(
		"Hash" => "Varchar(255)", // SHA of the base content
		"Filename" => "Varchar(255)", // Path identifier of the base content
		"Variant" => "Varchar(255)", // Identifier of the variant to the base, if given
	);

	private static $casting = array(
		'URL' => 'Varchar',
		'AbsoluteURL' => 'Varchar',
		'Basename' => 'Varchar',
		'Title' => 'Varchar',
		'MimeType' => 'Varchar',
		'String' => 'Text',
		'Tag' => 'HTMLText',
		'Size' => 'Varchar'
	);

	public function scaffoldFormField($title = null, $params = null) {
		return AssetField::create($this->getName(), $title);
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
		$template = $this->getFrontendTemplate();
		if(empty($template)) {
			return '';
		}
		return (string)$this->renderWith($template);
	}

	/**
	 * Determine the template to render as on the frontend
	 *
	 * @return string Name of template
	 */
	public function getFrontendTemplate() {
		// Check that path is available
		$url = $this->getURL();
		if(empty($url)) {
			return null;
		}

		// Image template for supported images
		if($this->getIsImage()) {
			return 'DBFile_image';
		}

		// Default download
		return 'DBFile_download';
	}

	/**
	 * Get trailing part of filename
	 *
	 * @return string
	 */
	public function getBasename() {
		if(!$this->exists()) {
			return null;
		}
		return basename($this->getSourceURL());
	}

	/**
	 * Get file extension
	 *
	 * @return string
	 */
	public function getExtension() {
		if(!$this->exists()) {
			return null;
		}
		return pathinfo($this->Filename, PATHINFO_EXTENSION);
	}

	/**
	 * Alt title for this
	 *
	 * @return string
	 */
	public function getTitle() {
		// If customised, use the customised title
		if($this->failover && ($title = $this->failover->Title)) {
			return $title;
		}
		// fallback to using base name
		return $this->getBasename();
	}

	public function setFromLocalFile($path, $filename = null, $hash = null, $variant = null, $config = array()) {
		$this->assertFilenameValid($filename ?: $path);
		$result = $this
			->getStore()
			->setFromLocalFile($path, $filename, $hash, $variant, $config);
		// Update from result
		if($result) {
			$this->setValue($result);
		}
		return $result;
	}

	public function setFromStream($stream, $filename, $hash = null, $variant = null, $config = array()) {
		$this->assertFilenameValid($filename);
		$result = $this
			->getStore()
			->setFromStream($stream, $filename, $hash, $variant, $config);
		// Update from result
		if($result) {
			$this->setValue($result);
		}
		return $result;
	}

	public function setFromString($data, $filename, $hash = null, $variant = null, $config = array()) {
		$this->assertFilenameValid($filename);
		$result = $this
			->getStore()
			->setFromString($data, $filename, $hash, $variant, $config);
		// Update from result
		if($result) {
			$this->setValue($result);
		}
		return $result;
	}

	public function getStream() {
		if(!$this->exists()) {
			return null;
		}
		return $this
			->getStore()
			->getAsStream($this->Filename, $this->Hash, $this->Variant);
	}

	public function getString() {
		if(!$this->exists()) {
			return null;
		}
		return $this
			->getStore()
			->getAsString($this->Filename, $this->Hash, $this->Variant);
	}

	public function getURL($grant = true) {
		if(!$this->exists()) {
			return null;
		}
		$url = $this->getSourceURL($grant);
		$this->updateURL($url);
		$this->extend('updateURL', $url);
		return $url;
	}

	/**
	 * Get URL, but without resampling.
	 * Note that this will return the url even if the file does not exist.
	 *
	 * @param bool $grant Ensures that the url for any protected assets is granted for the current user.
	 * @return string
	 */
	public function getSourceURL($grant = true) {
		return $this
			->getStore()
			->getAsURL($this->Filename, $this->Hash, $this->Variant, $grant);
	}

	/**
	 * Get the absolute URL to this resource
	 *
	 * @return string
	 */
	public function getAbsoluteURL() {
		if(!$this->exists()) {
			return null;
		}
		return Director::absoluteURL($this->getURL());
	}

	public function getMetaData() {
		if(!$this->exists()) {
			return null;
		}
		return $this
			->getStore()
			->getMetadata($this->Filename, $this->Hash, $this->Variant);
	}

	public function getMimeType() {
		if(!$this->exists()) {
			return null;
		}
		return $this
			->getStore()
			->getMimeType($this->Filename, $this->Hash, $this->Variant);
	}

	public function getValue() {
		if(!$this->exists()) {
			return null;
		}
		return array(
			'Filename' => $this->Filename,
			'Hash' => $this->Hash,
			'Variant' => $this->Variant
		);
	}

	public function getVisibility() {
		if(empty($this->Filename)) {
			return null;
		}
		return $this
			->getStore()
			->getVisibility($this->Filename, $this->Hash);
	}

	public function exists() {
		if(empty($this->Filename)) {
			return false;
		}
		return $this
			->getStore()
			->exists($this->Filename, $this->Hash, $this->Variant);
	}

	public function getFilename() {
		return $this->getField('Filename');
	}

	public function getHash() {
		return $this->getField('Hash');
	}

	public function getVariant() {
		return $this->getField('Variant');
	}

	/**
	 * Return file size in bytes.
	 *
	 * @return int
	 */
	public function getAbsoluteSize() {
		$metadata = $this->getMetaData();
		if(isset($metadata['size'])) {
			return $metadata['size'];
		}
		return 0;
	}

	/**
	 * Customise this object with an "original" record for getting other customised fields
	 *
	 * @param AssetContainer $original
	 * @return $this
	 */
	public function setOriginal($original) {
		$this->failover = $original;
		return $this;
	}

	/**
	 * Get list of allowed file categories
	 *
	 * @return array
	 */
	public function getAllowedCategories() {
		return $this->allowedCategories;
	}

	/**
	 * Assign allowed categories
	 *
	 * @param array|string $categories
	 * @return $this
	 */
	public function setAllowedCategories($categories) {
		if(is_string($categories)) {
			$categories = preg_split('/\s*,\s*/', $categories);
		}
		$this->allowedCategories = (array)$categories;
		return $this;
	}

	/**
	 * Gets the list of extensions (if limited) for this field. Empty list
	 * means there is no restriction on allowed types.
	 *
	 * @return array
	 */
	protected function getAllowedExtensions() {
		$categories = $this->getAllowedCategories();
		return File::get_category_extensions($categories);
	}

	/**
	 * Validate that this DBFile accepts this filename as valid
	 *
	 * @param string $filename
	 * @throws ValidationException
	 * @return bool
	 */
	protected function isValidFilename($filename) {
		$extension = strtolower(File::get_file_extension($filename));

		// Validate true if within the list of allowed extensions
		$allowed = $this->getAllowedExtensions();
		if($allowed) {
			return in_array($extension, $allowed);
		}

		// If no extensions are configured, fallback to global list
		$globalList = File::config()->allowed_extensions;
		if(in_array($extension, $globalList)) {
			return true;
		}

		// Only admins can bypass global rules
		return !File::config()->apply_restrictions_to_admin && Permission::check('ADMIN');
	}

	/**
	 * Check filename, and raise a ValidationException if invalid
	 *
	 * @param string $filename
	 * @throws ValidationException
	 */
	protected function assertFilenameValid($filename) {
		$result = new ValidationResult();
		$this->validate($result, $filename);
		if(!$result->valid()) {
			throw new ValidationException($result);
		}
	}


	/**
	 * Hook to validate this record against a validation result
	 *
	 * @param ValidationResult $result
	 * @param string $filename Optional filename to validate. If omitted, the current value is validated.
	 * @return bool Valid flag
	 */
	public function validate(ValidationResult $result, $filename = null) {
		if(empty($filename)) {
			$filename = $this->getFilename();
		}
		if(empty($filename) || $this->isValidFilename($filename)) {
			return true;
		}

		// Check allowed extensions
		$extensions = $this->getAllowedExtensions();
		if(empty($extensions)) {
			$extensions = File::config()->allowed_extensions;
		}
		sort($extensions);
		$message = _t(
			'File.INVALIDEXTENSION',
			'Extension is not allowed (valid: {extensions})',
			'Argument 1: Comma-separated list of valid extensions',
			array('extensions' => wordwrap(implode(', ',$extensions)))
		);
		$result->error($message);
		return false;
	}

	public function setField($field, $value, $markChanged = true) {
		// Catch filename validation on direct assignment
		if($field === 'Filename' && $value) {
			$this->assertFilenameValid($value);
		}

		return parent::setField($field, $value, $markChanged);
	}


	/**
	 * Returns the size of the file type in an appropriate format.
	 *
	 * @return string|false String value, or false if doesn't exist
	 */
	public function getSize() {
		$size = $this->getAbsoluteSize();
		if($size) {
			return \File::format_size($size);
		}
		return false;
	}

	public function deleteFile() {
		if(!$this->Filename) {
			return false;
		}

		return $this
			->getStore()
			->delete($this->Filename, $this->Hash);
	}

	public function publishFile() {
		if($this->Filename) {
			$this
				->getStore()
				->publish($this->Filename, $this->Hash);
		}
	}

	public function protectFile() {
		if($this->Filename) {
			$this
				->getStore()
				->protect($this->Filename, $this->Hash);
		}
	}

	public function grantFile() {
		if($this->Filename) {
			$this
				->getStore()
				->grant($this->Filename, $this->Hash);
		}
	}

	public function revokeFile() {
		if($this->Filename) {
			$this
				->getStore()
				->revoke($this->Filename, $this->Hash);
		}
	}

	public function canViewFile() {
		return $this->Filename
			&& $this
				->getStore()
				->canView($this->Filename, $this->Hash);
	}
}
