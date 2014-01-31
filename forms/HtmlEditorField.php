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
	 * @config
	 * @var Boolean Use TinyMCE's GZIP compressor
	 */
	private static $use_gzip = true;

	/**
	 * @config
	 * @var Integer Default insertion width for Images and Media
	 */
	private static $insert_width = 600;

	/**
	 * @config
	 * @var bool Should we check the valid_elements (& extended_valid_elements) rules from HtmlEditorConfig server side?
	 */
	private static $sanitise_server_side = false;

	protected $rows = 30;
	
	/**
	 * Includes the JavaScript neccesary for this field to work using the {@link Requirements} system.
	 */
	public static function include_js() {
		require_once 'tinymce/tiny_mce_gzip.php';

		$configObj = HtmlEditorConfig::get_active();

		if(Config::inst()->get('HtmlEditorField', 'use_gzip')) {
			$internalPlugins = array();
			foreach($configObj->getPlugins() as $plugin => $path) if(!$path) $internalPlugins[] = $plugin;
			$tag = TinyMCE_Compressor::renderTag(array(
				'url' => THIRDPARTY_DIR . '/tinymce/tiny_mce_gzip.php',
				'plugins' => implode(',', $internalPlugins),
				'themes' => 'advanced',
				'languages' => $configObj->getOption('language')
			), true);
			preg_match('/src="([^"]*)"/', $tag, $matches);
			Requirements::javascript(html_entity_decode($matches[1]));

		} else {
			Requirements::javascript(MCE_ROOT . 'tiny_mce_src.js');
		} 

		Requirements::customScript($configObj->generateJS(), 'htmlEditorConfig');
	}
	
	/**
	 * @see TextareaField::__construct()
	 */
	public function __construct($name, $title = null, $value = '') {
		parent::__construct($name, $title, $value);
		
		self::include_js();
	}
	
	/**
	 * @return string
	 */
	public function Field($properties = array()) {
		// mark up broken links
		$value = Injector::inst()->create('HTMLValue', $this->value);

		if($links = $value->getElementsByTagName('a')) foreach($links as $link) {
			$matches = array();
			
			if(preg_match('/\[sitetree_link(?:\s*|%20|,)?id=([0-9]+)\]/i', $link->getAttribute('href'), $matches)) {
				if(!DataObject::get_by_id('SiteTree', $matches[1])) {
					$class = $link->getAttribute('class');
					$link->setAttribute('class', ($class ? "$class ss-broken" : 'ss-broken'));
				}
			}

			if(preg_match('/\[file_link(?:\s*|%20|,)?id=([0-9]+)\]/i', $link->getAttribute('href'), $matches)) {
				if(!DataObject::get_by_id('File', $matches[1])) {
					$class = $link->getAttribute('class');
					$link->setAttribute('class', ($class ? "$class ss-broken" : 'ss-broken'));
				}
			}
		}

		$properties['Value'] = htmlentities($value->getContent(), ENT_COMPAT, 'UTF-8');
		$obj = $this->customise($properties);

		return $obj->renderWith($this->getTemplates());
	}

	public function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array(
				'tinymce' => 'true',
				'style'   => 'width: 97%; height: ' . ($this->rows * 16) . 'px', // prevents horizontal scrollbars
				'value' => null,
			)
		);
	}
	
	public function saveInto(DataObjectInterface $record) {
		if($record->hasField($this->name) && $record->escapeTypeForField($this->name) != 'xml') {
			throw new Exception (
				'HtmlEditorField->saveInto(): This field should save into a HTMLText or HTMLVarchar field.'
			);
		}
		
		$htmlValue = Injector::inst()->create('HTMLValue', $this->value);

		// Sanitise if requested
		if($this->config()->sanitise_server_side) {
			$santiser = Injector::inst()->create('HtmlEditorSanitiser', HtmlEditorConfig::get_active());
			$santiser->sanitise($htmlValue);
		}

		// Resample images and add default attributes
		if($images = $htmlValue->getElementsByTagName('img')) foreach($images as $img) {
			// strip any ?r=n data from the src attribute
			$img->setAttribute('src', preg_replace('/([^\?]*)\?r=[0-9]+$/i', '$1', $img->getAttribute('src')));

			// Resample the images if the width & height have changed.
			if($image = File::find(urldecode(Director::makeRelative($img->getAttribute('src'))))){
				$width  = $img->getAttribute('width');
				$height = $img->getAttribute('height');

				if($width && $height && ($width != $image->getWidth() || $height != $image->getHeight())) {
					//Make sure that the resized image actually returns an image:
					$resized=$image->ResizedImage($width, $height);
					if($resized) $img->setAttribute('src', $resized->getRelativePath());
				}
			}

			// Add default empty title & alt attributes.
			if(!$img->getAttribute('alt')) $img->setAttribute('alt', '');
			if(!$img->getAttribute('title')) $img->setAttribute('title', '');
		}

		// Store into record
		$record->{$this->name} = $htmlValue->getContent();
	}

	/**
	 * @return HtmlEditorField_Readonly
	 */
	public function performReadonlyTransformation() {
		$field = $this->castedCopy('HtmlEditorField_Readonly');
		$field->dontEscape = true;
		
		return $field;
	}
	
	public function performDisabledTransformation() {
		return $this->performReadonlyTransformation();
	}
}

