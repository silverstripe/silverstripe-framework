<?php

/**
 * Field for uploading single or multiple files of all types, including images.<br><b>NOTE: this Field will call write() on the supplied record</b><br><b>Features (some might not be avaliable to old browsers):</b><ul><li>File Drag&Drop support<li>Progressbar<li>Image thumbnail/file icons even before upload finished<li>Saving into relations<li>Edit file<li>allowedExtensions is by default File::$allowed_extensions<li>maxFileSize the vaule of min(upload_max_filesize, post_max_size) from php.ini</ul>
 * 
 * @example <code>$UploadField = new UploadField('myFiles', 'please upload some images <span>max 5 files</span>');<br>$UploadField->getValidator()->setAllowedExtensions(array('jpg', 'jpeg', 'png', 'gif'));<br>$UploadField->setConfig('allowedMaxFileNumber', 5);</code>
 * @author Zauberfisch
 * @package sapphire
 * @subpackage forms
 */
class UploadField extends FileField {
	protected $template = 'UploadField';
	protected $templateFileButtons = 'UploadField_FileButtons';
	protected $templateFileEdit = 'UploadField_FileEdit';
	protected $record;
	protected $items;
	/**
	 * Config for this field used in both, php and javascript (will be merged into the config of the javascript file upload plugin)
	 * @var array
	 */
	protected $config = array(
		/**
		 * @var boolean
		 */
		'autoUpload' => true,
		/**
		 * php validation of allowedMaxFileNumber only works when a db relation is avaliable, set to null to allow unlimited
		 * if record has a has_one and allowedMaxFileNumber is null, it will be set to 1
		 * @var int
		 */
		'allowedMaxFileNumber' => null,
		/**
		 * @var int
		 */
		'previewMaxWidth' => 80,
		/**
		 * @var int
		 */
		'previewMaxHeight' => 60,
		/**
		 * javascript template used to display uploading files
		 * @see javascript/UploadField_uploadtemplate.js
		 * @var string
		 */
		'uploadTemplateName' => 'ss-uploadfield-uploadtemplate',
		/**
		 * javascript template used to display already uploaded files
		 * @see javascript/UploadField_downloadtemplate.js
		 * @var string
		 */
		'downloadTemplateName' => 'ss-uploadfield-downloadtemplate',
		/**
		 * FieldList $fields or string $name (of a method on File to provide a fields) for the EditForm
		 * @example 'getCMSFields'
		 * @var FieldList|string
		 */
		'fileEditFields' => null,
		/**
		 * FieldList $actions or string $name (of a method on File to provide a actions) for the EditForm
		 * @example 'getCMSActions'
		 * @var FieldList|string
		 */
		'fileEditActions' => null,
		/**
		 * Validator (eg RequiredFields) or string $name (of a method on File to provide a Validator) for the EditForm
		 * @example 'getCMSValidator'
		 * @var string
		 */
		'fileEditValidator' => null
	);
	/**
	 * Set name of template used for Buttons on each file (replace, edit, remove, delete) (without path or extension)
	 * 
	 * @param String
	 */
	public function setTemplateFileButtons($template) {
		$this->templateFileButtons = $template;
		return $this;
	}
	/**
	 * @return String
	 */
	public function getTemplateFileButtons() {
		return $this->templateFileButtons;
	}
	/**
	 * Set name of template used for the edit (inline & popup) of a file file (without path or extension)
	 * 
	 * @param String
	 */
	public function setTemplateFileEdit($template) {
		$this->templateFileEdit = $template;
		return $this;
	}
	/**
	 * @return String
	 */
	public function getTemplateFileEdit() {
		return $this->templateFileEdit;
	}
	/**
	 * Force a record to be used as "Parent" for uploaded Files (eg a Page with a has_one to File)
	 * @param DataOjbect $record
	 */
	public function setRecord($record) {
		$this->record = $record;
		return $this;
	}
	/**
	 * Get the record to use as "Parent" for uploaded Files (eg a Page with a has_one to File) If none is set, it will use Form->getRecord() or Form->Controller()->data()
	 * @return DataObject
	 */
	public function getRecord() {
		if (!$this->record && $this->form) {
			if ($this->form->getRecord() && is_a($this->form->getRecord(), 'DataObject')) {
				$this->record = $this->form->getRecord();
			} elseif ($this->form->Controller() && $this->form->Controller()->hasMethod('data') 
					&& $this->form->Controller()->data() && is_a($this->form->Controller()->data(), 'DataObject')) {
				$this->record = $this->form->Controller()->data();
			}
		}
		return $this->record;
	}
	/**
	 * @param SS_List $items
	 */
	public function setItems(SS_List $items) { 
		$this->items = $items; 
		return $this;
	}
	/**
	 * @return SS_List
	 */
	public function getItems() {
		if (!$this->items || !$this->items->exists()) {
			$record = $this->getRecord();
			$this->items = array();
			if ($record && $record->exists()) {
				if ($record->has_many($this->getName()) || $record->many_many($this->getName())) {
					$this->items = $record->{$this->getName()}()->toArray();
				} elseif($record->has_one($this->getName())) {
					$item = $record->{$this->getName()}();
					if ($item && $item->exists())
						$this->items = array($record->{$this->getName()}());
				}
			}
			$this->items = new ArrayList($this->items);
			// hack to provide $UploadFieldThumbnailURL, $hasRelation and $UploadFieldEditLink in template for each file
			if ($this->items->exists()) {
				foreach ($this->items as $i=>$file) {
					$this->items[$i] = $this->customiseFile($file);	
				}
			}
		}
		return $this->items;
	}
	/**
	 * Hack to add some Variables and a dynamic template to a File
	 * @param File $file
	 * @param bool [$hasRelation] has this file a relation to the record the file is on?
	 * @return ViewableData_Customised
	 */
	protected function customiseFile(File $file, $hasRelation = true) {
		$file = $file->customise(array(
			'UploadFieldHasRelation' => $hasRelation,
			'UploadFieldThumbnailURL' => $this->getThumbnailURLForFile($file),
			'UploadFieldRemoveLink' => $this->getItemHandler($file->ID)->RemoveLink(),
			'UploadFieldDeleteLink' => $this->getItemHandler($file->ID)->DeleteLink(),
			'UploadFieldEditLink' => $this->getItemHandler($file->ID)->EditLink()
		));
		// we do this in a second customise to have the access to the previous customisations
		return $file->customise(array(
			'UploadFieldFileButtons' => $file->renderWith($this->getTemplateFileButtons())
		));
	}
	/**
	 * @param string $key
	 * @param mixed $val
	 */
	public function setConfig($key, $val) {
		$this->config[$key] = $val;
		return $this;
	}
	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getConfig($key) {
		return $this->config[$key];
	}
	/**
	 * @param File $file
	 * @return string
	 */
	protected function getThumbnailURLForFile(File $file) {
		if ($file && $file->exists() && file_exists(Director::baseFolder() . '/' . $file->getFilename())) {
			if ($file->hasMethod('getThumbnail')) {
				return $file->getThumbnail($this->getConfig('previewMaxWidth'), $this->getConfig('previewMaxHeight'))->getURL();
			} elseif ($file->hasMethod('getThumbnailURL')) {
				return $file->getThumbnailURL($this->getConfig('previewMaxWidth'), $this->getConfig('previewMaxHeight'));
			} elseif ($file->hasMethod('SetRatioSize')) {
				return $file->SetRatioSize($this->getConfig('previewMaxWidth'), $this->getConfig('previewMaxHeight'))->getURL();
			} else {
				return $file->Icon();
			}
		}
		return false;
	}
	/**
	 * Field for uploading single or multiple files of all types, including images.<br><b>NOTE: this Field will call write() on the supplied record</b><br><b>Features (some might not be avaliable to old browsers):</b><ul><li>File Drag&Drop support<li>Progressbar<li>Image thumbnail/file icons even before upload finished<li>Saving into relations<li>Edit file<li>allowedExtensions is by default File::$allowed_extensions<li>maxFileSize the vaule of min(upload_max_filesize, post_max_size) from php.ini</ul>
	 * 
	 * @example <code>$UploadField = new UploadField('myFiles', 'please upload some images <span>max 5 files</span>');<br>$UploadField->getValidator()->setAllowedExtensions(array('jpg', 'jpeg', 'png', 'gif'));<br>$UploadField->setConfig('allowedMaxFileNumber', 5);</code>
	 * @param string $name The internal field name, passed to forms.
	 * @param string $title The field label.
	 * @param SS_List $items
	 * @param Form $form Reference to the container form
	 * @param string $rightTitle Used in SmallFieldHolder() to force a right-aligned label
	 */
	public function __construct($name, $title = null, SS_List $items = null, Form $form = null, $rightTitle = null) {
		// TODO thats the first thing that came to my head, feel free to change it
		$this->addExtraClass('ss-upload'); // class, used by js
		$this->addExtraClass('ss-uploadfield'); // class, used by css for uploadfield only
		parent::__construct($name, $title, null, $form, $rightTitle);
		if ($items)
			$this->setItems($items);
		$this->getValidator()->setAllowedExtensions(array_filter(File::$allowed_extensions)); // filter out '' since this would be a regex problem on JS end
		$this->getValidator()->setAllowedMaxFileSize(min(File::ini2bytes(ini_get('upload_max_filesize')), File::ini2bytes(ini_get('post_max_size')))); // get the lower max size
	}
	/**
	 * Set configs to AssetUploadField
	 * return UploadField $this
	 */
	public function performAssetUploadFieldTransformation() {
		$this->setConfig('previewMaxWidth', 40);
		$this->setConfig('previewMaxHeight', 30);
		$this->addExtraClass('ss-assetuploadfield');
		$this->removeExtraClass('ss-uploadfield');
		$this->setTemplate('AssetUploadField');
		Requirements::css(SAPPHIRE_DIR . '/css/AssetUploadField.css');
		return $this;
	}
	public function Field() {
		$record = $this->getRecord();
		if ($record && $record->exists()) {
			if (!$record->has_many($this->getName()) && !$record->many_many($this->getName()) && !$this->getConfig('allowedMaxFileNumber') && 
					((substr($this->getName(), -2) === 'ID' && $record->has_one(substr($this->getName(), 0, -2))) || $record->has_one($this->getName()))) {
				// if there is a has_one relation with that name on the record and allowedMaxFileNumber has not been set, its wanted to be 1
				$this->setConfig('allowedMaxFileNumber', 1);
			}
		}
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/jquery_improvements.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery-ui.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/i18n.js');
		Requirements::javascript(SAPPHIRE_ADMIN_DIR . '/javascript/ssui.core.js');
		Requirements::javascript(THIRDPARTY_DIR . '/javascript-templates/tmpl.js');
		Requirements::javascript(THIRDPARTY_DIR . '/javascript-loadimage/load-image.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-fileupload/jquery.iframe-transport.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-fileupload/cors/jquery.xdr-transport.js');
		//Requirements::javascript(THIRDPARTY_DIR . '/jquery-fileupload/jquery.postmessage-transport.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-fileupload/jquery.fileupload.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-fileupload/jquery.fileupload-ui.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/UploadField_uploadtemplate.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/UploadField_downloadtemplate.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/UploadField.js');
		Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css'); // TODO hmmm, remove it?
		Requirements::css(SAPPHIRE_DIR . '/css/UploadField.css');

		$config = array(
			'url' => $this->Link('upload'),
			'acceptFileTypes' => '.+$',
			'maxNumberOfFiles' => $this->getConfig('allowedMaxFileNumber')
		);
		if (count($this->getValidator()->getAllowedExtensions())) {
			$allowedExtensions = $this->getValidator()->getAllowedExtensions();
			$config['acceptFileTypes'] = '(\.|\/)(' . implode('|', $allowedExtensions) . ')$';
			$config['errorMessages']['acceptFileTypes'] = sprintf(_t(
				'File.INVALIDEXTENSION', 
				'Extension is not allowed (valid: %s)'
			), wordwrap(implode(', ', $allowedExtensions)));
		}
		if ($this->getValidator()->getAllowedMaxFileSize()) {
			$config['maxFileSize'] = $this->getValidator()->getAllowedMaxFileSize();
			$config['errorMessages']['maxFileSize'] = sprintf(_t(
				'File.TOOLARGE', 
				'Filesize is too large, maximum %s allowed.'
			), File::format_size($config['maxFileSize']));
		}
		if ($config['maxNumberOfFiles'] > 1) {
			$config['errorMessages']['maxNumberOfFiles'] = sprintf(_t(
				'UploadField.MAXNUMBEROFFILES', 
				'Max number of %s file(s) exceeded.'
			), $config['maxNumberOfFiles']);
		}
		$configOverwrite = array();
		if (is_numeric($config['maxNumberOfFiles']) && $this->getItems()->count()) {
			$configOverwrite['maxNumberOfFiles'] = $config['maxNumberOfFiles'] - $this->getItems()->count();
		}
		$config = array_merge($config, $this->config, $configOverwrite);
		return $this->customise(array(
			'configString' => str_replace('"', "'", Convert::raw2json($config)),
			'config' => new ArrayData($config),
			'multiple' => $config['maxNumberOfFiles'] !== 1,
			'displayInput' => (!isset($configOverwrite['maxNumberOfFiles']) || $configOverwrite['maxNumberOfFiles'])
		))->renderWith($this->getTemplate());
	}
	/**
	 * Validation method for this field, called when the entire form is validated
	 * 
	 * @param $validator
	 * @return Boolean
	 */
	public function validate($validator) {
		return true;
	}
	/**
	 * @var array
	 */
	public static $allowed_actions = array(
		'upload',
		'handleItem'
	);
	/**
	 * @var array
	 */
	public static $url_handlers = array(
		'item/$ID' => 'handleItem',
		'$Action!' => '$Action',
	);
	/**
	 * @param SS_HTTPRequest $request
	 * @return UploadField_ItemHandler
	 */
	public function handleItem(SS_HTTPRequest $request) {
		return $this->getItemHandler($request->param('ID'));
	}
	/**
	 * @param int $itemID
	 * @return UploadField_ItemHandler
	 */
	public function getItemHandler($itemID) {
		return Object::create('UploadField_ItemHandler', $this, $itemID);
	}
	/**
	 * Action to handle upload of a single file
	 * 
	 * @param SS_HTTPRequest $request
	 * @return string json
	 */
	public function upload(SS_HTTPRequest $request) {
		$tmpfile = $request->postVar($this->getName());
		$record = $this->getRecord();
		if (!$tmpfile) {
			$return = array('error' => _t('UploadField.FIELDNOTSET', 'file infotmation not found'));
		} else {
			$return = array(
				'name' => $tmpfile['name'],
				'size' => $tmpfile['size'],
				'type' => $tmpfile['type'],
				'error' => $tmpfile['error']
			);
		}
		if (!$return['error'] && $record && $record->exists()) {
			$toManyFiles = false;
			if ($this->getConfig('allowedMaxFileNumber') && ($record->has_many($this->getName()) || $record->many_many($this->getName()))) {
				if(!$record->isInDB()) $record->write();
				$toManyFiles = $record->{$this->getName()}()->count() >= $this->getConfig('allowedMaxFileNumber');
			} elseif(substr($this->getName(), -2) === 'ID' && $record->has_one(substr($this->getName(), 0, -2))) {
				$toManyFiles = $record->{substr($this->getName(), 0, -2)}() && $record->{substr($this->getName(), 0, -2)}()->exists();
			} elseif($record->has_one($this->getName())) {
				$toManyFiles = $record->{$this->getName()}() && $record->{$this->getName()}()->exists();
			}
			if ($toManyFiles) {
				if (!$this->getConfig('allowedMaxFileNumber'))
					$this->setConfig('allowedMaxFileNumber', 1);
				$return['error'] = sprintf(_t(
					'UploadField.MAXNUMBEROFFILES', 
					'Max number of %s file(s) exceeded.'
				), $this->getConfig('allowedMaxFileNumber'));
			}
		}
		if (!$return['error']) {
			try {
				$this->upload->loadIntoFile($tmpfile, null, $this->folderName);
			} catch (Exception $e) {
				// we shouldn't get an error here, but just in case
				$return['error'] = $e->getMessage();
			}
			if (!$return['error']) {
				if ($this->upload->isError()) {
					$return['error'] = implode(' '.PHP_EOL, $this->upload->getErrors());
				} else {
					$file = $this->upload->getFile();
					$file->OwnerID = (Member::currentUser() ? Member::currentUser()->ID : 0);
					$file->write();
					$hasRelation = false;
					if ($record && $record->exists()) {
						if ($record->has_many($this->getName()) || $record->many_many($this->getName())) {
							if(!$record->isInDB()) $record->write();
							$record->{$this->getName()}()->add($file);
							$hasRelation = true;
						} elseif(substr($this->getName(), -2) === 'ID' && $record->has_one(substr($this->getName(), 0, -2))) {
							$record->{$this->getName()} = $file->ID;
							$record->write();
							$hasRelation = true;
						} elseif($record->has_one($this->getName())) {
							$record->{$this->getName() . 'ID'} = $file->ID;
							$record->write();
							$hasRelation = true;
						}
					}
					$file =  $this->customiseFile($file, $hasRelation);
					$return = array_merge($return, array(
						'id' => $file->ID,
						'name' => $file->getTitle() . '.' . $file->getExtension(),
						'url' => $file->getURL(),
						'thumbnail_url' => $file->UploadFieldThumbnailURL,
						'edit_url' => $file->UploadFieldEditLink,
						'size' => $file->getAbsoluteSize(),
						'buttons' => $file->UploadFieldFileButtons
					));
				}
			}
		}
		$response = new SS_HTTPResponse(Convert::raw2json(array($return)));
		$response->addHeader('Content-Type', 'text/plain');
		return $response;
	}
}
/**
 * RequestHandler for actions (edit, remove, delete) on a single item (File) of the UploadField
 * @author Zauberfisch
 * @package sapphire
 * @subpackage forms
 */
