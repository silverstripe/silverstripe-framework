<?php

/**
 * A composite form field which allows users to select a folder into which files may be uploaded
 *
 * @package framework
 * @subpackage forms
 */
class SelectUploadField extends UploadField {

	private static $url_handlers = array(
		'folder/tree/$ID' => 'tree'
	);

	private static $allowed_actions = array(
		'tree'
	);

	protected $selectField;

	public function __construct($name, $title = null, \SS_List $items = null) {
		parent::__construct($name, $title, $items);

		$this->selectField = FolderDropdownField::create("{$name}/folder")
			->addExtraClass('FolderSelector')
			->setTitle('Select a folder to upload into');
	}

	public function FolderSelector() {
		return $this->selectField;
	}

	public function tree($request) {
		return $this->FolderSelector()->tree($request);
	}

	public function setForm($form) {
		parent::setForm($form);
		$this->selectField->setForm($form);
	}

	public function Type() {
		return 'selectupload upload';
	}

	protected function updateFolderName($request) {
		// Get path from upload
		$folderID = $request->requestVar("{$this->Name}/folder");
		if($folderID && ($folder = Folder::get()->byID($folderID))) {
			$path = $folder->getFilename();
			if(stripos($path, ASSETS_DIR) === 0) {
				$path = substr($path, strlen(ASSETS_DIR) + 1);
			}
			$this->setFolderName($path);
			FolderDropdownField::set_last_folder($folderID);
		}
	}

	public function handleRequest(\SS_HTTPRequest $request, \DataModel $model) {
		$this->updateFolderName($request);
		return parent::handleRequest($request, $model);
	}
}