/**
 * Readonly version of an {@link HTMLEditorField}.
 * @package forms
 * @subpackage fields-formattedinput
 */
class HtmlEditorField_Readonly extends ReadonlyField {
	public function Field($properties = array()) {
		$valforInput = $this->value ? Convert::raw2att($this->value) : "";
		return "<span class=\"readonly typography\" id=\"" . $this->id() . "\">"
			. ( $this->value && $this->value != '<p></p>' ? $this->value : '<i>(not set)</i>' )
			. "</span><input type=\"hidden\" name=\"".$this->name."\" value=\"".$valforInput."\" />";
	}
	public function Type() {
		return 'htmleditorfield readonly';
	}
}

/**
 * Toolbar shared by all instances of {@link HTMLEditorField}, to avoid too much markup duplication.
 *  Needs to be inserted manually into the template in order to function - see {@link LeftAndMain->EditorToolbar()}.
 * 
 * @package forms
 * @subpackage fields-formattedinput
 */
class HtmlEditorField_Toolbar extends RequestHandler {

	private static $allowed_actions = array(
		'LinkForm',
		'MediaForm',
		'viewfile'
	);

	/**
	 * @var string
	 */
	protected $templateViewFile = 'HtmlEditorField_viewfile';

	protected $controller, $name;
	
	public function __construct($controller, $name) {
		parent::__construct();

		Requirements::javascript(FRAMEWORK_DIR . "/thirdparty/jquery/jquery.js");
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery-ui.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/javascript/ssui.core.js');
		Requirements::javascript(FRAMEWORK_DIR ."/javascript/HtmlEditorField.js");

		Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css');
		
		$this->controller = $controller;
		$this->name = $name;
	}

	public function forTemplate() {
		return sprintf(
			'<div id="cms-editor-dialogs" data-url-linkform="%s" data-url-mediaform="%s"></div>',
			Controller::join_links($this->controller->Link(), $this->name, 'LinkForm', 'forTemplate'),
			Controller::join_links($this->controller->Link(), $this->name, 'MediaForm', 'forTemplate')
		);
	}

	/**
	 * Searches the SiteTree for display in the dropdown
	 *  
	 * @return callback
	 */	
	public function siteTreeSearchCallback($sourceObject, $labelField, $search) {
		return DataObject::get($sourceObject, "\"MenuTitle\" LIKE '%$search%' OR \"Title\" LIKE '%$search%'");
	}
	
