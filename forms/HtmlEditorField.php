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
	 * @var Boolean Use TinyMCE's GZIP compressor
	 */
	static $use_gzip = true;

	protected $rows = 30;
	
	/**
	 * Includes the JavaScript neccesary for this field to work using the {@link Requirements} system.
	 */
	public static function include_js() {
		require_once 'tinymce/tiny_mce_gzip.php';

		$configObj = HtmlEditorConfig::get_active();

		if(self::$use_gzip) {
			$internalPlugins = array();
			foreach($configObj->getPlugins() as $plugin => $path) if(!$path) $internalPlugins[] = $plugin;
			$tag = TinyMCE_Compressor::renderTag(array(
				'url' => THIRDPARTY_DIR . '/tinymce/tiny_mce_gzip.php',
				'plugins' => implode(',', $internalPlugins),
				'themes' => 'advanced',
				'languages' => $configObj->getOption('language')
			), true);
			preg_match('/src="([^"]*)"/', $tag, $matches);
			Requirements::javascript($matches[1]);

		} else {
			Requirements::javascript(MCE_ROOT . 'tiny_mce_src.js');
		} 

		Requirements::customScript($configObj->generateJS(), 'htmlEditorConfig');
	}
	
	/**
	 * @see TextareaField::__construct()
	 */
	public function __construct($name, $title = null, $value = '') {
		if(count(func_get_args()) > 3) Deprecation::notice('3.0', 'Use setRows() and setCols() instead of constructor arguments', Deprecation::SCOPE_GLOBAL);

		parent::__construct($name, $title, $value);
		
		self::include_js();
	}
	
	/**
	 * @return string
	 */
	function Field($properties = array()) {
		// mark up broken links
		$value  = new SS_HTMLValue($this->value);
		
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

		return $this->createTag (
			'textarea',
			$this->getAttributes(),
			htmlentities($value->getContent(), ENT_COMPAT, 'UTF-8')
		);
	}

	function getAttributes() {
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
		if($record->escapeTypeForField($this->name) != 'xml') {
			throw new Exception (
				'HtmlEditorField->saveInto(): This field should save into a HTMLText or HTMLVarchar field.'
			);
		}
		
		$linkedPages = array();
		$linkedFiles = array();
		
		$htmlValue = new SS_HTMLValue($this->value);
		
		if(class_exists('SiteTree')) {
			// Populate link tracking for internal links & links to asset files.
			if($links = $htmlValue->getElementsByTagName('a')) foreach($links as $link) {
				$href = Director::makeRelative($link->getAttribute('href'));

				if($href) {
					if(preg_match('/\[sitetree_link,id=([0-9]+)\]/i', $href, $matches)) {
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
					if($resized) $img->setAttribute('src', $resized->getRelativePath());
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
		if(class_exists('SiteTree')) {
			if($record->ID && $record->many_many('LinkTracking') && $tracker = $record->LinkTracking()) {
			    $tracker->removeByFilter(sprintf('"FieldName" = \'%s\' AND "SiteTreeID" = %d', $this->name, $record->ID));

				if($linkedPages) foreach($linkedPages as $item) {
					$SQL_fieldName = Convert::raw2sql($this->name);
					DB::query("INSERT INTO \"SiteTree_LinkTracking\" (\"SiteTreeID\",\"ChildID\", \"FieldName\")
						VALUES ($record->ID, $item, '$SQL_fieldName')");
				}
			}
		
			if($record->ID && $record->many_many('ImageTracking') && $tracker = $record->ImageTracking()) {
			    $tracker->where(sprintf('"FieldName" = \'%s\' AND "SiteTreeID" = %d', $this->name, $record->ID))->removeAll();

				$fieldName = $this->name;
				if($linkedFiles) foreach($linkedFiles as $item) {
					$tracker->add($item, array('FieldName' => $this->name));
				}
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
	function Field($properties = array()) {
		$valforInput = $this->value ? Convert::raw2att($this->value) : "";
		return "<span class=\"readonly typography\" id=\"" . $this->id() . "\">" . ( $this->value && $this->value != '<p></p>' ? $this->value : '<i>(not set)</i>' ) . "</span><input type=\"hidden\" name=\"".$this->name."\" value=\"".$valforInput."\" />";
	}
	function Type() {
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

	static $allowed_actions = array(
		'LinkForm',
		'MediaForm',
		'viewfile'
	);

	/**
	 * @var string
	 */
	protected $templateViewFile = 'HtmlEditorField_viewfile';

	protected $controller, $name;
	
	function __construct($controller, $name) {
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
			Controller::join_links($this->controller->Link($this->name), 'LinkForm', 'forTemplate'),
			Controller::join_links($this->controller->Link($this->name), 'MediaForm', 'forTemplate')
		);
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
		
		$numericLabelTmpl = '<span class="step-label"><span class="flyout">%d</span><span class="arrow"></span><strong class="title">%s</strong></span>';
		$form = new Form(
			$this->controller,
			"{$this->name}/LinkForm", 
			new FieldList(
				$headerWrap = new CompositeField(
					new LiteralField(
						'Heading', 
						sprintf('<h3 class="htmleditorfield-mediaform-heading insert">%s</h3>', _t('HtmlEditorField.LINK', 'Insert Link'))
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
						'<div class="step2">' . sprintf($numericLabelTmpl, '2', _t('HtmlEditorField.DETAILS', 'Details')) . '</div>'
					),
					$siteTree,
					new TextField('external', _t('HtmlEditorField.URL', 'URL'), 'http://'),
					new EmailField('email', _t('HtmlEditorField.EMAIL', 'Email address')),
					new TreeDropdownField('file', _t('HtmlEditorField.FILE', 'File'), 'File', 'ID', 'Title', true),
					new TextField('Anchor', _t('HtmlEditorField.ANCHORVALUE', 'Anchor')),
					new TextField('Description', _t('HtmlEditorField.LINKDESCR', 'Link description')),
					new CheckboxField('TargetBlank', _t('HtmlEditorField.LINKOPENNEWWIN', 'Open link in a new window?')),
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
	function MediaForm() {
		// TODO Handle through GridState within field - currently this state set too late to be useful here (during request handling)
		$parentID = $this->controller->getRequest()->requestVar('ParentID');

		$fileFieldConfig = GridFieldConfig::create()->addComponents(
			new GridFieldFilterHeader(),
			new GridFieldSortableHeader(),
			new GridFieldDataColumns(),
			new GridFieldPaginator(5),
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
		
		$numericLabelTmpl = '<span class="step-label"><span class="flyout">%d</span><span class="arrow"></span><strong class="title">%s</strong></span>';

		$fromCMS = new CompositeField(
			new LiteralField('headerSelect', '<h4>' . sprintf($numericLabelTmpl, '1', _t('HtmlEditorField.FindInFolder', 'Find in Folder')) . '</h4>'),
				$select = new TreeDropdownField('ParentID', "", 'Folder'),	
				$fileField
		);
		
		$fromCMS->addExtraClass('content ss-uploadfield from-CMS');
		$select->addExtraClass('content-select');


		$fromWeb = new CompositeField(
			new LiteralField('headerURL', '<h4>' . sprintf($numericLabelTmpl, '1', _t('HtmlEditorField.ADDURL', 'Add URL')) . '</h4>'),
			$remoteURL = new TextField('RemoteURL', 'http://'),
			new LiteralField('addURLImage', '<button class="action ui-action-constructive ui-button field add-url" data-icon="addMedia"></button>')
		);

		$remoteURL->addExtraClass('remoteurl');
		$fromWeb->addExtraClass('content ss-uploadfield from-web');

		Requirements::css(FRAMEWORK_DIR . '/css/AssetUploadField.css');
		$computerUploadField = Object::create('UploadField', 'AssetUploadField', '');
		$computerUploadField->setConfig('previewMaxWidth', 40);
		$computerUploadField->setConfig('previewMaxHeight', 30);
		$computerUploadField->addExtraClass('ss-assetuploadfield');
		$computerUploadField->removeExtraClass('ss-uploadfield');
		$computerUploadField->setTemplate('HtmlEditorField_UploadField');
		$computerUploadField->setFolderName(Upload::$uploads_folder);

		$tabSet = new TabSet(
			"MediaFormInsertMediaTabs",
			new Tab(
				_t('HtmlEditorField.FROMCOMPUTER','From your computer'),
				$computerUploadField
			),
			new Tab(
				_t('HtmlEditorField.FROMWEB', 'From the web'),
				$fromWeb
			),
			new Tab(
				_t('HtmlEditorField.FROMCMS','From the CMS'),
				$fromCMS
			)
		);

		$allFields = new CompositeField(
			$tabSet,
			new LiteralField('headerEdit', '<h4 class="field header-edit">' . sprintf($numericLabelTmpl, '2', _t('HtmlEditorField.ADJUSTDETAILSDIMENSIONS', 'Details &amp; dimensions')) . '</h4>'),
			$editComposite = new CompositeField(
				new LiteralField('contentEdit', '<div class="content-edit ss-uploadfield-files files"></div>')
			)
		);

		$allFields->addExtraClass('ss-insert-media');

		$headings = new CompositeField(
			new LiteralField(
				'Heading',
				sprintf('<h3 class="htmleditorfield-mediaform-heading insert">%s</h3>', _t('HtmlEditorField.INSERTMEDIA', 'Insert Media')).
				sprintf('<h3 class="htmleditorfield-mediaform-heading update">%s</h3>', _t('HtmlEditorField.UpdateMEDIA', 'Update Media'))
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
		// Check if appCategory is an image and exists on the local system, otherwise use oEmbed to refference a remote image
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
		
		$previewField = new LiteralField("ImageFull",
			"<img id='thumbnailImage' class='thumbnail-preview' src='{$thumbnailURL}?r=" . rand(1,100000)  . "' alt='{$file->Name}' />\n"
		);

		if($file->Width != null){
			$dimensionsField = new FieldGroup(_t('HtmlEditorField.IMAGEDIMENSIONS', 'Dimensions'),
				$widthField = new TextField('Width', _t('HtmlEditorField.IMAGEWIDTHPX', 'Width'), $file->Width),
				$heightField = new TextField('Height', _t('HtmlEditorField.IMAGEHEIGHTPX', 'Height'), $file->Height)
			);
		}

		
		$fields = new FieldList(
			$filePreview = CompositeField::create(
				CompositeField::create(
					$previewField
				)->setName("FilePreviewImage")->addExtraClass('cms-file-info-preview'),
				CompositeField::create(
					CompositeField::create(
						new ReadonlyField("FileType", _t('AssetTableField.TYPE','File type') . ':', $file->Type),
						$urlField = new ReadonlyField('ClickableURL', _t('AssetTableField.URL','URL'),
							sprintf('<a href="%s" target="_blank" class="file">%s</a>', $url, $url)
						)
					)
				)->setName("FilePreviewData")->addExtraClass('cms-file-info-data')
			)->setName("FilePreview")->addExtraClass('cms-file-info'),
			new TextField('CaptionText', _t('HtmlEditorField.CAPTIONTEXT', 'Caption text')),
			$alignment = new DropdownField(
				'CSSClass',
				_t('HtmlEditorField.CSSCLASS', 'Alignment / style'),
				array(
					'left' => _t('HtmlEditorField.CSSCLASSLEFT', 'On the left, with text wrapping around.'),
					'leftAlone' => _t('HtmlEditorField.CSSCLASSLEFTALONE', 'On the left, on its own.'),
					'right' => _t('HtmlEditorField.CSSCLASSRIGHT', 'On the right, with text wrapping around.'),
					'center' => _t('HtmlEditorField.CSSCLASSCENTER', 'Centered, on its own.'),
				)
			),
			$dimensionsField
		);
		$urlField->addExtraClass('text-wrap');
		$urlField->dontEscape = true;
		if($dimensionsField){
			$dimensionsField->addExtraClass('dimensions last');
			$widthField->setMaxLength(5);
			$heightField->setMaxLength(5);
		}else{
			$alignment->addExtraClass('last');
		}


		if($file->Type == 'photo') {
			$filePreview->FieldList()->insertBefore(new TextField(
				'AltText', 
				_t('HtmlEditorField.IMAGEALTTEXT', 'Alternative text (alt) - shown if image cannot be displayed'), 
				$file->Title, 
				80
			), 'CaptionText');
			$filePreview->FieldList()->insertBefore(new TextField(
				'Title', 
				_t('HtmlEditorField.IMAGETITLE', 'Title text (tooltip) - for additional information about the image')
			), 'CaptionText');
		}

		$this->extend('updateFieldsForImage', $fields, $url, $file);

		return $fields;
	}

	/**
	 * @return FieldList
	 */
	protected function getFieldsForFlash($url, $file) {
		$fields = new FieldList(
			$dimensionsField = new FieldGroup(_t('HtmlEditorField.IMAGEDIMENSIONS', 'Dimensions'),
				$widthField = new TextField('Width', _t('HtmlEditorField.IMAGEWIDTHPX', 'Width'), $file->Width),
				$heightField = new TextField('Height', " x " . _t('HtmlEditorField.IMAGEHEIGHTPX', 'Height'), $file->Height)
			)
		);
		$dimensionsField->addExtraClass('dimensions');
		$widthField->setMaxLength(5);
		$heightField->setMaxLength(5);

		$this->extend('updateFieldsForFlash', $fields, $url, $file);

		return $fields;
	}

	/**
	 * @return FieldList
	 */
	protected function getFieldsForImage($url, $file) {
		if($file->File instanceof Image) {
			$formattedImage = $file->File->generateFormattedImage('SetWidth', Image::$asset_preview_width);
			$thumbnailURL = $formattedImage ? $formattedImage->URL : $url;	
		} else {
			$thumbnailURL = $url;
		}
		
		$previewField = new LiteralField("ImageFull",
			"<img id='thumbnailImage' class='thumbnail-preview' src='{$thumbnailURL}?r=" . rand(1,100000)  . "' alt='{$file->Name}' />\n"
		);

		if($file->Width != null){
			$dimensionsField = new FieldGroup(_t('HtmlEditorField.IMAGEDIMENSIONS', 'Dimensions'),
				$widthField = new TextField('Width', _t('HtmlEditorField.IMAGEWIDTHPX', 'Width'), $file->Width),
				$heightField = new TextField('Height', " x " . _t('HtmlEditorField.IMAGEHEIGHTPX', 'Height'), $file->Height)
			);
		}

		$fields = new FieldList(
			$filePreview = CompositeField::create(
				CompositeField::create(
					$previewField
				)->setName("FilePreviewImage")->addExtraClass('cms-file-info-preview'),
				CompositeField::create(
					CompositeField::create(
						new ReadonlyField("FileType", _t('AssetTableField.TYPE','File type') . ':', $file->FileType),
						new ReadonlyField("Size", _t('AssetTableField.SIZE','File size') . ':', $file->getSize()),
						$urlField = new ReadonlyField('ClickableURL', _t('AssetTableField.URL','URL'), 
							sprintf('<a href="%s" target="_blank" class="file-url">%s</a>', $file->Link(), $file->RelativeLink())
						),
						new DateField_Disabled("Created", _t('AssetTableField.CREATED','First uploaded') . ':', $file->Created),
						new DateField_Disabled("LastEdited", _t('AssetTableField.LASTEDIT','Last changed') . ':', $file->LastEdited)
					)
				)->setName("FilePreviewData")->addExtraClass('cms-file-info-data')
			)->setName("FilePreview")->addExtraClass('cms-file-info'),			
			TextField::create(
				'AltText', 
				_t('HtmlEditorField.IMAGEALT', 'Alternative text (alt)'),  
				$file->Title, 
				80
			)->setDescription(_t('HtmlEditorField.IMAGEALTTEXTDESC', 'Shown to screen readers or if image can not be displayed')),
			TextField::create(
				'Title', 
				_t('HtmlEditorField.IMAGETITLETEXT', 'Title text (tooltip)')
			)->setDescription(_t('HtmlEditorField.IMAGETITLETEXTDESC', 'For additional information about the image')),
			new TextField('CaptionText', _t('HtmlEditorField.CAPTIONTEXT', 'Caption text')),
			$alignment = new DropdownField(
				'CSSClass',
				_t('HtmlEditorField.CSSCLASS', 'Alignment / style'),
				array(
					'left' => _t('HtmlEditorField.CSSCLASSLEFT', 'On the left, with text wrapping around.'),
					'leftAlone' => _t('HtmlEditorField.CSSCLASSLEFTALONE', 'On the left, on its own.'),
					'right' => _t('HtmlEditorField.CSSCLASSRIGHT', 'On the right, with text wrapping around.'),
					'center' => _t('HtmlEditorField.CSSCLASSCENTER', 'Centered, on its own.'),
				)
			),
			$dimensionsField			
		);
		$urlField->dontEscape = true;
		if($dimensionsField){
			$dimensionsField->addExtraClass('dimensions last');			
			$widthField->setMaxLength(5);
			$heightField->setMaxLength(5);
		}else{
			$alignment->addExtraClass('last');
		}
		

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
	function __construct($url, $file = null) {
		$this->url = $url;
		$this->file = $file;
		$this->failover = $file;

		parent::__construct();
	}

	/**
	 * @return File Might not be set (for remote files)
	 */
	function getFile() {
		return $this->file;
	}

	function getURL() {
		return $this->url;
	}

	function getName() {
		return ($this->file) ? $this->file->Name : preg_replace('/\?.*/', '', basename($this->url));
	}

	/**
	 * @return String HTML
	 */
	function getPreview() {
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

	function getExtension() {
		return strtolower(($this->file) ? $this->file->Extension : pathinfo($this->Name, PATHINFO_EXTENSION));
	}

	function appCategory() {
		if($this->file) {
			return $this->file->appCategory();
		} else {
			// Hack to use the framework's built-in thumbnail support without creating a local file representation
			$tmpFile = new File(array('Name' => $this->Name, 'Filename' => $this->Name));
			return $tmpFile->appCategory();			
		}
	}

}

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
					"The URL that has been passed is not a valid Oembed resource, and the embed element could not be created.",
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

class HtmlEditorField_Image extends HtmlEditorField_File {

	protected $width;

	protected $height;

	function __construct($url, $file = null) {
		parent::__construct($url, $file);

		// Get dimensions for remote file
		$info = @getimagesize($url);
		if($info) {
			$this->width = $info[0];
			$this->height = $info[1];
		}
	}

	function getWidth() {
		return ($this->file) ? $this->file->Width : $this->width;
	}

	function getHeight() {
		return ($this->file) ? $this->file->Height : $this->height;
	}

	function getPreview() {
		return ($this->file) ? $this->file->CMSThumbnail() : sprintf('<img src="%s" />', $this->url);
	}

}
