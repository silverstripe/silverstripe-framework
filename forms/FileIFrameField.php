<?php
/**
 * A field that allows you to attach a file to a DataObject without submitting the form it is part of, through the use
 * of an iframe.
 *
 * If all you need is a simple file upload, it is reccomended you use {@link FileField}
 *
 * @deprecated 3.0 Use UploadField
 *
 * @package forms
 * @subpackage fields-files
 */
class FileIFrameField extends FileField {
	
	public static $allowed_actions = array (
		'iframe',
		'EditFileForm',
		'DeleteFileForm'
	);
	
	/**
	 * Flag that controls whether or not new files
	 * can be uploaded by the user from their local computer.
	 * 
	 * @var boolean
	 */
	protected $canUploadNewFile = true;	
	
	/** 
	 * Sets whether or not files can be uploaded into the CMS from the user's local computer 
	 * 
	 * @param boolean
	 */
	function setCanUploadNewFile($can) {
		$this->canUploadNewFile = $can;
	}
	
	/**
	 * @return boolean
	 */
	function getCanUploadNewFile() {
		return $this->canUploadNewFile;
	}
	
	/**
	 * The data class that this field is editing.
	 * @return string Class name
	 */
	public function dataClass() {
		if($this->form && $this->form->getRecord()) {
			$class = $this->form->getRecord()->has_one($this->getName());
			return ($class) ? $class : 'File';
		} else {
			return 'File';
		}
	}
	
	/**
	 * @return string
	 */
	public function Field($properties = array()) {
		Deprecation::notice('3.0', 'Use UploadField');

		Requirements::css(FRAMEWORK_DIR . '/thirdparty/jquery-ui-themes/smoothness/jquery-ui.css');
		Requirements::add_i18n_javascript(FRAMEWORK_DIR . '/javascript/lang');
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery/jquery.js');
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery-ui/jquery-ui.js');
		
		
		if($this->form->getRecord() && $this->form->getRecord()->exists()) {
			$record = $this->form->getRecord();
			if(class_exists('Translatable') && Object::has_extension('SiteTree', 'Translatable') && $record->Locale){
				$iframe = "iframe?locale=".$record->Locale;
			}else{
				$iframe = "iframe";
			}
			
			return $this->createTag (
				'iframe',
				array (
					'name'  => $this->getName() . '_iframe',
					'src'   => Controller::join_links($this->Link(), $iframe),
					'style' => 'height: 152px; width: 100%; border: none;'
				)
			) . $this->createTag (
				'input',
				array (
					'type'  => 'hidden',
					'id'    => $this->ID(),
					'name'  => $this->getName() . 'ID',
					'value' => $this->attrValue()
				)
			);
		} else {
			return _t(
				'FileIFrameField.ATTACHONCESAVED', 
				'{type}s can be attached once you have saved the record for the first time.',
				array('type' => $this->FileTypeName())
			);
		}
	}
	
	/**
	 * Attempt to retreive a File object that has already been attached to this forms data record
	 *
	 * @return File|null
	 */
	public function AttachedFile() {
		return $this->form->getRecord() ? $this->form->getRecord()->{$this->getName()}() : null;
	}
	
	/**
	 * @return string
	 */
	public function iframe() {
		// clear the requirements added by any parent controllers
		Requirements::clear();
		Requirements::add_i18n_javascript(FRAMEWORK_DIR . '/javascript/lang');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(FRAMEWORK_DIR . '/javascript/FileIFrameField.js');
		
		Requirements::css(FRAMEWORK_DIR . '/css/FileIFrameField.css');
		
		return $this->renderWith('FileIframeField_iframe');
	}
	