	/**
	 * Return a {@link Form} instance allowing a user to
	 * add links in the TinyMCE content editor.
	 *  
	 * @return Form
	 */
	public function LinkForm() {
		$siteTree = new TreeDropdownField('internal', _t('HtmlEditorField.PAGE', "Page"),
			'SiteTree', 'ID', 'MenuTitle', true);
		// mimic the SiteTree::getMenuTitle(), which is bypassed when the search is performed
		$siteTree->setSearchFunction(array($this, 'siteTreeSearchCallback'));
		
		$numericLabelTmpl = '<span class="step-label"><span class="flyout">%d</span><span class="arrow"></span>'
			. '<strong class="title">%s</strong></span>';
		$form = new Form(
			$this->controller,
			"{$this->name}/LinkForm", 
			new FieldList(
				$headerWrap = new CompositeField(
					new LiteralField(
						'Heading', 
						sprintf('<h3 class="htmleditorfield-mediaform-heading insert">%s</h3>',
							_t('HtmlEditorField.LINK', 'Insert Link'))
					)
				),
				$contentComposite = new CompositeField(
					new OptionsetField(
						'LinkType',
						sprintf($numericLabelTmpl, '1', _t('HtmlEditorField.LINKTO', 'Link to')),
						array(
							'internal' => _t('HtmlEditorField.LINKINTERNAL', 'Page on the site'),
							'external' => _t('HtmlEditorField.LINKEXTERNAL', 'Another website'),
							'anchor' => _t('HtmlEditorField.LINKANCHOR', 'Anchor on this page'),
							'email' => _t('HtmlEditorField.LINKEMAIL', 'Email address'),
							'file' => _t('HtmlEditorField.LINKFILE', 'Download a file'),
						),
						'internal'
					),
					new LiteralField('Step2',
						'<div class="step2">'
						. sprintf($numericLabelTmpl, '2', _t('HtmlEditorField.DETAILS', 'Details')) . '</div>'
					),
					$siteTree,
					new TextField('external', _t('HtmlEditorField.URL', 'URL'), 'http://'),
					new EmailField('email', _t('HtmlEditorField.EMAIL', 'Email address')),
					new TreeDropdownField('file', _t('HtmlEditorField.FILE', 'File'), 'File', 'ID', 'Title', true),
					new TextField('Anchor', _t('HtmlEditorField.ANCHORVALUE', 'Anchor')),
					new TextField('Description', _t('HtmlEditorField.LINKDESCR', 'Link description')),
					new CheckboxField('TargetBlank',
						_t('HtmlEditorField.LINKOPENNEWWIN', 'Open link in a new window?')),
					new HiddenField('Locale', null, $this->controller->Locale)
				)
			),
			new FieldList(
				ResetFormAction::create('remove', _t('HtmlEditorField.BUTTONREMOVELINK', 'Remove link'))
					->addExtraClass('ss-ui-action-destructive')
					->setUseButtonTag(true)
				,
				FormAction::create('insert', _t('HtmlEditorField.BUTTONINSERTLINK', 'Insert link'))
					->addExtraClass('ss-ui-action-constructive')
					->setAttribute('data-icon', 'accept')
					->setUseButtonTag(true)
			)
		);

		$headerWrap->addExtraClass('CompositeField composite cms-content-header nolabel ');		
		$contentComposite->addExtraClass('ss-insert-link content');
		
		$form->unsetValidator();
		$form->loadDataFrom($this);
		$form->addExtraClass('htmleditorfield-form htmleditorfield-linkform cms-dialog-content');
		
		$this->extend('updateLinkForm', $form);
		
		return $form;
	}

