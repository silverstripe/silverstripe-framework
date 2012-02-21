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
		if(count(func_get_args()) > 3) Deprecation::notice('3.0', 'Use setRows() and setCols() instead of constructor arguments');

		parent::__construct($name, $title, $value);
		
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
	
	public function saveInto($record) {
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
	function Field() {
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

		Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/jquery/jquery.js");
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery-ui.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(SAPPHIRE_ADMIN_DIR . '/javascript/ssui.core.js');
		Requirements::javascript(SAPPHIRE_DIR ."/javascript/HtmlEditorField.js");

		Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css');
		
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
		
		$numericLabelTmpl = '<span class="step-label"><span class="flyout">%d</span><span class="arrow"></span><strong class="title">%s</strong></span>';
		$form = new Form(
			$this->controller,
			"{$this->name}/LinkForm", 
			new FieldList(
				new LiteralField(
					'Heading', 
					sprintf('<h3>%s</h3>', _t('HtmlEditorField.LINK', 'Link'))
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
						)
					),
					new LiteralField('Step2',
						'<div class="step2">' . sprintf($numericLabelTmpl, '2', _t('HtmlEditorField.DETAILS', 'Details')) . '</div>'
					),
					$siteTree,
					new TextField('external', _t('HtmlEditorField.URL', 'URL'), 'http://'),
					new EmailField('email', _t('HtmlEditorField.EMAIL', 'Email address')),
					new TreeDropdownField('file', _t('HtmlEditorField.FILE', 'File'), 'File', 'Filename', 'Title', true),
					new TextField('Anchor', _t('HtmlEditorField.ANCHORVALUE', 'Anchor')),
					new TextField('Description', _t('HtmlEditorField.LINKDESCR', 'Link description')),
					new CheckboxField('TargetBlank', _t('HtmlEditorField.LINKOPENNEWWIN', 'Open link in a new window?')),
					new HiddenField('Locale', null, $this->controller->Locale)
				)
			),
			new FieldList(
				Object::create('ResetFormAction', 'remove', _t('HtmlEditorField.BUTTONREMOVELINK', 'Remove link'))
					->addExtraClass('ss-ui-action-destructive')
					->setUseButtonTag(true)
				,
				FormAction::create('insert', _t('HtmlEditorField.BUTTONINSERTLINK', 'Insert link'))
					->addExtraClass('ss-ui-action-constructive')
					->setAttribute('data-icon', 'accept')
					->setUseButtonTag(true)
			)
		);
		
		$contentComposite->addExtraClass('content');
		
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

		$fileFieldConfig = GridFieldConfig::create();
		$fileFieldConfig->addComponent(new GridFieldSortableHeader());
		$fileFieldConfig->addComponent(new GridFieldFilter());
		$fileFieldConfig->addComponent(new GridFieldDefaultColumns());
		$fileFieldConfig->addComponent(new GridFieldPaginator(5));
		$fileField = new GridField('Files', false, null, $fileFieldConfig);
		$fileField->setList($this->getFiles($parentID));
		$fileField->setAttribute('data-selectable', true);
		$fileField->setAttribute('data-multiselect', true);
		$fileField->setDisplayFields(array(
			'CMSThumbnail' => false,
			'Name' => _t('File.Name'),
		));
		
		$numericLabelTmpl = '<span class="step-label"><span class="flyout">%d</span><span class="arrow"></span><strong class="title">%s</strong></span>';
		$fields = new FieldList(
			new LiteralField(
				'Heading', 
				sprintf('<h3>%s</h3>', _t('HtmlEditorField.IMAGE', 'Image'))
			),
			
			$contentComposite = new CompositeField(
				new LiteralField('headerSelect', '<h4 class="field header-select">' . sprintf($numericLabelTmpl, '1', _t('HtmlEditorField.Find', 'Find')) . '</h4>'),
				$selectComposite = new CompositeField(
					new TreeDropdownField('ParentID', _t('HtmlEditorField.FOLDER', 'Folder'), 'Folder'),
					$fileField
				),
				
				new LiteralField('headerEdit', '<h4 class="field header-edit">' . sprintf($numericLabelTmpl, '2', _t('HtmlEditorField.EditDetails', 'Edit details')) . '</h4>'),
				$editComposite = new CompositeField(
					new LiteralField('contentEdit', '<div class="content-edit"></div>')
				)
				
			)
		);
		
		$actions = new FieldList(
			FormAction::create('insertimage', _t('HtmlEditorField.BUTTONINSERT', 'Insert'))
				->addExtraClass('ss-ui-action-constructive')
				->setAttribute('data-icon', 'accept')
				->setUseButtonTag(true)
		);

		$form = new Form(
			$this->controller,
			"{$this->name}/MediaForm",
			$fields,
			$actions
		);
		
		$contentComposite->addExtraClass('content');
		$selectComposite->addExtraClass('content-select');
		
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
			if(Director::is_absolute_url($url)) {
				$url = $url;
				$file = null;	
			} else {
				$url = Director::makeRelative($request->getVar('FileURL'));
				$url = ereg_replace('_resampled/[^-]+-','',$url);
				$file = DataList::create('File')->filter('Filename', $url)->first();	
				if(!$file) $file = new File(array('Title' => basename($url)));	
			}
		} elseif($id = $request->getVar('ID')) {
			$file = DataObject::get_by_id('File', $id);
			$url = $file->RelativeLink();
		} else {
			throw new LogicException('Need either "ID" or "FileURL" parameter to identify the file');
		}

		// Instanciate file wrapper and get fields based on its type
		if($file && $file->appCategory() == 'image') {
			$fileWrapper = new HtmlEditorField_Image($url, $file);
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
			if($file->Extension == 'swf') {
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
		$fields = new FieldList(
			new TextField(
				'AltText', 
				_t('HtmlEditorField.IMAGEALTTEXT', 'Alternative text (alt) - shown if image cannot be displayed'), 
				$file->Title, 
				80
			),
			new TextField(
				'Title', 
				_t('HtmlEditorField.IMAGETITLE', 'Title text (tooltip) - for additional information about the image')
			),
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
			$dimensionsField = new FieldGroup(_t('HtmlEditorField.IMAGEDIMENSIONS', 'Dimensions'),
				$widthField = new TextField('Width', _t('HtmlEditorField.IMAGEWIDTHPX', 'Width'), $file->Width),
				$heightField = new TextField('Height', " x " . _t('HtmlEditorField.IMAGEHEIGHTPX', 'Height'), $file->Height)
			)
		);
		$dimensionsField->addExtraClass('dimensions');
		$widthField->setMaxLength(5);
		$heightField->setMaxLength(5);

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

		$files = DataList::create('File')->where(implode(' OR ', $wheres));
		
		// Limit by folder (if required)
		if($parentID) $files->filter('ParentID', $parentID);
		
		return $files;
	}

	/**
	 * @return Array All extensions which can be handled by the different views.
	 */
	protected function getAllowedExtensions() {
		$exts = array('jpg', 'gif', 'png', 'swf');
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