	/**
	 * @return Form
	 */
	public function EditFileForm() {
		$uploadFile = _t('FileIFrameField.FROMCOMPUTER', 'From your Computer');
		$selectFile = _t('FileIFrameField.FROMFILESTORE', 'From the File Store');
		
		if($this->AttachedFile() && $this->AttachedFile()->ID) {
			$title = _t('FileIFrameField.REPLACE', 'Replace {type}', array('type' => $this->FileTypeName()));
		} else {
			$title = _t('FileIFrameField.ATTACH', 'Attach {type}', array('type' => $this->FileTypeName()));
		}
		
		$fileSources = array();
		
		if(singleton($this->dataClass())->canCreate()) {
			if($this->canUploadNewFile) {
				$fileSources["new//$uploadFile"] = new FileField('Upload', '');
			}
		}
		
		$fileSources["existing//$selectFile"] = new TreeDropdownField('ExistingFile', '', 'File');

		$fields = new FieldList (
			new HeaderField('EditFileHeader', $title),
			new SelectionGroup('FileSource', $fileSources)
		);
		
		// locale needs to be passed through from the iframe source
		if(isset($_GET['locale'])) {
			$fields->push(new HiddenField('locale', '', $_GET['locale']));
		}
		
		return new Form (
			$this,
			'EditFileForm',
			$fields,
			new FieldList(
				new FormAction('save', $title)
			)
		);
	}
	
	public function save($data, $form) {
		// check the user has entered all the required information
		if (
			!isset($data['FileSource'])
			|| ($data['FileSource'] == 'new' && (!isset($_FILES['Upload']) || !$_FILES['Upload']))
			|| ($data['FileSource'] == 'existing' && (!isset($data['ExistingFile']) || !$data['ExistingFile']))
		) {
			$form->sessionMessage(_t('FileIFrameField.NOSOURCE', 'Please select a source file to attach'), 'required');
			$form->getController()->redirectBack();
			return;
		}

		$desiredClass = $this->dataClass();
		$controller = $this->form->getController();

		// upload a new file
		if($data['FileSource'] == 'new') {
			$fileObject = Object::create($desiredClass);
			
			try {
				$this->upload->loadIntoFile($_FILES['Upload'], $fileObject, $this->folderName);
			} catch (Exception $e){
				$form->sessionMessage(_t('FileIFrameField.DISALLOWEDFILETYPE', 'This filetype is not allowed to be uploaded'), 'bad');
				$controller->redirectBack();
				return;
			}
			
			if($this->upload->isError()) {
				$controller->redirectBack();
				return;
			}
			
			$this->form->getRecord()->{$this->getName() . 'ID'} = $fileObject->ID;
			
			$fileObject->write();
		}
		
		// attach an existing file from the assets store
		if($data['FileSource'] == 'existing') {
			$fileObject = DataObject::get_by_id('File', $data['ExistingFile']);
			
			// dont allow the user to attach a folder by default
			if(!$fileObject || ($fileObject instanceof Folder && $desiredClass != 'Folder')) {
				$controller->redirectBack();
				return;
			}
			
			$this->form->getRecord()->{$this->getName() . 'ID'} = $fileObject->ID;
			
			if(!$fileObject instanceof $desiredClass) {
				$fileObject->ClassName = $desiredClass;
				$fileObject->write();
			}
		}
		
		$this->form->getRecord()->write();
		$controller->redirectBack();
	}
	
	/**
	 * @return Form
	 */
	public function DeleteFileForm() {
		$form = new Form (
			$this,
			'DeleteFileForm',
			new FieldList (
				new HiddenField('DeleteFile', null, false)
			),
			new FieldList (
				$deleteButton = new FormAction (
					'delete', _t('FileIFrameField.DELETE', 'Delete {type}', array('type' => $this->FileTypeName()))
				)
			)
		);
		
		$deleteButton->addExtraClass('delete');
		return $form;
	}
	
	public function delete($data, $form) {
		// delete the actual file, or just un-attach it?
		if(isset($data['DeleteFile']) && $data['DeleteFile']) {
			$file = DataObject::get_by_id('File', $this->form->getRecord()->{$this->getName() . 'ID'});
			
			if($file) {
				$file->delete();
			}
		}

		// then un-attach file from this record
		$this->form->getRecord()->{$this->getName() . 'ID'} = 0;
		$this->form->getRecord()->write();

		$this->form->getController()->redirectBack();
	}
	
	/**
	 * Get the type of file this field is used to attach (e.g. File, Image)
	 *
	 * @return string
	 */
	public function FileTypeName() {
		return _t('FileIFrameField.FILE', 'File');
	}
	
}