	/**
	 * Return a {@link Form} instance allowing a user to
	 * add images and flash objects to the TinyMCE content editor.
	 *  
	 * @return Form
	 */
	public function MediaForm() {
		// TODO Handle through GridState within field - currently this state set too late to be useful here (during
		// request handling)
		$parentID = $this->controller->getRequest()->requestVar('ParentID');

		$fileFieldConfig = GridFieldConfig::create()->addComponents(
			new GridFieldFilterHeader(),
			new GridFieldSortableHeader(),
			new GridFieldDataColumns(),
			new GridFieldPaginator(5),
			// TODO Shouldn't allow delete here, its too confusing with a "remove from editor view" action.
			// Remove once we can fit the search button in the last actual title column
			new GridFieldDeleteAction(),
			new GridFieldDetailForm()
		);
		$fileField = new GridField('Files', false, null, $fileFieldConfig);
		$fileField->setList($this->getFiles($parentID));
		$fileField->setAttribute('data-selectable', true);
		$fileField->setAttribute('data-multiselect', true);
		$columns = $fileField->getConfig()->getComponentByType('GridFieldDataColumns');
		$columns->setDisplayFields(array(
			'CMSThumbnail' => false,
			'Name' => _t('File.Name'),
		));
		
		$numericLabelTmpl = '<span class="step-label"><span class="flyout">%d</span><span class="arrow"></span>'
			. '<strong class="title">%s</strong></span>';

		$fromCMS = new CompositeField(
			new LiteralField('headerSelect', 
				'<h4>'.sprintf($numericLabelTmpl, '1', _t('HtmlEditorField.FindInFolder', 'Find in Folder')).'</h4>'),
			$select = TreeDropdownField::create('ParentID', "", 'Folder')->addExtraClass('noborder'),	
			$fileField
		);
		
		$fromCMS->addExtraClass('content ss-uploadfield');
		$select->addExtraClass('content-select');


		$fromWeb = new CompositeField(
			new LiteralField('headerURL',
				'<h4>' . sprintf($numericLabelTmpl, '1', _t('HtmlEditorField.ADDURL', 'Add URL')) . '</h4>'),
			$remoteURL = new TextField('RemoteURL', 'http://'),
			new LiteralField('addURLImage',
				'<button class="action ui-action-constructive ui-button field add-url" data-icon="addMedia">' .
				_t('HtmlEditorField.BUTTONADDURL', 'Add url').'</button>')
		);

		$remoteURL->addExtraClass('remoteurl');
		$fromWeb->addExtraClass('content ss-uploadfield');

		Requirements::css(FRAMEWORK_DIR . '/css/AssetUploadField.css');
		$computerUploadField = Object::create('UploadField', 'AssetUploadField', '');
		$computerUploadField->setConfig('previewMaxWidth', 40);
		$computerUploadField->setConfig('previewMaxHeight', 30);
		$computerUploadField->addExtraClass('ss-assetuploadfield');
		$computerUploadField->removeExtraClass('ss-uploadfield');
		$computerUploadField->setTemplate('HtmlEditorField_UploadField');
		$computerUploadField->setFolderName(Config::inst()->get('Upload', 'uploads_folder'));

		$tabSet = new TabSet(
			"MediaFormInsertMediaTabs",
			Tab::create(
				'FromComputer',
				_t('HtmlEditorField.FROMCOMPUTER','From your computer'),
				$computerUploadField
			)->addExtraClass('htmleditorfield-from-computer'),
			Tab::create(
				'FromWeb',
				_t('HtmlEditorField.FROMWEB', 'From the web'),
				$fromWeb
			)->addExtraClass('htmleditorfield-from-web'),
			Tab::create(
				'FromCms',
				_t('HtmlEditorField.FROMCMS','From the CMS'),
				$fromCMS
			)->addExtraClass('htmleditorfield-from-cms')
		);
		$tabSet->addExtraClass('cms-tabset-primary');

		$allFields = new CompositeField(
			$tabSet,
			new LiteralField('headerEdit', '<h4 class="field noborder header-edit">' . sprintf($numericLabelTmpl, '2',
				_t('HtmlEditorField.ADJUSTDETAILSDIMENSIONS', 'Details &amp; dimensions')) . '</h4>'),
			$editComposite = new CompositeField(
				new LiteralField('contentEdit', '<div class="content-edit ss-uploadfield-files files"></div>')
			)
		);

		$allFields->addExtraClass('ss-insert-media');

		$headings = new CompositeField(
			new LiteralField(
				'Heading',
				sprintf('<h3 class="htmleditorfield-mediaform-heading insert">%s</h3>',
					_t('HtmlEditorField.INSERTMEDIA', 'Insert Media')).
				sprintf('<h3 class="htmleditorfield-mediaform-heading update">%s</h3>',
					_t('HtmlEditorField.UpdateMEDIA', 'Update Media'))
			)
		);

		$headings->addExtraClass('cms-content-header');
		$editComposite->addExtraClass('ss-assetuploadfield');

		$fields = new FieldList(
			$headings,
			$allFields
		);
		
		$actions = new FieldList(
			FormAction::create('insertmedia', _t('HtmlEditorField.BUTTONINSERT', 'Insert'))
				->addExtraClass('ss-ui-action-constructive media-insert')
				->setAttribute('data-icon', 'accept')
				->setUseButtonTag(true),
			FormAction::create('insertmedia', _t('HtmlEditorField.BUTTONUpdate', 'Update'))
				->addExtraClass('ss-ui-action-constructive media-update')
				->setAttribute('data-icon', 'accept')
				->setUseButtonTag(true)
		);

		$form = new Form(
			$this->controller,
			"{$this->name}/MediaForm",
			$fields,
			$actions
		);
		

		$form->unsetValidator();
		$form->disableSecurityToken();
		$form->loadDataFrom($this);
		$form->addExtraClass('htmleditorfield-form htmleditorfield-mediaform cms-dialog-content');
		// TODO Re-enable once we remove $.metadata dependency which currently breaks the JS due to $.ui.widget
		// $form->setAttribute('data-urlViewfile', $this->controller->Link($this->name));

		// Allow other people to extend the fields being added to the imageform 
		$this->extend('updateMediaForm', $form);
		
		return $form;
	}

