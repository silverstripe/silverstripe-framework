<?php
/**
 * A TinyMCE-powered WYSIWYG HTML editor field with image and link insertion and tracking capabilities. Editor fields
 * are created from <textarea> tags, which are then converted with JavaScript.
 *
 * @package forms
 * @subpackage fields-formattedinput
 */
class HtmlEditorField extends TextareaField {
	
	/**
	 * Includes the JavaScript neccesary for this field to work using the {@link Requirements} system.
	 */
	public static function include_js() {
		Requirements::javascript(MCE_ROOT . 'tiny_mce_src.js');
		Requirements::customScript(HtmlEditorConfig::get_active()->generateJS(), 'htmlEditorConfig');
	}
	
	/**
	 * @see TextareaField::__construct()
	 */
	public function __construct($name, $title = null, $rows = 30, $cols = 20, $value = '', $form = null) {
		parent::__construct($name, $title, $rows, $cols, $value, $form);
		
		$this->addExtraClass('htmleditor');
		
		self::include_js();
	}
	
	/**
	 * @return string
	 */
	function Field() {
		// mark up broken links
		$value  = new SS_HTMLValue($this->value);
		
		if($links = $value->getElementsByTagName('a')) foreach($links as $link) {
			$matches = array();
			
			if(preg_match('/\[sitetree_link id=([0-9]+)\]/i', $link->getAttribute('href'), $matches)) {
				if(!DataObject::get_by_id('SiteTree', $matches[1])) {
					$class = $link->getAttribute('class');
					$link->setAttribute('class', ($class ? "$class ss-broken" : 'ss-broken'));
				}
			}
		}
		
		return $this->createTag (
			'textarea',
			array (
				'class'   => $this->extraClass(),
				'rows'    => $this->rows,
				'cols'    => $this->cols,
				'style'   => 'width: 97%; height: ' . ($this->rows * 16) . 'px', // prevents horizontal scrollbars
				'tinymce' => 'true',
				'id'      => $this->id(),
				'name'    => $this->name
			),
			htmlentities($value->getContent(), ENT_COMPAT, 'UTF-8')
		);
	}
	