class UploadField_ItemHandler extends RequestHandler {
	/**
	 * @var UploadFIeld
	 */
	protected $parent;
	/**
	 * @var int FileID
	 */
	protected $itemID;
	public static $url_handlers = array(
		'$Action!' => '$Action',
		'' => 'index',
	);
	/**
	 * @return File
	 */
	function getItem() {
		return DataObject::get_by_id('File', $this->itemID);
	}
	/**
	 * @param UploadFIeld $parent
	 * @param int $item
	 */
	public function __construct($parent, $itemID) {
		$this->parent = $parent;
		$this->itemID = $itemID;
		parent::__construct();
	}
	/**
	 * @param string $action
	 * @return string
	 */
	public function Link($action = null) {
		return Controller::join_links($this->parent->Link(), '/item/', $this->itemID, $action);
	}
	/**
	 * @return string
	 */
	public function RemoveLink() {
		return $this->Link('remove');
	}
	/**
	 * @return string
	 */
	public function DeleteLink() {
		return $this->Link('delete');
	}
	/**
	 * @return string
	 */
	public function EditLink() {
		return $this->Link('edit');
	}
	/**
	 * Action to handle removeing a single file from the db relation
	 * 
	 * @param SS_HTTPRequest $request
	 * @return SS_HTTPResponse
	 */
	public function remove(SS_HTTPRequest $request) {
		$response = new SS_HTTPResponse();
		$response->setStatusCode(500);
		$fieldName = $this->parent->getName();
		$record = $this->parent->getRecord();
		$id = $this->getItem()->ID;
		if ($id && $record && $record->exists()) {
			if (($record->has_many($fieldName) || $record->many_many($fieldName)) && $file = $record->{$fieldName}()->byID($id)) {
				$record->{$fieldName}()->remove($file);
				$response->setStatusCode(200);
			} elseif(substr($fieldName, -2) === 'ID' && $record->has_one(substr($fieldName, 0, -2)) && $record->{$fieldName} == $id) {
				$record->{$fieldName} = 0;
				$record->write();
				$response->setStatusCode(200);
			} elseif($record->has_one($fieldName) && $record->{$fieldName . 'ID'} == $id) {
				$record->{$fieldName . 'ID'} = 0;
				$record->write();
				$response->setStatusCode(200);
			}
		}
		if ($response->getStatusCode() != 200)
			$response->setStatusDescription(_t('UploadField.REMOVEERROR', 'Error removing file'));
		return $response;
	}
	/**
	 * Action to handle deleting of a single file
	 * 
	 * @param SS_HTTPRequest $request
	 * @return SS_HTTPResponse
	 */
	public function delete(SS_HTTPRequest $request) {
		$this->remove($request);
		$response = new SS_HTTPResponse();
		$file = $this->getItem();
		if (!$file) {
			$response->setStatusCode(500);
			$response->setStatusDescription(_t('UploadField.DELETEERROR', 'Error deleting file'));
		} else {
			$file->delete();
			$response->setStatusCode(200);
		}
		return $response;
	}
	/**
	 * Action to handle editing of a single file
	 * 
	 * @param SS_HTTPRequest $request
	 * @return ViewableData_Customised
	 */
	public function edit(SS_HTTPRequest $request) {
		Requirements::clear();
		Requirements::unblock_all();
		return $this->customise(array(
			'Form' => $this->EditForm()
		))->renderWith($this->parent->getTemplateFileEdit());
	}
	/**
	 * @return Form
	 */
	function EditForm() {
		$file = $this->getItem();
		if (is_a($this->parent->getConfig('fileEditFields'), 'FieldList')) {
			$fields = $this->parent->getConfig('fileEditFields');
		} elseif ($file->hasMethod($this->parent->getConfig('fileEditFields'))) {
			$fields = $file->{$this->parent->getConfig('fileEditFields')}();
		} else {
			$fields = $file->uploadMetadataFields(); // TODO use getCMSFields
		}
		if (is_a($this->parent->getConfig('fileEditActions'), 'FieldList')) {
			$actions = $this->parent->getConfig('fileEditActions');
		} elseif ($file->hasMethod($this->parent->getConfig('fileEditActions'))) {
			$actions = $file->{$this->parent->getConfig('fileEditActions')}();
		} else {
			$actions = new FieldList(new FormAction('doEdit', _t('UploadField.DOEDIT', 'save')));
		}
		if (is_a($this->parent->getConfig('fileEditValidator'), 'Validator')) {
			$validator = $this->parent->getConfig('fileEditValidator');
		} elseif ($file->hasMethod($this->parent->getConfig('fileEditValidator'))) {
			$validator = $file->{$this->parent->getConfig('fileEditValidator')}();
		} else {
			$validator = null;
		}
		$form = new Form(
			$this,
			__FUNCTION__, 
			$fields,
			$actions,
			$validator
		);
		$form->loadDataFrom($file);
		return $form;
	}
	/**
	 * @param array $data
	 * @param Form $form
	 * @param SS_HTTPRequest $request
	 */
	public function doEdit(array $data, Form $form, SS_HTTPRequest $request) {
		$file = $this->getItem();
		$form->saveInto($file);
		$file->write();
		Director::redirectBack();
	}

}