	/**
	 * View of a single file, either on the filesystem or on the web.
	 */
	public function viewfile($request) {

		// TODO Would be cleaner to consistently pass URL for both local and remote files,
		// but GridField doesn't allow for this kind of metadata customization at the moment.
		if($url = $request->getVar('FileURL')) {
			if(Director::is_absolute_url($url) && !Director::is_site_url($url)) {
				$url = $url;
				$file = new File(array(
					'Title' => basename($url),
					'Filename' => $url
				));	
			} else {
				$url = Director::makeRelative($request->getVar('FileURL'));
				$url = preg_replace('/_resampled\/[^-]+-/', '', $url);
				$file = File::get()->filter('Filename', $url)->first();	
				if(!$file) $file = new File(array(
					'Title' => basename($url),
					'Filename' => $url
				));	
			}
		} elseif($id = $request->getVar('ID')) {
			$file = DataObject::get_by_id('File', $id);
			$url = $file->RelativeLink();
		} else {
			throw new LogicException('Need either "ID" or "FileURL" parameter to identify the file');
		}

		// Instanciate file wrapper and get fields based on its type
		// Check if appCategory is an image and exists on the local system, otherwise use oEmbed to refference a
		// remote image
		if($file && $file->appCategory() == 'image' && Director::is_site_url($url)) {
			$fileWrapper = new HtmlEditorField_Image($url, $file);
		} elseif(!Director::is_site_url($url)) {
			$fileWrapper = new HtmlEditorField_Embed($url, $file);
		} else {
			$fileWrapper = new HtmlEditorField_File($url, $file);
		}

		$fields = $this->getFieldsForFile($url, $fileWrapper);
		$this->extend('updateFieldsForFile', $fields, $url, $fileWrapper);

		return $fileWrapper->customise(array(
			'Fields' => $fields,
		))->renderWith($this->templateViewFile);
	}

	/**
	 * Similar to {@link File->getCMSFields()}, but only returns fields
	 * for manipulating the instance of the file as inserted into the HTML content,
	 * not the "master record" in the database - hence there's no form or saving logic.
	 * 
	 * @param String Relative or absolute URL to file
	 * @return FieldList
	 */
	protected function getFieldsForFile($url, $file) {
		$fields = $this->extend('getFieldsForFile', $url, $file);
		if(!$fields) {
			if($file instanceof HtmlEditorField_Embed) {
				$fields = $this->getFieldsForOembed($url, $file);
			} elseif($file->Extension == 'swf') {
				$fields = $this->getFieldsForFlash($url, $file);
			} else {
				$fields = $this->getFieldsForImage($url, $file);
			}
			$fields->push(new HiddenField('URL', false, $url));
		}

		$this->extend('updateFieldsForFile', $fields, $url, $file);
		
		return $fields;
	}

