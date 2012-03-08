<?php

/**
 * Field for uploading single or multiple files of all types, including images.
 * <b>NOTE: this Field will call write() on the supplied record</b>
 * 
 * <b>Features (some might not be avaliable to old browsers):</b>
 * 
 * - File Drag&Drop support
 * - Progressbar
 * - Image thumbnail/file icons even before upload finished
 * - Saving into relations
 * - Edit file
 * - allowedExtensions is by default File::$allowed_extensions<li>maxFileSize the vaule of min(upload_max_filesize, post_max_size) from php.ini
 * 
 * @example <code>
 * $UploadField = new UploadField('myFiles', 'Please upload some images <span>(max. 5 files)</span>');
 * $UploadField->getValidator()->setAllowedExtensions(array('jpg', 'jpeg', 'png', 'gif'));
 * $UploadField->setConfig('allowedMaxFileNumber', 5);
 * </code>
 * 
 * @author Zauberfisch
 * @package sapphire
 * @subpackage forms
 */
class UploadField extends FileField {

	/**
	 * @var array
	 */
	public static $allowed_actions = array(
		'upload',
		'attach',
		'handleItem',
		'handleSelect',
	);

	/**
	 * @var array
	 */
	public static $url_handlers = array(
		'item/$ID' => 'handleItem',
		'select' => 'handleSelect',
		'$Action!' => '$Action',
	);

	/**
	 * @var String
	 */
	protected $template = 'UploadField';

	/**
	 * @var String
	 */
	protected $templateFileButtons = 'UploadField_FileButtons';

	/**
	 * @var String
	 */
	protected $templateFileEdit = 'UploadField_FileEdit';

	/**
	 * @var DataObject
	 */
	protected $record;

