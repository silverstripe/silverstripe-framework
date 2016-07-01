<?php
use Embed\Adapters\AdapterInterface;
use Embed\Embed;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\DataObject;


/**
 * A TinyMCE-powered WYSIWYG HTML editor field with image and link insertion and tracking capabilities. Editor fields
 * are created from <textarea> tags, which are then converted with JavaScript.
 *
 * @package forms
 * @subpackage fields-formattedinput
 */
class HTMLEditorField extends TextareaField {

	/**
	 * Use TinyMCE's GZIP compressor
	 *
	 * @config
	 * @var bool
	 */
	private static $use_gzip = true;

	/**
	 * Should we check the valid_elements (& extended_valid_elements) rules from HTMLEditorConfig server side?
	 *
	 * @config
	 * @var bool
	 */
	private static $sanitise_server_side = false;

	/**
	 * Number of rows
	 *
	 * @config
	 * @var int
	 */
	private static $default_rows = 30;

	/**
	 * ID or instance of editorconfig
	 *
	 * @var string|HTMLEditorConfig
	 */
	protected $editorConfig = null;

	/**
	 * Gets the HTMLEditorConfig instance
	 *
	 * @return HTMLEditorConfig
	 */
	public function getEditorConfig() {
		// Instance override
		if($this->editorConfig instanceof HTMLEditorConfig) {
			return $this->editorConfig;
		}

		// Get named / active config
		return HTMLEditorConfig::get($this->editorConfig);
	}

	/**
	 * Assign a new configuration instance or identifier
	 *
	 * @param string|HTMLEditorConfig $config
	 * @return $this
	 */
	public function setEditorConfig($config) {
		$this->editorConfig = $config;
		return $this;
	}

	/**
	 * Creates a new HTMLEditorField.
	 * @see TextareaField::__construct()
	 *
	 * @param string $name The internal field name, passed to forms.
	 * @param string $title The human-readable field label.
	 * @param mixed $value The value of the field.
	 * @param string $config HTMLEditorConfig identifier to be used. Default to the active one.
	 */
	public function __construct($name, $title = null, $value = '', $config = null) {
		parent::__construct($name, $title, $value);

		if($config) {
			$this->setEditorConfig($config);
		}

		$this->setRows($this->config()->default_rows);
	}

	public function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			$this->getEditorConfig()->getAttributes()
		);
	}

	public function saveInto(DataObjectInterface $record) {
		if($record->hasField($this->name) && $record->escapeTypeForField($this->name) != 'xml') {
			throw new Exception (
				'HTMLEditorField->saveInto(): This field should save into a HTMLText or HTMLVarchar field.'
			);
		}

		// Sanitise if requested
		$htmlValue = Injector::inst()->create('HTMLValue', $this->Value());
		if($this->config()->sanitise_server_side) {
			$santiser = Injector::inst()->create('HTMLEditorSanitiser', HTMLEditorConfig::get_active());
			$santiser->sanitise($htmlValue);
		}

		// optionally manipulate the HTML after a TinyMCE edit and prior to a save
		$this->extend('processHTML', $htmlValue);

		// Store into record
		$record->{$this->name} = $htmlValue->getContent();
	}

	public function setValue($value) {
		// Regenerate links prior to preview, so that the editor can see them.
		$value = Image::regenerate_html_links($value);
		return parent::setValue($value);
	}

	/**
	 * @return HTMLEditorField_Readonly
	 */
	public function performReadonlyTransformation() {
		$field = $this->castedCopy('HTMLEditorField_Readonly');
		$field->dontEscape = true;

		return $field;
	}

	public function performDisabledTransformation() {
		return $this->performReadonlyTransformation();
	}

	public function Field($properties = array()) {
		// Include requirements
		$this->getEditorConfig()->init();
		return parent::Field($properties);
	}
}

/**
 * Readonly version of an {@link HTMLEditorField}.
 * @package forms
 * @subpackage fields-formattedinput
 */
class HTMLEditorField_Readonly extends ReadonlyField {
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
class HTMLEditorField_Toolbar extends RequestHandler {

	private static $allowed_actions = array(
		'LinkForm',
		'MediaForm',
		'viewfile',
		'getanchors'
	);

	/**
	 * @var string
	 */
	protected $templateViewFile = 'HTMLEditorField_viewfile';

	protected $controller, $name;