	/**
	 * @return FieldList
	 */
	protected function getFieldsForOembed($url, $file) {
		if(isset($file->Oembed->thumbnail_url)) {
			$thumbnailURL = $file->Oembed->thumbnail_url;	
		} elseif($file->Type == 'photo') {
			$thumbnailURL = $file->Oembed->url;
		} else {
			$thumbnailURL = FRAMEWORK_DIR . '/images/default_media.png';
		}
		
		$fields = new FieldList(
			$filePreview = CompositeField::create(
				CompositeField::create(
					new LiteralField(
						"ImageFull",
						"<img id='thumbnailImage' class='thumbnail-preview' "
							. "src='{$thumbnailURL}?r=" . rand(1,100000) . "' alt='{$file->Name}' />\n"
					)
				)->setName("FilePreviewImage")->addExtraClass('cms-file-info-preview'),
				CompositeField::create(
					CompositeField::create(
						new ReadonlyField("FileType", _t('AssetTableField.TYPE','File type') . ':', $file->Type),
						$urlField = ReadonlyField::create('ClickableURL', _t('AssetTableField.URL','URL'),
							sprintf('<a href="%s" target="_blank" class="file">%s</a>', $url, $url)
						)->addExtraClass('text-wrap')
					)
				)->setName("FilePreviewData")->addExtraClass('cms-file-info-data')
			)->setName("FilePreview")->addExtraClass('cms-file-info'),
			new TextField('CaptionText', _t('HtmlEditorField.CAPTIONTEXT', 'Caption text')),
			DropdownField::create(
				'CSSClass',
				_t('HtmlEditorField.CSSCLASS', 'Alignment / style'),
				array(
					'leftAlone' => _t('HtmlEditorField.CSSCLASSLEFTALONE', 'On the left, on its own.'),
					'center' => _t('HtmlEditorField.CSSCLASSCENTER', 'Centered, on its own.'),
					'left' => _t('HtmlEditorField.CSSCLASSLEFT', 'On the left, with text wrapping around.'),
					'right' => _t('HtmlEditorField.CSSCLASSRIGHT', 'On the right, with text wrapping around.')
				)
			)->addExtraClass('last')
		);
		if($file->Width != null){
			$fields->push(
				FieldGroup::create(
					_t('HtmlEditorField.IMAGEDIMENSIONS', 'Dimensions'),
					TextField::create(
						'Width', 
						_t('HtmlEditorField.IMAGEWIDTHPX', 'Width'), 
						$file->InsertWidth
					)->setMaxLength(5),
					TextField::create(
						'Height', 
						_t('HtmlEditorField.IMAGEHEIGHTPX', 'Height'), 
						$file->InsertHeight
					)->setMaxLength(5)
				)->addExtraClass('dimensions last')
			);
		}
		$urlField->dontEscape = true;

		if($file->Type == 'photo') {
			$fields->insertBefore(new TextField(
				'AltText', 
				_t('HtmlEditorField.IMAGEALTTEXT', 'Alternative text (alt) - shown if image cannot be displayed'), 
				$file->Title, 
				80
			), 'CaptionText');
			$fields->insertBefore(new TextField(
				'Title', 
				_t('HtmlEditorField.IMAGETITLE', 'Title text (tooltip) - for additional information about the image')
			), 'CaptionText');
		}

		$this->extend('updateFieldsForOembed', $fields, $url, $file);

		return $fields;
	}

	/**
	 * @return FieldList
	 */
	protected function getFieldsForFlash($url, $file) {
		$fields = new FieldList(
			FieldGroup::create(
				_t('HtmlEditorField.IMAGEDIMENSIONS', 'Dimensions'),
				TextField::create(
					'Width', 
					_t('HtmlEditorField.IMAGEWIDTHPX', 'Width'), 
					$file->Width
				)->setMaxLength(5),
				TextField::create(
					'Height', 
					" x " . _t('HtmlEditorField.IMAGEHEIGHTPX', 'Height'),
					$file->Height
				)->setMaxLength(5)
			)->addExtraClass('dimensions')
		);

		$this->extend('updateFieldsForFlash', $fields, $url, $file);

		return $fields;
	}