	/**
	 * @var SS_List
	 */
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
	 * @param string $name The internal field name, passed to forms.
	 * @param string $title The field label.
	 * @param SS_List $items If no items are defined, the field will try to auto-detect an existion relation on {@link $record}, 
	 *                       with the same name as the field name.
	 * @param Form $form Reference to the container form
	 */
	public function __construct($name, $title = null, SS_List $items = null) {
		// TODO thats the first thing that came to my head, feel free to change it
		$this->addExtraClass('ss-upload'); // class, used by js
		$this->addExtraClass('ss-uploadfield'); // class, used by css for uploadfield only

		parent::__construct($name, $title);

		if($items) $this->setItems($items);

		$this->getValidator()->setAllowedExtensions(array_filter(File::$allowed_extensions)); // filter out '' since this would be a regex problem on JS end
		$this->getValidator()->setAllowedMaxFileSize(min(File::ini2bytes(ini_get('upload_max_filesize')), File::ini2bytes(ini_get('post_max_size')))); // get the lower max size
	}

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
		$name = $this->getName();
		if (!$this->items || !$this->items->exists()) {
			$record = $this->getRecord();
			$this->items = array();
			// Try to auto-detect relationship
			if ($record && $record->exists()) {
				if ($record->has_many($name) || $record->many_many($name)) {
					// Ensure relationship is cast to an array, as we can't alter the items of a DataList/RelationList (see below)
					$this->items = $record->{$name}()->toArray();
				} elseif($record->has_one($name)) {
					$item = $record->{$name}();
					if ($item && $item->exists())
						$this->items = array($record->{$name}());
				}
			}
			$this->items = new ArrayList($this->items);
			// hack to provide $UploadFieldThumbnailURL, $hasRelation and $UploadFieldEditLink in template for each file
			if ($this->items->exists()) {
				foreach ($this->items as $i=>$file) {
					$this->items[$i] = $this->customiseFile($file);	
					if(!$file->canView()) unset($this->items[$i]); // Respect model permissions
				}
			}
		}
		return $this->items;
	}

	/**
	 * Hack to add some Variables and a dynamic template to a File
	 * @param File $file
	 * @return ViewableData_Customised
	 */
	protected function customiseFile(File $file) {
		$file = $file->customise(array(
			'UploadFieldHasRelation' => $this->managesRelation(),
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

	public function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array('data-selectdialog-url', $this->Link('select'))
		);
	}

	public function Field() {
		$record = $this->getRecord();
		$name = $this->getName();

		// if there is a has_one relation with that name on the record and 
		// allowedMaxFileNumber has not been set, its wanted to be 1
		if(
			$record && $record->exists()
			&& $record->has_one($name) && !$this->getConfig('allowedMaxFileNumber')
		) {
			$this->setConfig('allowedMaxFileNumber', 1);
		}

		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery-ui.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/i18n.js');
		Requirements::javascript(SAPPHIRE_ADMIN_DIR . '/javascript/ssui.core.js');

		Requirements::combine_files('uploadfield.js', array(
			THIRDPARTY_DIR . '/javascript-templates/tmpl.js',
			THIRDPARTY_DIR . '/javascript-loadimage/load-image.js',
			THIRDPARTY_DIR . '/jquery-fileupload/jquery.iframe-transport.js',
			THIRDPARTY_DIR . '/jquery-fileupload/cors/jquery.xdr-transport.js',
			THIRDPARTY_DIR . '/jquery-fileupload/jquery.fileupload.js',
			THIRDPARTY_DIR . '/jquery-fileupload/jquery.fileupload-ui.js',
			SAPPHIRE_DIR . '/javascript/UploadField_uploadtemplate.js',
			SAPPHIRE_DIR . '/javascript/UploadField_downloadtemplate.js',
			SAPPHIRE_DIR . '/javascript/UploadField.js',
		));
		Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css'); // TODO hmmm, remove it?
		Requirements::css(SAPPHIRE_DIR . '/css/UploadField.css');

		$config = array(
			'url' => $this->Link('upload'),
			'urlSelectDialog' => $this->Link('select'),
			'urlAttach' => $this->Link('attach'),
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
	 * @param SS_HTTPRequest $request
	 * @return UploadField_ItemHandler
	 */
	public function handleSelect(SS_HTTPRequest $request) {
		return Object::create('UploadField_SelectHandler', $this, $this->folderName);
	}

	/**
	 * Action to handle upload of a single file
	 * 
	 * @param SS_HTTPRequest $request
	 * @return string json
	 */
	public function upload(SS_HTTPRequest $request) {
		if($this->isDisabled() || $this->isReadonly()) return $this->httpError(403);

		// Protect against CSRF on destructive action
		$token = $this->getForm()->getSecurityToken();
		if(!$token->checkRequest($request)) return $this->httpError(400);

		$name = $this->getName();
		$tmpfile = $request->postVar($name);
		$record = $this->getRecord();
		
		if (!$tmpfile) {
			$return = array('error' => _t('UploadField.FIELDNOTSET', 'File information not found'));
		} else {
			$return = array(
				'name' => $tmpfile['name'],
				'size' => $tmpfile['size'],
				'type' => $tmpfile['type'],
				'error' => $tmpfile['error']
			);
		}
		if (!$return['error'] && $record && $record->exists()) {
			$tooManyFiles = false;
			if ($this->getConfig('allowedMaxFileNumber') && ($record->has_many($name) || $record->many_many($name))) {
				if(!$record->isInDB()) $record->write();
				$tooManyFiles = $record->{$name}()->count() >= $this->getConfig('allowedMaxFileNumber');
			} elseif($record->has_one($name)) {
				$tooManyFiles = $record->{$name}() && $record->{$name}()->exists();
			}
			if ($tooManyFiles) {
				if(!$this->getConfig('allowedMaxFileNumber')) $this->setConfig('allowedMaxFileNumber', 1);
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
					$file->write();
					$this->attachFile($file);
					$file =  $this->customiseFile($file);
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

	/**
	 * Add existing {@link File} records to the relationship.
	 */
	public function attach($request) {
		if(!$request->isPOST()) return $this->httpError(403);
		if(!$this->managesRelation()) return $this->httpError(403);

		$return = array();

		$files = DataList::create('File')->byIDs($request->postVar('ids'));
		foreach($files as $file) {
			$this->attachFile($file);
			$file =  $this->customiseFile($file);
			$return[] = array(
				'id' => $file->ID,
				'name' => $file->getTitle() . '.' . $file->getExtension(),
				'url' => $file->getURL(),
				'thumbnail_url' => $file->UploadFieldThumbnailURL,
				'edit_url' => $file->UploadFieldEditLink,
				'size' => $file->getAbsoluteSize(),
				'buttons' => $file->UploadFieldFileButtons
			);
		}
		$response = new SS_HTTPResponse(Convert::raw2json($return));
		$response->addHeader('Content-Type', 'application/json');
		return $response;
	}

	/**
	 * @param File
	 */
	protected function attachFile($file) {
		$record = $this->getRecord();
		$name = $this->getName();
		if ($record && $record->exists()) {
			if ($record->has_many($name) || $record->many_many($name)) {
				if(!$record->isInDB()) $record->write();
				$record->{$name}()->add($file);
			} elseif($record->has_one($name)) {
				$record->{$name . 'ID'} = $file->ID;
				$record->write();
			}
		}
	}

	public function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->addExtraClass('readonly');
		$clone->setReadonly(true);
		return $clone;
	}

	/**
	 * Determines if the underlying record (if any) has a relationship
	 * matching the field name. Important for permission control.
	 * 
	 * @return boolean
	 */
	public function managesRelation() {
		$record = $this->getRecord();
		$fieldName = $this->getName();
		return (
			$record 
			&& ($record->has_one($fieldName) || $record->has_many($fieldName) || $record->many_many($fieldName))
		);
	}

}

/**
 * RequestHandler for actions (edit, remove, delete) on a single item (File) of the UploadField
 * 
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
	 * @param UploadFIeld $parent
	 * @param int $item
	 */
	public function __construct($parent, $itemID) {
		$this->parent = $parent;
		$this->itemID = $itemID;

		parent::__construct();
	}

	/**
	 * @return File
	 */
	function getItem() {
		return DataObject::get_by_id('File', $this->itemID);
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
		$token = $this->parent->getForm()->getSecurityToken();
		return $token->addToUrl($this->Link('remove'));
	}

	/**
	 * @return string
	 */
	public function DeleteLink() {
		$token = $this->parent->getForm()->getSecurityToken();
		return $token->addToUrl($this->Link('delete'));
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
		// Check form field state
		if($this->parent->isDisabled() || $this->parent->isReadonly()) return $this->httpError(403);

		// Protect against CSRF on destructive action
		$token = $this->parent->getForm()->getSecurityToken();
		if(!$token->checkRequest($request)) return $this->httpError(400);

		$response = new SS_HTTPResponse();
		$response->setStatusCode(500);
		$fieldName = $this->parent->getName();
		$record = $this->parent->getRecord();
		$id = $this->getItem()->ID;
		if ($id && $record && $record->exists()) {
			if (($record->has_many($fieldName) || $record->many_many($fieldName)) && $file = $record->{$fieldName}()->byID($id)) {
				$record->{$fieldName}()->remove($file);
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
		// Check form field state
		if($this->parent->isDisabled() || $this->parent->isReadonly()) return $this->httpError(403);

		// Protect against CSRF on destructive action
		$token = $this->parent->getForm()->getSecurityToken();
		if(!$token->checkRequest($request)) return $this->httpError(400);

		// Check item permissions
		$item = $this->getItem();
		if(!$item) return $this->httpError(404);
		if(!$item->canDelete()) return $this->httpError(403);

		// Only allow actions on files in the managed relation (if one exists)
		$items = $this->parent->getItems();
		if($this->parent->managesRelation() && !$items->byID($item->ID)) return $this->httpError(403);

		// First remove the file from the current relationship
		$this->remove($request);

		// Then delete the file from the filesystem
		$item->delete();
	}

	/**
	 * Action to handle editing of a single file
	 * 
	 * @param SS_HTTPRequest $request
	 * @return ViewableData_Customised
	 */
	public function edit(SS_HTTPRequest $request) {
		// Check form field state
		if($this->parent->isDisabled() || $this->parent->isReadonly()) return $this->httpError(403);

		// Check item permissions
		$item = $this->getItem();
		if(!$item) return $this->httpError(404);
		if(!$item->canEdit()) return $this->httpError(403);

		// Only allow actions on files in the managed relation (if one exists)
		$items = $this->parent->getItems();
		if($this->parent->managesRelation() && !$items->byID($item->ID)) return $this->httpError(403);

		Requirements::css(SAPPHIRE_DIR . '/css/UploadField.css');

		return $this->customise(array(
			'Form' => $this->EditForm()
		))->renderWith($this->parent->getTemplateFileEdit());
	}

	/**
	 * @return Form
	 */
	public function EditForm() {
		$file = $this->getItem();
		if (is_a($this->parent->getConfig('fileEditFields'), 'FieldList')) {
			$fields = $this->parent->getConfig('fileEditFields');
		} elseif ($file->hasMethod($this->parent->getConfig('fileEditFields'))) {
			$fields = $file->{$this->parent->getConfig('fileEditFields')}();
		} else {
			$fields = $file->getCMSFields();
			// Only display main tab, to avoid overly complex interface
			if($fields->hasTabSet() && $mainTab = $fields->findOrMakeTab('Root.Main')) $fields = $mainTab->Fields();
		}
		if (is_a($this->parent->getConfig('fileEditActions'), 'FieldList')) {
			$actions = $this->parent->getConfig('fileEditActions');
		} elseif ($file->hasMethod($this->parent->getConfig('fileEditActions'))) {
			$actions = $file->{$this->parent->getConfig('fileEditActions')}();
		} else {
			$actions = new FieldList($saveAction = new FormAction('doEdit', _t('UploadField.DOEDIT', 'Save')));
			$saveAction->addExtraClass('ss-ui-action-constructive icon-accept');
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
		$form->addExtraClass('small');
		
		return $form;
	}

	/**
	 * @param array $data
	 * @param Form $form
	 * @param SS_HTTPRequest $request
	 */
	public function doEdit(array $data, Form $form, SS_HTTPRequest $request) {
		// Check form field state
		if($this->parent->isDisabled() || $this->parent->isReadonly()) return $this->httpError(403);

		// Check item permissions
		$item = $this->getItem();
		if(!$item) return $this->httpError(404);
		if(!$item->canEdit()) return $this->httpError(403);

		// Only allow actions on files in the managed relation (if one exists)
		$items = $this->parent->getItems();
		if($this->parent->managesRelation() && !$items->byID($item->ID)) return $this->httpError(403);

		$form->saveInto($item);
		$item->write();

		$form->sessionMessage(_t('UploadField.Saved', 'Saved'), 'good');

		return $this->parent->getForm()->Controller()->redirectBack();
	}
	
}


class UploadField_SelectHandler extends RequestHandler {

	/**
	 * @var UploadField
	 */
	protected $parent;

	/**
	 * @var String
	 */
	protected $folderName;

	public static $url_handlers = array(
		'$Action!' => '$Action',
		'' => 'index',
	);

	function __construct($parent, $folderName = null) {
		$this->parent = $parent;
		$this->folderName = $folderName;

		parent::__construct();
	}

	function index() {
		return $this->renderWith('CMSDialog');
	}

	/**
	 * @param string $action
	 * @return string
	 */
	public function Link($action = null) {
		return Controller::join_links($this->parent->Link(), '/select/', $action);
	}

	/**
	 * @return Form
	 */
	function Form() {
		$action = new FormAction('doAttach', _t('UploadField.AttachFile', 'Attach file(s)'));
		$action->addExtraClass('ss-ui-action-constructive icon-accept');
		return new Form(
			$this,
			'Form',
			new FieldList($this->getListField()),
			new FieldList($action)
		);
	}

	/**
	 * @return FormField
	 */
	protected function getListField() {
		$folder = $this->getFolder();
		$config = GridFieldConfig::create();
		$config->addComponent(new GridFieldSortableHeader());
		$config->addComponent(new GridFieldFilter());
		$config->addComponent(new GridFieldDefaultColumns());
		$config->addComponent(new GridFieldPaginator(10));

		$field = new GridField('Files', false, $folder->stageChildren(), $config);
		$field->setAttribute('data-selectable', true);
		if($this->parent->getConfig('allowedMaxFileNumber') > 1) $field->setAttribute('data-multiselect', true);

		return $field;
	}

	/**
	 * @return Folder
	 */
	function getFolder() {
		return Folder::find_or_make($this->folderName);
	}

	function doAttach($data, $form) {
		// TODO Only implemented via JS for now
	}

}