	public function saveInto($record) {
		if($record->escapeTypeForField($this->name) != 'xml') {
			throw new Exception (
				'HtmlEditorField->saveInto(): This field should save into a HTMLText or HTMLVarchar field.'
			);
		}
		
		$linkedPages = array();
		$linkedFiles = array();
		
		$htmlValue = new SS_HTMLValue($this->value);
		
		// Populate link tracking for internal links & links to asset files.
		if($links = $htmlValue->getElementsByTagName('a')) foreach($links as $link) {
			$href = Director::makeRelative($link->getAttribute('href'));
			
			if($href) {
				if(preg_match('/\[sitetree_link id=([0-9]+)\]/i', $href, $matches)) {
					$ID = $matches[1];
					
					// clear out any broken link classes
					if($class = $link->getAttribute('class')) {
						$link->setAttribute('class', preg_replace('/(^ss-broken|ss-broken$| ss-broken )/', null, $class));
					}
					
					$linkedPages[] = $ID;
					if(!DataObject::get_by_id('SiteTree', $ID))  $record->HasBrokenLink = true;

				} else if(substr($href, 0, strlen(ASSETS_DIR) + 1) == ASSETS_DIR.'/') {
					$candidateFile = File::find(Convert::raw2sql(urldecode($href)));
					if($candidateFile) {
						$linkedFiles[] = $candidateFile->ID;
					} else {
						$record->HasBrokenFile = true;
					}
				} else if($href == '' || $href[0] == '/') {
					$record->HasBrokenLink = true;
				}
			}
		}
		
		// Resample images, add default attributes and add to assets tracking.
		if($images = $htmlValue->getElementsByTagName('img')) foreach($images as $img) {
			// strip any ?r=n data from the src attribute
			$img->setAttribute('src', preg_replace('/([^\?]*)\?r=[0-9]+$/i', '$1', $img->getAttribute('src')));
			if(!$image = File::find($path = urldecode(Director::makeRelative($img->getAttribute('src'))))) {
				if(substr($path, 0, strlen(ASSETS_DIR) + 1) == ASSETS_DIR . '/') {
					$record->HasBrokenFile = true;
				}
				
				continue;
			}
			
			// Resample the images if the width & height have changed.
			$width  = $img->getAttribute('width');
			$height = $img->getAttribute('height');
			
			if($image){
				if($width && $height && ($width != $image->getWidth() || $height != $image->getHeight())) {
					//Make sure that the resized image actually returns an image:
					$resized=$image->ResizedImage($width, $height);
					if($resized)
						$img->setAttribute('src', $resized->getRelativePath());
				}
			}
			
			// Add default empty title & alt attributes.
			if(!$img->getAttribute('alt')) $img->setAttribute('alt', '');
			if(!$img->getAttribute('title')) $img->setAttribute('title', '');
			
			//If the src attribute is not set, then we won't add this to the list:
			if($img->getAttribute('src')){
				// Add to the tracked files.
				$linkedFiles[] = $image->ID;
			}
		}
		
		// Save file & link tracking data.
		if($record->ID && $record->many_many('LinkTracking') && $tracker = $record->LinkTracking()) {
			$filter = sprintf('"FieldName" = \'%s\' AND "SiteTreeID" = %d', $this->name, $record->ID);
			DB::query("DELETE FROM \"$tracker->tableName\" WHERE $filter");

			if($linkedPages) foreach($linkedPages as $item) {
				$SQL_fieldName = Convert::raw2sql($this->name);
				DB::query("INSERT INTO \"SiteTree_LinkTracking\" (\"SiteTreeID\",\"ChildID\", \"FieldName\")
					VALUES ($record->ID, $item, '$SQL_fieldName')");
			}
		}
		
		if($record->ID && $record->many_many('ImageTracking') && $tracker = $record->ImageTracking()) {
			$filter = sprintf('"FieldName" = \'%s\' AND "SiteTreeID" = %d', $this->name, $record->ID);
			DB::query("DELETE FROM \"$tracker->tableName\" WHERE $filter");

			$fieldName = $this->name;
			if($linkedFiles) foreach($linkedFiles as $item) {
				$tracker->add($item, array('FieldName' => $this->name));
			}
		}
		
		$record->{$this->name} = $htmlValue->getContent();
	}

	/**
	 * @return HtmlEditorField_Readonly
	 */
	public function performReadonlyTransformation() {
		$field = new HtmlEditorField_Readonly($this->name, $this->title, $this->value);
		$field->setForm($this->form);
		$field->dontEscape = true;
		return $field;
	}
	
}

/**
 * Readonly version of an {@link HTMLEditorField}.
 * @package forms
 * @subpackage fields-formattedinput
 */
class HtmlEditorField_Readonly extends ReadonlyField {
	function Field() {
		$valforInput = $this->value ? Convert::raw2att($this->value) : "";
		return "<span class=\"readonly typography\" id=\"" . $this->id() . "\">" . ( $this->value && $this->value != '<p></p>' ? $this->value : '<i>(not set)</i>' ) . "</span><input type=\"hidden\" name=\"".$this->name."\" value=\"".$valforInput."\" />";
	}
	function Type() {
		return 'htmleditorfield readonly';
	}
}

/**
 * External toolbar for the HtmlEditorField.
 * This is used by the CMS
 * @package forms
 * @subpackage fields-formattedinput
 */
class HtmlEditorField_Toolbar extends RequestHandler {
	protected $controller, $name;
	
	function __construct($controller, $name) {
		parent::__construct();
		Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/behaviour/behaviour.js");
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/tiny_mce_improvements.js");
		
		Requirements::javascript(SAPPHIRE_DIR ."/thirdparty/jquery-form/jquery.form.js");
		Requirements::javascript(SAPPHIRE_DIR ."/javascript/HtmlEditorField.js");
		
		$this->controller = $controller;
		$this->name = $name;
	}