	/**
	 * @return FieldList
	 */
	protected function getFieldsForImage($url, $file) {
		if($file->File instanceof Image) {
			$formattedImage = $file->File->generateFormattedImage('SetWidth',
				Config::inst()->get('Image', 'asset_preview_width'));
			$thumbnailURL = $formattedImage ? $formattedImage->URL : $url;	
		} else {
			$thumbnailURL = $url;
		}
		
		$fields = new FieldList(
			CompositeField::create(
				CompositeField::create(
					LiteralField::create(
						"ImageFull",
						"<img id='thumbnailImage' class='thumbnail-preview' " 
							. "src='{$thumbnailURL}?r=" . rand(1,100000) . "' alt='{$file->Name}' />\n"
					)
				)->setName("FilePreviewImage")->addExtraClass('cms-file-info-preview'),
				CompositeField::create(
					CompositeField::create(
						new ReadonlyField("FileType", _t('AssetTableField.TYPE','File type'), $file->FileType),
						new ReadonlyField("Size", _t('AssetTableField.SIZE','File size'), $file->getSize()),
						$urlField = new ReadonlyField('ClickableURL', _t('AssetTableField.URL','URL'), 
							sprintf('<a href="%s" title="%s" target="_blank" class="file-url">%s</a>',
								$file->Link(), $file->Link(), $file->RelativeLink())
						),
						new DateField_Disabled("Created", _t('AssetTableField.CREATED','First uploaded'),
							$file->Created),
						new DateField_Disabled("LastEdited", _t('AssetTableField.LASTEDIT','Last changed'),
							$file->LastEdited)
					)
				)->setName("FilePreviewData")->addExtraClass('cms-file-info-data')
			)->setName("FilePreview")->addExtraClass('cms-file-info'),			

			TextField::create(
				'AltText', 
				_t('HtmlEditorField.IMAGEALT', 'Alternative text (alt)'),  
				$file->Title, 
				80
			)->setDescription(
				_t('HtmlEditorField.IMAGEALTTEXTDESC', 'Shown to screen readers or if image can not be displayed')),

			TextField::create(
				'Title', 
				_t('HtmlEditorField.IMAGETITLETEXT', 'Title text (tooltip)')
			)->setDescription(
				_t('HtmlEditorField.IMAGETITLETEXTDESC', 'For additional information about the image')),

			new TextField('CaptionText', _t('HtmlEditorField.CAPTIONTEXT', 'Caption text')),
			DropdownField::create(
				'CSSClass',
				_t('HtmlEditorField.CSSCLASS', 'Alignment / style'),
				array(
					'leftAlone' => _t('HtmlEditorField.CSSCLASSLEFTALONE', 'On the left, on its own.'),
					'center' => _t('HtmlEditorField.CSSCLASSCENTER', 'Centered, on its own.'),
					'left' => _t('HtmlEditorField.CSSCLASSLEFT', 'On the left, with text wrapping around.'),
					'right' => _t('HtmlEditorField.CSSCLASSRIGHT', 'On the right, with text wrapping around.')
				)
			)->addExtraClass('last')
		);
		if($file->Width != null){
			$fields->push(
				FieldGroup::create(_t('HtmlEditorField.IMAGEDIMENSIONS', 'Dimensions'),
					TextField::create(
						'Width', 
						_t('HtmlEditorField.IMAGEWIDTHPX', 'Width'), 
						$file->InsertWidth
					)->setMaxLength(5),
					TextField::create(
						'Height', 
						" x " . _t('HtmlEditorField.IMAGEHEIGHTPX', 'Height'),
						$file->InsertHeight
					)->setMaxLength(5)
				)->addExtraClass('dimensions last')
			);
		}
		$urlField->dontEscape = true;

		$this->extend('updateFieldsForImage', $fields, $url, $file);

		return $fields;
	}

	/**
	 * @param Int
	 * @return DataList
	 */
	protected function getFiles($parentID = null) {
		// TODO Use array('Filename:EndsWith' => $exts) once that's supported
		$exts = $this->getAllowedExtensions();
		$wheres = array();
		foreach($exts as $ext) $wheres[] = '"Filename" LIKE \'%.' . $ext . '\'';

		$files = File::get()->where(implode(' OR ', $wheres));
		
		// Limit by folder (if required)
		if($parentID) {
			$files = $files->filter('ParentID', $parentID);
		}

		return $files;
	}

	/**
	 * @return Array All extensions which can be handled by the different views.
	 */
	protected function getAllowedExtensions() {
		$exts = array('jpg', 'gif', 'png', 'swf','jpeg');
		$this->extend('updateAllowedExtensions', $exts);
		return $exts;
	}

}

/**
 * Encapsulation of a file which can either be a remote URL
 * or a {@link File} on the local filesystem, exhibiting common properties
 * such as file name or the URL.
 *
 * @todo Remove once core has support for remote files
 * @package forms
 * @subpackage fields-formattedinput
 */
class HtmlEditorField_File extends ViewableData {

	/** @var String */
	protected $url;

	/** @var File */
	protected $file;

	/**
	 * @param String
	 * @param File 
	 */
	public function __construct($url, $file = null) {
		$this->url = $url;
		$this->file = $file;
		$this->failover = $file;

		parent::__construct();
	}

	/**
	 * @return File Might not be set (for remote files)
	 */
	public function getFile() {
		return $this->file;
	}

	public function getURL() {
		return $this->url;
	}

	public function getName() {
		return ($this->file) ? $this->file->Name : preg_replace('/\?.*/', '', basename($this->url));
	}

	/**
	 * @return String HTML
	 */
	public function getPreview() {
		$preview = $this->extend('getPreview');
		if($preview) return $preview;

		if($this->file) {
			return $this->file->CMSThumbnail();
		} else {
			// Hack to use the framework's built-in thumbnail support without creating a local file representation
			$tmpFile = new File(array('Name' => $this->Name, 'Filename' => $this->Name));
			return $tmpFile->CMSThumbnail();
		}
	}