	public function __construct($controller, $name) {
		parent::__construct();

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
		return DataObject::get($sourceObject)->filterAny(array(
			'MenuTitle:PartialMatch' => $search,
			'Title:PartialMatch' => $search
		));
	}

	/**
	 * Return a {@link Form} instance allowing a user to
	 * add links in the TinyMCE content editor.
	 *
	 * @return Form
	 */
	public function LinkForm() {
		$siteTree = TreeDropdownField::create('internal', _t('HTMLEditorField.PAGE', "Page"),
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
							_t('HTMLEditorField.LINK', 'Insert Link'))
					)
				),
				$contentComposite = new CompositeField(
					OptionsetField::create(
						'LinkType',
						sprintf($numericLabelTmpl, '1', _t('HTMLEditorField.LINKTO', 'Link to')),
						array(
							'internal' => _t('HTMLEditorField.LINKINTERNAL', 'Page on the site'),
							'external' => _t('HTMLEditorField.LINKEXTERNAL', 'Another website'),
							'anchor' => _t('HTMLEditorField.LINKANCHOR', 'Anchor on this page'),
							'email' => _t('HTMLEditorField.LINKEMAIL', 'Email address'),
							'file' => _t('HTMLEditorField.LINKFILE', 'Download a file'),
						),
						'internal'
					),
					LiteralField::create('Step2',
						'<div class="step2">'
						. sprintf($numericLabelTmpl, '2', _t('HTMLEditorField.DETAILS', 'Details')) . '</div>'
					),
					$siteTree,
					TextField::create('external', _t('HTMLEditorField.URL', 'URL'), 'http://'),
					EmailField::create('email', _t('HTMLEditorField.EMAIL', 'Email address')),
					$fileField = UploadField::create('file', _t('HTMLEditorField.FILE', 'File')),
					TextField::create('Anchor', _t('HTMLEditorField.ANCHORVALUE', 'Anchor')),
					TextField::create('Subject', _t('HTMLEditorField.SUBJECT', 'Email subject')),
					TextField::create('Description', _t('HTMLEditorField.LINKDESCR', 'Link description')),
					CheckboxField::create('TargetBlank',
						_t('HTMLEditorField.LINKOPENNEWWIN', 'Open link in a new window?')),
					HiddenField::create('Locale', null, $this->controller->Locale)
				)
			),
			new FieldList()
		);

		$headerWrap->addExtraClass('CompositeField composite cms-content-header nolabel ');
		$contentComposite->addExtraClass('ss-insert-link content');
		$fileField->setAllowedMaxFileNumber(1);

		$form->unsetValidator();
		$form->loadDataFrom($this);
		$form->addExtraClass('htmleditorfield-form htmleditorfield-linkform cms-mediaform-content');

		$this->extend('updateLinkForm', $form);

		return $form;
	}

	/**
	 * Get the folder ID to filter files by for the "from cms" tab
	 *
	 * @return int
	 */
	protected function getAttachParentID() {
		$parentID = $this->controller->getRequest()->requestVar('ParentID');
		$this->extend('updateAttachParentID', $parentID);
		return $parentID;
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
		$parentID = $this->getAttachParentID();

		$fileFieldConfig = GridFieldConfig::create()->addComponents(
			new GridFieldFilterHeader(),
			new GridFieldSortableHeader(),
			new GridFieldDataColumns(),
			new GridFieldPaginator(7),
			// TODO Shouldn't allow delete here, its too confusing with a "remove from editor view" action.
			// Remove once we can fit the search button in the last actual title column
			new GridFieldDeleteAction(),
			new GridFieldDetailForm()
		);
		$fileField = GridField::create('Files', false, null, $fileFieldConfig);
		$fileField->setList($this->getFiles($parentID));
		$fileField->setAttribute('data-selectable', true);
		$fileField->setAttribute('data-multiselect', true);
		$columns = $fileField->getConfig()->getComponentByType('GridFieldDataColumns');
		$columns->setDisplayFields(array(
			'StripThumbnail' => false,
			'Title' => _t('File.Title'),
			'Created' => singleton('File')->fieldLabel('Created'),
		));
		$columns->setFieldCasting(array(
			'Created' => 'DBDatetime->Nice'
		));

		$fromCMS = new CompositeField(
			$select = TreeDropdownField::create('ParentID', "", 'Folder')
				->addExtraClass('noborder')
				->setValue($parentID),
			$fileField
		);

		$fromCMS->addExtraClass('content ss-uploadfield htmleditorfield-from-cms');
		$select->addExtraClass('content-select');


		$URLDescription = _t('HTMLEditorField.URLDESCRIPTION', 'Insert videos and images from the web into your page simply by entering the URL of the file. Make sure you have the rights or permissions before sharing media directly from the web.<br /><br />Please note that files are not added to the file store of the CMS but embeds the file from its original location, if for some reason the file is no longer available in its original location it will no longer be viewable on this page.');
		$fromWeb = new CompositeField(
			$description = new LiteralField('URLDescription', '<div class="url-description">' . $URLDescription . '</div>'),
			$remoteURL = new TextField('RemoteURL', 'http://'),
			new LiteralField('addURLImage',
				'<button type="button" class="action ui-action-constructive ui-button field font-icon-plus add-url">' .
				_t('HTMLEditorField.BUTTONADDURL', 'Add url').'</button>')
		);

		$remoteURL->addExtraClass('remoteurl');
		$fromWeb->addExtraClass('content ss-uploadfield htmleditorfield-from-web');

		Requirements::css(FRAMEWORK_DIR . '/client/dist/styles/AssetUploadField.css');
		$computerUploadField = Object::create('UploadField', 'AssetUploadField', '');
		$computerUploadField->setConfig('previewMaxWidth', 40);
		$computerUploadField->setConfig('previewMaxHeight', 30);
		$computerUploadField->addExtraClass('ss-assetuploadfield htmleditorfield-from-computer');
		$computerUploadField->removeExtraClass('ss-uploadfield');
		$computerUploadField->setTemplate('HTMLEditorField_UploadField');
		$computerUploadField->setFolderName(Config::inst()->get('Upload', 'uploads_folder'));

		$defaultPanel = new CompositeField(
			$computerUploadField,
			$fromCMS
		);

		$fromWebPanel = new CompositeField(
			$fromWeb
		);

		$defaultPanel->addExtraClass('htmleditorfield-default-panel');
		$fromWebPanel->addExtraClass('htmleditorfield-web-panel');

		$allFields = new CompositeField(
			$defaultPanel,
			$fromWebPanel,
			$editComposite = new CompositeField(
				new LiteralField('contentEdit', '<div class="content-edit ss-uploadfield-files files"></div>')
			)
		);

		$allFields->addExtraClass('ss-insert-media');

		$headings = new CompositeField(
			new LiteralField(
				'Heading',
				sprintf('<h3 class="htmleditorfield-mediaform-heading insert">%s</h3>',
					_t('HTMLEditorField.INSERTMEDIA', 'Insert media from')).
				sprintf('<h3 class="htmleditorfield-mediaform-heading update">%s</h3>',
					_t('HTMLEditorField.UpdateMEDIA', 'Update media'))
			)
		);

		$headings->addExtraClass('cms-content-header');
		$editComposite->addExtraClass('ss-assetuploadfield');

		$fields = new FieldList(
			$headings,
			$allFields
		);

		$form = new Form(
			$this->controller,
			"{$this->name}/MediaForm",
			$fields,
			new FieldList()
		);


		$form->unsetValidator();
		$form->disableSecurityToken();
		$form->loadDataFrom($this);
		$form->addExtraClass('htmleditorfield-form htmleditorfield-mediaform cms-dialog-content');

		// Allow other people to extend the fields being added to the imageform
		$this->extend('updateMediaForm', $form);

		return $form;
	}

	/**
	 * List of allowed schemes (no wildcard, all lower case) or empty to allow all schemes
	 *
	 * @config
	 * @var array
	 */
	private static $fileurl_scheme_whitelist = array('http', 'https');

	/**
	 * List of allowed domains (no wildcard, all lower case) or empty to allow all domains
	 *
	 * @config
	 * @var array
	 */
	private static $fileurl_domain_whitelist = array();

	/**
	 * Find local File dataobject given ID
	 *
	 * @param int $id
	 * @return array
	 */
	protected function viewfile_getLocalFileByID($id) {
		/** @var File $file */
		$file = DataObject::get_by_id('File', $id);
		if ($file && $file->canView()) {
			return array($file, $file->getURL());
		}
		return [null, null];
	}

	/**
	 * Get remote File given url
	 *
	 * @param string $fileUrl Absolute URL
	 * @return array
	 * @throws SS_HTTPResponse_Exception
	 */
	protected function viewfile_getRemoteFileByURL($fileUrl) {
		if(!Director::is_absolute_url($fileUrl)) {
			throw $this->getErrorFor(_t(
				"HTMLEditorField_Toolbar.ERROR_ABSOLUTE",
				"Only absolute urls can be embedded"
			));
		}
		$scheme = strtolower(parse_url($fileUrl, PHP_URL_SCHEME));
		$allowed_schemes = self::config()->fileurl_scheme_whitelist;
		if (!$scheme || ($allowed_schemes && !in_array($scheme, $allowed_schemes))) {
			throw $this->getErrorFor(_t(
				"HTMLEditorField_Toolbar.ERROR_SCHEME",
				"This file scheme is not included in the whitelist"
			));
		}
		$domain = strtolower(parse_url($fileUrl, PHP_URL_HOST));
		$allowed_domains = self::config()->fileurl_domain_whitelist;
		if (!$domain || ($allowed_domains && !in_array($domain, $allowed_domains))) {
			throw $this->getErrorFor(_t(
				"HTMLEditorField_Toolbar.ERROR_HOSTNAME",
				"This file hostname is not included in the whitelist"
			));
		}
		return [null, $fileUrl];
	}

	/**
	 * Prepare error for the front end
	 *
	 * @param string $message
	 * @param int $code
	 * @return SS_HTTPResponse_Exception
	 */
	protected function getErrorFor($message, $code = 400) {
		$exception = new SS_HTTPResponse_Exception($message, $code);
		$exception->getResponse()->addHeader('X-Status', $message);
		return $exception;
	}

	/**
	 * View of a single file, either on the filesystem or on the web.
	 *
	 * @throws SS_HTTPResponse_Exception
	 * @param SS_HTTPRequest $request
	 * @return string
	 */
	public function viewfile($request) {
		$file = null;
		$url = null;
		// Get file and url by request method
		if($fileUrl = $request->getVar('FileURL')) {
			// Get remote url
			list($file, $url) = $this->viewfile_getRemoteFileByURL($fileUrl);
		} elseif($id = $request->getVar('ID')) {
			// Or we could have been passed an ID directly
			list($file, $url) = $this->viewfile_getLocalFileByID($id);
		} else {
			// Or we could have been passed nothing, in which case panic
			throw $this->getErrorFor(_t(
				"HTMLEditorField_Toolbar.ERROR_ID",
				'Need either "ID" or "FileURL" parameter to identify the file'
			));
		}

		// Validate file exists
		if(!$url) {
			throw $this->getErrorFor(_t(
				"HTMLEditorField_Toolbar.ERROR_NOTFOUND",
				'Unable to find file to view'
			));
		}

		// Instantiate file wrapper and get fields based on its type
		// Check if appCategory is an image and exists on the local system, otherwise use Embed to reference a
		// remote image
		$fileCategory = $this->getFileCategory($url, $file);
		switch($fileCategory) {
			case 'image':
			case 'image/supported':
				$fileWrapper = new HTMLEditorField_Image($url, $file);
				break;
			case 'flash':
				$fileWrapper = new HTMLEditorField_Flash($url, $file);
				break;
			default:
				// Only remote files can be linked via o-embed
				// {@see HTMLEditorField_Toolbar::getAllowedExtensions())
				if($file) {
					throw $this->getErrorFor(_t(
						"HTMLEditorField_Toolbar.ERROR_OEMBED_REMOTE",
						"Embed is only compatible with remote files"
					));
				}

				// Other files should fallback to embed
				$fileWrapper = new HTMLEditorField_Embed($url, $file);
				break;
		}

		// Render fields and return
		$fields = $this->getFieldsForFile($url, $fileWrapper);
		return $fileWrapper->customise(array(
			'Fields' => $fields,
		))->renderWith($this->templateViewFile);
	}

	/**
	 * Guess file category from either a file or url
	 *
	 * @param string $url
	 * @param File $file
	 * @return string
	 */
	protected function getFileCategory($url, $file) {
		if($file) {
			return $file->appCategory();
		}
		if($url) {
			return File::get_app_category(File::get_file_extension($url));
		}
		return null;
	}

	/**
	 * Find all anchors available on the given page.
	 *
	 * @return array
	 * @throws SS_HTTPResponse_Exception
	 */
	public function getanchors() {
		$id = (int)$this->getRequest()->getVar('PageID');
		$anchors = array();

		if (($page = Page::get()->byID($id)) && !empty($page)) {
			if (!$page->canView()) {
				throw new SS_HTTPResponse_Exception(
					_t(
						'HTMLEditorField.ANCHORSCANNOTACCESSPAGE',
						'You are not permitted to access the content of the target page.'
					),
					403
				);
			}

			// Similar to the regex found in HTMLEditorField.js / getAnchors method.
			if (preg_match_all(
				"/\\s+(name|id)\\s*=\\s*([\"'])([^\\2\\s>]*?)\\2|\\s+(name|id)\\s*=\\s*([^\"']+)[\\s +>]/im",
				$page->Content,
				$matches
			)) {
				$anchors = array_values(array_unique(array_filter(
					array_merge($matches[3], $matches[5]))
				));
			}

		} else {
			throw new SS_HTTPResponse_Exception(
				_t('HTMLEditorField.ANCHORSPAGENOTFOUND', 'Target page not found.'),
				404
			);
		}

		return json_encode($anchors);
	}

	/**
	 * Similar to {@link File->getCMSFields()}, but only returns fields
	 * for manipulating the instance of the file as inserted into the HTML content,
	 * not the "master record" in the database - hence there's no form or saving logic.
	 *
	 * @param string $url Abolute URL to asset
	 * @param HTMLEditorField_File $file Asset wrapper
	 * @return FieldList
	 */
	protected function getFieldsForFile($url, HTMLEditorField_File $file) {
		$fields = $this->extend('getFieldsForFile', $url, $file);
		if(!$fields) {
			$fields = $file->getFields();
			$file->extend('updateFields', $fields);
		}
		$this->extend('updateFieldsForFile', $fields, $url, $file);
		return $fields;
	}


	/**
	 * Gets files filtered by a given parent with the allowed extensions
	 *
	 * @param int $parentID
	 * @return DataList
	 */
	protected function getFiles($parentID = null) {
		$exts = $this->getAllowedExtensions();
		$dotExts = array_map(function($ext) {
			return ".{$ext}";
		}, $exts);
		$files = File::get()->filter('Name:EndsWith', $dotExts);

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
		$exts = array('jpg', 'gif', 'png', 'swf', 'jpeg');
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
abstract class HTMLEditorField_File extends ViewableData {

	/**
	 * Default insertion width for Images and Media
	 *
	 * @config
	 * @var int
	 */
	private static $insert_width = 600;

	/**
	 * Default insert height for images and media
	 *
	 * @config
	 * @var int
	 */
	private static $insert_height = 360;

	/**
	 * Max width for insert-media preview.
	 *
	 * Matches CSS rule for .cms-file-info-preview
	 *
	 * @var int
	 */
	private static $media_preview_width = 176;

	/**
	 * Max height for insert-media preview.
	 *
	 * Matches CSS rule for .cms-file-info-preview
	 *
	 * @var int
	 */
	private static $media_preview_height = 128;

	private static $casting = array(
		'URL' => 'Varchar',
		'Name' => 'Varchar'
	);

	/**
	 * Absolute URL to asset
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * File dataobject (if available)
	 *
	 * @var File
	 */
	protected $file;

	/**
	 * @param string $url
	 * @param File $file
	 */
	public function __construct($url, File $file = null) {
		$this->url = $url;
		$this->file = $file;
		$this->failover = $file;
		parent::__construct();
	}

	/**
	 * @return FieldList
	 */
	public function getFields() {
		$fields = new FieldList(
			CompositeField::create(
				CompositeField::create(LiteralField::create("ImageFull", $this->getPreview()))
					->setName("FilePreviewImage")
					->addExtraClass('cms-file-info-preview'),
				CompositeField::create($this->getDetailFields())
					->setName("FilePreviewData")
					->addExtraClass('cms-file-info-data')
			)
				->setName("FilePreview")
				->addExtraClass('cms-file-info'),
			TextField::create('CaptionText', _t('HTMLEditorField.CAPTIONTEXT', 'Caption text')),
			DropdownField::create(
				'CSSClass',
				_t('HTMLEditorField.CSSCLASS', 'Alignment / style'),
				array(
					'leftAlone' => _t('HTMLEditorField.CSSCLASSLEFTALONE', 'On the left, on its own.'),
					'center' => _t('HTMLEditorField.CSSCLASSCENTER', 'Centered, on its own.'),
					'left' => _t('HTMLEditorField.CSSCLASSLEFT', 'On the left, with text wrapping around.'),
					'right' => _t('HTMLEditorField.CSSCLASSRIGHT', 'On the right, with text wrapping around.')
				)
			),
			FieldGroup::create(_t('HTMLEditorField.IMAGEDIMENSIONS', 'Dimensions'),
				TextField::create(
					'Width',
					_t('HTMLEditorField.IMAGEWIDTHPX', 'Width'),
					$this->getInsertWidth()
				)->setMaxLength(5),
				TextField::create(
					'Height',
					" x " . _t('HTMLEditorField.IMAGEHEIGHTPX', 'Height'),
					$this->getInsertHeight()
				)->setMaxLength(5)
			)->addExtraClass('dimensions last'),
			HiddenField::create('URL', false, $this->getURL()),
			HiddenField::create('FileID', false, $this->getFileID())
		);
		return $fields;
	}

	/**
	 * Get list of fields for previewing this records details
	 *
	 * @return FieldList
	 */
	protected function getDetailFields() {
		$fields = new FieldList(
			ReadonlyField::create("FileType", _t('AssetTableField.TYPE','File type'), $this->getFileType()),
			ReadonlyField::create(
				'ClickableURL', _t('AssetTableField.URL','URL'), $this->getExternalLink()
			)->setDontEscape(true)
		);
		// Get file size
		if($this->getSize()) {
			$fields->insertAfter(
				'FileType',
				ReadonlyField::create("Size", _t('AssetTableField.SIZE','File size'), $this->getSize())
			);
		}
		// Get modified details of local record
		if($this->getFile()) {
			$fields->push(new DateField_Disabled(
				"Created",
				_t('AssetTableField.CREATED', 'First uploaded'),
				$this->getFile()->Created
			));
			$fields->push(new DateField_Disabled(
				"LastEdited",
				_t('AssetTableField.LASTEDIT','Last changed'),
				$this->getFile()->LastEdited
			));
		}
		return $fields;

	}

	/**
	 * Get file DataObject
	 *
	 * Might not be set (for remote files)
	 *
	 * @return File
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * Get file ID
	 *
	 * @return int
	 */
	public function getFileID() {
		if($file = $this->getFile()) {
			return $file->ID;
		}
	}

	/**
	 * Get absolute URL
	 *
	 * @return string
	 */
	public function getURL() {
		return $this->url;
	}

	/**
	 * Get basename
	 *
	 * @return string
	 */
	public function getName() {
		return $this->file
			? $this->file->Name
			: preg_replace('/\?.*/', '', basename($this->url));
	}

	/**
	 * Get descriptive file type
	 *
	 * @return string
	 */
	public function getFileType() {
		return File::get_file_type($this->getName());
	}

	/**
	 * Get file size (if known) as string
	 *
	 * @return string|false String value, or false if doesn't exist
	 */
	public function getSize() {
		if($this->file) {
			return $this->file->getSize();
		}
		return false;
	}

	/**
	 * HTML content for preview
	 *
	 * @return string HTML
	 */
	public function getPreview() {
		$preview = $this->extend('getPreview');
		if($preview) {
			return $preview;
		}

		// Generate tag from preview
		$thumbnailURL = Convert::raw2att(
			Controller::join_links($this->getPreviewURL(), "?r=" . rand(1,100000))
		);
		$fileName = Convert::raw2att($this->Name);
		return sprintf(
			"<img id='thumbnailImage' class='thumbnail-preview'  src='%s' alt='%s' />\n",
			$thumbnailURL,
			$fileName
		);
	}

	/**
	 * HTML Content for external link
	 *
	 * @return string
	 */
	public function getExternalLink() {
		$title = $this->file
			? $this->file->getTitle()
			: $this->getName();
		return sprintf(
			'<a href="%1$s" title="%2$s" target="_blank" rel="external" class="file-url">%1$s</a>',
			Convert::raw2att($this->url),
			Convert::raw2att($title)
		);
	}

	/**
	 * Generate thumbnail url
	 *
	 * @return string
	 */
	public function getPreviewURL() {
		// Get preview from file
		if($this->file) {
			return $this->getFilePreviewURL();
		}

		// Generate default icon html
		return File::get_icon_for_extension($this->getExtension());
	}

	/**
	 * Generate thumbnail URL from file dataobject (if available)
	 *
	 * @return string
	 */
	protected function getFilePreviewURL() {
		// Get preview from file
		if($this->file) {
			$width = $this->config()->media_preview_width;
			$height = $this->config()->media_preview_height;
			return $this->file->ThumbnailURL($width, $height);
		}
	}

	/**
	 * Get file extension
	 *
	 * @return string
	 */
	public function getExtension() {
		$extension = File::get_file_extension($this->getName());
		return strtolower($extension);
	}

	/**
	 * Category name
	 *
	 * @return string
	 */
	public function appCategory() {
		if($this->file) {
			return $this->file->appCategory();
		} else {
			return File::get_app_category($this->getExtension());
		}
	}

	/**
	 * Get height of this item
	 */
	public function getHeight() {
		if($this->file) {
			$height = $this->file->getHeight();
			if($height) {
				return $height;
			}
		}
		return $this->config()->insert_height;
	}

	/**
	 * Get width of this item
	 *
	 * @return type
	 */
	public function getWidth() {
		if($this->file) {
			$width = $this->file->getWidth();
			if($width) {
				return $width;
			}
		}
		return $this->config()->insert_width;
	}

	/**
	 * Provide an initial width for inserted media, restricted based on $embed_width
	 *
	 * @return int
	 */
	public function getInsertWidth() {
		$width = $this->getWidth();
		$maxWidth = $this->config()->insert_width;
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
		$maxWidth = $this->config()->insert_width;
		return ($width <= $maxWidth) ? $height : round($height*($maxWidth/$width));
	}

}

/**
 * Encapsulation of an embed tag, linking to an external media source.
 *
 * @see Embed
 * @package forms
 * @subpackage fields-formattedinput
 */
class HTMLEditorField_Embed extends HTMLEditorField_File {

	private static $casting = array(
		'Type' => 'Varchar',
		'Info' => 'Varchar'
	);

	/**
	 * Embed result
	 *
	 * @var Embed
	 */
	protected $embed;

	public function __construct($url, File $file = null) {
		parent::__construct($url, $file);
		$this->embed = Embed::create($url);
		if(!$this->embed) {
			$controller = Controller::curr();
			$response = $controller->getResponse();
			$response->addHeader('X-Status',
				rawurlencode(_t(
					'HTMLEditorField.URLNOTANOEMBEDRESOURCE',
					"The URL '{url}' could not be turned into a media resource.",
					"The given URL is not a valid Oembed resource; the embed element couldn't be created.",
					array('url' => $url)
				)));
			$response->setStatusCode(404);

			throw new SS_HTTPResponse_Exception($response);
		}
	}

	/**
	 * Get file-edit fields for this filed
	 *
	 * @return FieldList
	 */
	public function getFields() {
		$fields = parent::getFields();
		if($this->Type === 'photo') {
			$fields->insertBefore('CaptionText', new TextField(
				'AltText',
				_t('HTMLEditorField.IMAGEALTTEXT', 'Alternative text (alt) - shown if image can\'t be displayed'),
				$this->Title,
				80
			));
			$fields->insertBefore('CaptionText', new TextField(
				'Title',
				_t('HTMLEditorField.IMAGETITLE', 'Title text (tooltip) - for additional information about the image')
			));
		}
		return $fields;
	}

	/**
	 * Get width of this Embed
	 *
	 * @return int
	 */
	public function getWidth() {
		return $this->embed->width ?: 100;
	}

	/**
	 * Get height of this Embed
	 *
	 * @return int
	 */
	public function getHeight() {
		return $this->embed->height ?: 100;
	}

	public function getPreviewURL() {
		// Use thumbnail url
		if($this->embed->image) {
			return $this->embed->image;
		}

		// Use direct image type
		if($this->getType() == 'photo' && !empty($this->embed->url)) {
			return $this->embed->url;
		}

		// Default media
		return FRAMEWORK_DIR . '/images/default_media.png';
	}

	public function getName() {
		if($this->embed->title) {
			return $this->embed->title;
		} else {
			return parent::getName();
		}
	}

	/**
	 * Get Embed type
	 *
	 * @return string
	 */
	public function getType() {
		return $this->embed->type;
	}

	/**
	 * Get filetype
	 *
	 * @return string
	 */
	public function getFileType() {
		return $this->getType()
			?: parent::getFileType();
	}

	/**
	 * @return AdapterInterface
	 */
	public function getEmbed() {
		return $this->embed;
	}

	public function appCategory() {
		return 'embed';
	}

	/**
	 * Info for this Embed
	 *
	 * @return string
	 */
	public function getInfo() {
		return $this->embed->info;
	}
}

/**
 * Encapsulation of an image tag, linking to an image either internal or external to the site.
 *
 * @package forms
 * @subpackage fields-formattedinput
 */
class HTMLEditorField_Image extends HTMLEditorField_File {

	/**
	 * @var int
	 */
	protected $width;

	/**
	 * @var int
	 */
	protected $height;

	/**
	 * File size details
	 *
	 * @var string
	 */
	protected $size;

	public function __construct($url, File $file = null) {
		parent::__construct($url, $file);

		if($file) {
			return;
		}

		// Get size of remote file
		$size = @filesize($url);
		if($size) {
			$this->size = $size;
		}

		// Get dimensions of remote file
		$info = @getimagesize($url);
		if($info) {
			$this->width = $info[0];
			$this->height = $info[1];
		}
	}

	public function getFields() {
		$fields = parent::getFields();

		// Alt text
		$fields->insertBefore(
			'CaptionText',
			TextField::create(
				'AltText',
				_t('HTMLEditorField.IMAGEALT', 'Alternative text (alt)'),
				$this->Title,
				80
			)->setDescription(
				_t('HTMLEditorField.IMAGEALTTEXTDESC', 'Shown to screen readers or if image can\'t be displayed')
			)
		);

		// Tooltip
		$fields->insertAfter(
			'AltText',
			TextField::create(
				'Title',
				_t('HTMLEditorField.IMAGETITLETEXT', 'Title text (tooltip)')
			)->setDescription(
				_t('HTMLEditorField.IMAGETITLETEXTDESC', 'For additional information about the image')
			)
		);

		return $fields;
	}

	protected function getDetailFields() {
		$fields = parent::getDetailFields();
		$width = $this->getOriginalWidth();
		$height = $this->getOriginalHeight();

		// Show dimensions of original
		if($width && $height) {
			$fields->insertAfter(
				'ClickableURL',
				ReadonlyField::create(
					"OriginalWidth",
					_t('AssetTableField.WIDTH','Width'),
					$width
				)
			);
			$fields->insertAfter(
				'OriginalWidth',
				ReadonlyField::create(
					"OriginalHeight",
					_t('AssetTableField.HEIGHT','Height'),
					$height
				)
			);
		}
		return $fields;
	}

	/**
	 * Get width of original, if known
	 *
	 * @return int
	 */
	public function getOriginalWidth() {
		if($this->width) {
			return $this->width;
		}
		if($this->file) {
			$width = $this->file->getWidth();
			if($width) {
				return $width;
			}
		}
	}

	/**
	 * Get height of original, if known
	 *
	 * @return int
	 */
	public function getOriginalHeight() {
		if($this->height) {
			return $this->height;
		}

		if($this->file) {
			$height = $this->file->getHeight();
			if($height) {
				return $height;
			}
		}
	}

	public function getWidth() {
		if($this->width) {
			return $this->width;
		}
		return parent::getWidth();
	}

	public function getHeight() {
		if($this->height) {
			return $this->height;
		}
		return parent::getHeight();
	}

	public function getSize() {
		if($this->size) {
			return File::format_size($this->size);
		}
		parent::getSize();
	}

	/**
	 * Provide an initial width for inserted image, restricted based on $embed_width
	 *
	 * @return int
	 */
	public function getInsertWidth() {
		$width = $this->getWidth();
		$maxWidth = $this->config()->insert_width;
		return $width <= $maxWidth
			? $width
			: $maxWidth;
	}

	/**
	 * Provide an initial height for inserted image, scaled proportionally to the initial width
	 *
	 * @return int
	 */
	public function getInsertHeight() {
		$width = $this->getWidth();
		$height = $this->getHeight();
		$maxWidth = $this->config()->insert_width;
		return ($width <= $maxWidth) ? $height : round($height*($maxWidth/$width));
	}

	public function getPreviewURL() {
		// Get preview from file
		if($this->file) {
			return $this->getFilePreviewURL();
		}

		// Embed image directly
		return $this->url;
	}
}

/**
 * Generate flash file embed
 */
class HTMLEditorField_Flash extends HTMLEditorField_File {

	public function getFields() {
		$fields = parent::getFields();
		$fields->removeByName('CaptionText', true);
		return $fields;
	}
}
