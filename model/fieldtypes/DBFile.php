<?php

use SilverStripe\Filesystem\Storage\AssetContainer;
use SilverStripe\Filesystem\Storage\AssetStore;

/**
 * Represents a file reference stored in a database
 *
 * @property string $Hash SHA of the file
 * @property string $Filename Name of the file, including directory
 * @property string $Variant Variant of the file
 *
 * @package framework
 * @subpackage model
 */
class DBFile extends CompositeDBField implements AssetContainer {

	/**
	 * @return AssetStore
	 */
	protected function getStore() {
		return Injector::inst()->get('AssetStore');
	}

	/**
	 * Mapping of mime patterns to templates to use
	 */
	private static $templates = array(
		'/image\\/.+/' => 'DBFile_image',
		'/.+/' => 'DBFile_download'
	);

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
		'Tag' => 'HTMLText'
	);

	public function scaffoldFormField($title = null, $params = null) {
		// @todo - This doesn't actually work with DBFile yet
		return new UploadField($this->getName(), $title);
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
		// Check mime type
		$mime = $this->getMimeType();
		if(empty($mime)) {
			return '';
		}

		// Check that path is available
		$url = $this->getURL();
		if(empty($url)) {
			return '';
		}

		$template = $this->getTemplateForMime($mime);
		if(empty($template)) {
			return '';
		}

		// Render
		return (string)$this->renderWith($template);
	}

	/**
	 * Given a mime type, determine the template to render as on the frontend
	 *
	 * @param string $mimetype
	 * @return string Name of template
	 */
	protected function getTemplateForMime($mimetype) {
		foreach($this->config()->templates as $pattern => $template) {
			if($pattern === $mimetype || preg_match($pattern, $mimetype)) {
				return $template;
			}
		}
		return null;
	}

	/**
	 * Get trailing part of filename
	 *
	 * @return string
	 */
	public function getBasename() {
		// @todo - add variant onto this ?
		if($this->Filename) {
			return basename($this->Filename);
		}
	}

	/**
	 * Alt title for this
	 *
	 * @return string
	 */
	public function getTitle() {
		// @todo - better solution?
		return $this->getBasename();
	}

	public function setFromLocalFile($path, $filename = null, $conflictResolution = null) {
		$result = $this
			->getStore()
			->setFromLocalFile($path, $filename, $conflictResolution);
		// Update from result
		if($result) {
			$this->setValue($result);
		}
		return $result;
	}

	public function setFromStream($stream, $filename, $conflictResolution = null) {
		$result = $this
			->getStore()
			->setFromStream($stream, $filename, $conflictResolution);
		// Update from result
		if($result) {
			$this->setValue($result);
		}
		return $result;
	}

	public function setFromString($data, $filename, $conflictResolution = null) {
		$result = $this
			->getStore()
			->setFromString($data, $filename, $conflictResolution);
		// Update from result
		if($result) {
			$this->setValue($result);
		}
		return $result;
	}

	public function getStream() {
		return $this
			->getStore()
			->getAsStream($this->Hash, $this->Filename, $this->Variant);
	}

	public function getString() {
		return $this
			->getStore()
			->getAsString($this->Hash, $this->Filename, $this->Variant);
	}

	public function getURL() {
		return $this
			->getStore()
			->getAsURL($this->Hash, $this->Filename, $this->Variant);
	}

	/**
	 * Get the absolute URL to this resource
	 *
	 * @return type
	 */
	public function getAbsoluteURL() {
		return Director::absoluteURL($this->getURL());
	}

	public function getMetaData() {
		return $this
			->getStore()
			->getMetadata($this->Hash, $this->Filename, $this->Variant);
	}

	public function getMimeType() {
		return $this
			->getStore()
			->getMimeType($this->Hash, $this->Filename, $this->Variant);
	}

	public function exists() {
		return !empty($this->Filename);
	}
}