	public function getExtension() {
		return strtolower(($this->file) ? $this->file->Extension : pathinfo($this->Name, PATHINFO_EXTENSION));
	}

	public function appCategory() {
		if($this->file) {
			return $this->file->appCategory();
		} else {
			// Hack to use the framework's built-in thumbnail support without creating a local file representation
			$tmpFile = new File(array('Name' => $this->Name, 'Filename' => $this->Name));
			return $tmpFile->appCategory();			
		}
	}

}

/**
 * Encapsulation of an oembed tag, linking to an external media source.
 *
 * @see Oembed
 * @package forms
 * @subpackage fields-formattedinput
 */
class HtmlEditorField_Embed extends HtmlEditorField_File {
	protected $oembed;

	public function __construct($url, $file = null) {
		parent::__construct($url, $file);
		$this->oembed = Oembed::get_oembed_from_url($url);
		if(!$this->oembed) {
			$controller = Controller::curr();
			$controller->response->addHeader('X-Status',
				rawurlencode(_t(
					'HtmlEditorField.URLNOTANOEMBEDRESOURCE',
					"The URL '{url}' could not be turned into a media resource.",
					"The given URL is not a valid Oembed resource; the embed element couldn't be created.",
					array('url' => $url)
				)));
			$controller->response->setStatusCode(404);

			throw new SS_HTTPResponse_Exception($controller->response);
		}
	}

	public function getWidth() {
		return $this->oembed->Width ?: 100;
	}

	public function getHeight() {
		return $this->oembed->Height ?: 100;
	}

	/**
	 * Provide an initial width for inserted media, restricted based on $embed_width
	 * 
	 * @return int
	 */
	public function getInsertWidth() {
		$width = $this->getWidth();
		$maxWidth = Config::inst()->get('HtmlEditorField', 'insert_width');
		return ($width <= $maxWidth) ? $width : $maxWidth;
	}

	/**
	 * Provide an initial height for inserted media, scaled proportionally to the initial width
	 * 
	 * @return int
	 */
	public function getInsertHeight() {
		$width = $this->getWidth();
		$height = $this->getHeight();
		$maxWidth = Config::inst()->get('HtmlEditorField', 'insert_width');
		return ($width <= $maxWidth) ? $height : round($height*($maxWidth/$width));
	}

	public function getPreview() {
		if(isset($this->oembed->thumbnail_url)) {
			return sprintf('<img src="%s" />', $this->oembed->thumbnail_url);
		}
	}

	public function getName() {
		if(isset($this->oembed->title)) {
			return $this->oembed->title;
		} else {
			return parent::getName();
		}
	}

	public function getType() {
		return $this->oembed->type;
	}

	public function getOembed() {
		return $this->oembed;
	}

	public function appCategory() {
		return 'embed';
	}
	
	public function getInfo() {
		return $this->oembed->info;
	}
}

/**
 * Encapsulation of an image tag, linking to an image either internal or external to the site.
 *
 * @package forms
 * @subpackage fields-formattedinput
 */
class HtmlEditorField_Image extends HtmlEditorField_File {

	protected $width;

	protected $height;

	public function __construct($url, $file = null) {
		parent::__construct($url, $file);

		// Get dimensions for remote file
		$info = @getimagesize($url);
		if($info) {
			$this->width = $info[0];
			$this->height = $info[1];
		}
	}

	public function getWidth() {
		return ($this->file) ? $this->file->Width : $this->width;
	}

	public function getHeight() {
		return ($this->file) ? $this->file->Height : $this->height;
	}

	/**
	 * Provide an initial width for inserted image, restricted based on $embed_width
	 * 
	 * @return int
	 */
	public function getInsertWidth() {
		$width = $this->getWidth();
		$maxWidth = Config::inst()->get('HtmlEditorField', 'insert_width');
		return ($width <= $maxWidth) ? $width : $maxWidth;
	}

	/**
	 * Provide an initial height for inserted image, scaled proportionally to the initial width
	 * 
	 * @return int
	 */
	public function getInsertHeight() {
		$width = $this->getWidth();
		$height = $this->getHeight();
		$maxWidth = Config::inst()->get('HtmlEditorField', 'insert_width');
		return ($width <= $maxWidth) ? $height : round($height*($maxWidth/$width));
	}

	public function getPreview() {
		return ($this->file) ? $this->file->CMSThumbnail() : sprintf('<img src="%s" />', $this->url);
	}

}