	/**
	 * Searches the SiteTree for display in the dropdown
	 *  
	 * @return callback
	 */	
	function siteTreeSearchCallback($sourceObject, $labelField, $search) {
		return DataObject::get($sourceObject, "\"MenuTitle\" LIKE '%$search%' OR \"Title\" LIKE '%$search%'");
	}
	
	/**
	 * Return a {@link Form} instance allowing a user to
	 * add links in the TinyMCE content editor.
	 *  
	 * @return Form
	 */
	function LinkForm() {
		$siteTree = new TreeDropdownField('internal', _t('HtmlEditorField.PAGE', "Page"), 'SiteTree', 'ID', 'MenuTitle', true);
		// mimic the SiteTree::getMenuTitle(), which is bypassed when the search is performed
		$siteTree->setSearchFunction(array($this, 'siteTreeSearchCallback'));
		
		$form = new Form(
			$this->controller,
			"{$this->name}/LinkForm", 
			new FieldSet(
				new LiteralField('Heading', '<h2><img src="cms/images/closeicon.gif" alt="' . _t('HtmlEditorField.CLOSE', 'close').'" title="' . _t('HtmlEditorField.CLOSE', 'close') . '" />' . _t('HtmlEditorField.LINK', 'Link') . '</h2>'),
				new OptionsetField(
					'LinkType',
					_t('HtmlEditorField.LINKTO', 'Link to'), 
					array(
						'internal' => _t('HtmlEditorField.LINKINTERNAL', 'Page on the site'),
						'external' => _t('HtmlEditorField.LINKEXTERNAL', 'Another website'),
						'anchor' => _t('HtmlEditorField.LINKANCHOR', 'Anchor on this page'),
						'email' => _t('HtmlEditorField.LINKEMAIL', 'Email address'),
						'file' => _t('HtmlEditorField.LINKFILE', 'Download a file'),			
					)
				),
				$siteTree,
				new TextField('external', _t('HtmlEditorField.URL', 'URL'), 'http://'),
				new EmailField('email', _t('HtmlEditorField.EMAIL', 'Email address')),
				new TreeDropdownField('file', _t('HtmlEditorField.FILE', 'File'), 'File', 'Filename', 'Title', true),
				new TextField('Anchor', _t('HtmlEditorField.ANCHORVALUE', 'Anchor')),
				new TextField('LinkText', _t('HtmlEditorField.LINKTEXT', 'Link text')),
				new TextField('Description', _t('HtmlEditorField.LINKDESCR', 'Link description')),
				new CheckboxField('TargetBlank', _t('HtmlEditorField.LINKOPENNEWWIN', 'Open link in a new window?')),
				new HiddenField('Locale', null, $this->controller->Locale)
			),
			new FieldSet(
				new FormAction('insert', _t('HtmlEditorField.BUTTONINSERTLINK', 'Insert link')),
				new FormAction('remove', _t('HtmlEditorField.BUTTONREMOVELINK', 'Remove link'))
			)
		);
		
		$form->loadDataFrom($this);
		
		$this->extend('updateLinkForm', $form);
		
		return $form;
	}

	/**
	 * Return a {@link Form} instance allowing a user to
	 * add images to the TinyMCE content editor.
	 *  
	 * @return Form
	 */
	function ImageForm() {
		$fields = new FieldSet(
			new LiteralField('Heading', '<h2><img src="cms/images/closeicon.gif" alt="' . _t('HtmlEditorField.CLOSE', 'close') . '" title="' . _t('HtmlEditorField.CLOSE', 'close') . '" />' . _t('HtmlEditorField.IMAGE', 'Image') . '</h2>'),
			new TreeDropdownField('FolderID', _t('HtmlEditorField.FOLDER', 'Folder'), 'Folder'),
			new CompositeField(new FieldSet(
				new LiteralField('ShowUpload', '<p class="showUploadField"><a href="#">'. _t('HtmlEditorField.SHOWUPLOADFORM', 'Upload File') .'</a></p>'),
				new FileField("Files[0]" , _t('AssetAdmin.CHOOSEFILE','Choose file: ')),
				new LiteralField('Response', '<div id="UploadFormResponse"></div>'),
				new HiddenField('UploadMode', 'Upload Mode', 'CMSEditor') // used as a hook for doUpload switching
			)),
			new TextField('getimagesSearch', _t('HtmlEditorField.SEARCHFILENAME', 'Search by file name')),
			new ThumbnailStripField('FolderImages', 'FolderID', 'getimages'),
			new TextField('AltText', _t('HtmlEditorField.IMAGEALTTEXT', 'Alternative text (alt) - shown if image cannot be displayed'), '', 80),
			new TextField('ImageTitle', _t('HtmlEditorField.IMAGETITLE', 'Title text (tooltip) - for additional information about the image')),
			new TextField('CaptionText', _t('HtmlEditorField.CAPTIONTEXT', 'Caption text')),
			new DropdownField(
				'CSSClass',
				_t('HtmlEditorField.CSSCLASS', 'Alignment / style'),
				array(
					'left' => _t('HtmlEditorField.CSSCLASSLEFT', 'On the left, with text wrapping around.'),
					'leftAlone' => _t('HtmlEditorField.CSSCLASSLEFTALONE', 'On the left, on its own.'),
					'right' => _t('HtmlEditorField.CSSCLASSRIGHT', 'On the right, with text wrapping around.'),
					'center' => _t('HtmlEditorField.CSSCLASSCENTER', 'Centered, on its own.'),
				)
			),
			new FieldGroup(_t('HtmlEditorField.IMAGEDIMENSIONS', 'Dimensions'),
				new TextField('Width', _t('HtmlEditorField.IMAGEWIDTHPX', 'Width'), 100),
				new TextField('Height', " x " . _t('HtmlEditorField.IMAGEHEIGHTPX', 'Height'), 100)
			)
		);
		
		$actions = new FieldSet(
			new FormAction('insertimage', _t('HtmlEditorField.BUTTONINSERTIMAGE', 'Insert image'))
		);
		
		$form = new Form(
			$this->controller,
			"{$this->name}/ImageForm",
			$fields,
			$actions
		);
		
		$form->disableSecurityToken();
		$form->loadDataFrom($this);
		
		// Allow other people to extend the fields being added to the imageform 
		$this->extend('updateImageForm', $form);
		
		return $form;
	}

	function FlashForm() {
		$form = new Form(
			$this->controller,
			"{$this->name}/FlashForm", 
			new FieldSet(
				new LiteralField('Heading', '<h2><img src="cms/images/closeicon.gif" alt="'._t('HtmlEditorField.CLOSE', 'close').'" title="'._t('HtmlEditorField.CLOSE', 'close').'" />'._t('HtmlEditorField.FLASH', 'Flash').'</h2>'),
				new TreeDropdownField("FolderID", _t('HtmlEditorField.FOLDER'), "Folder"),
				new TextField('getflashSearch', _t('HtmlEditorField.SEARCHFILENAME', 'Search by file name')),
				new ThumbnailStripField("Flash", "FolderID", "getflash"),
				new FieldGroup(_t('HtmlEditorField.IMAGEDIMENSIONS', "Dimensions"),
					new TextField("Width", _t('HtmlEditorField.IMAGEWIDTHPX', "Width"), 100),
					new TextField("Height", "x " . _t('HtmlEditorField.IMAGEHEIGHTPX', "Height"), 100)
				)
			),
			new FieldSet(
				new FormAction("insertflash", _t('HtmlEditorField.BUTTONINSERTFLASH', 'Insert Flash'))
			)
		);

		$form->disableSecurityToken();
		$form->loadDataFrom($this);
		$form->disableSecurityToken();
		
		$this->extend('updateFlashForm', $form);
		
		return $form;
	}
}

