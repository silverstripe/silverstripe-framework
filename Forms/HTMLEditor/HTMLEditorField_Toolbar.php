<?php

namespace SilverStripe\Forms\HTMLEditor;

use Page;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Upload;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\Forms\UploadField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;

/**
 * Toolbar shared by all instances of {@link HTMLEditorField}, to avoid too much markup duplication.
 *  Needs to be inserted manually into the template in order to function - see {@link LeftAndMain->EditorToolbar()}.
 */
class HTMLEditorField_Toolbar extends RequestHandler
{

	private static $allowed_actions = array(
		'LinkForm',
		'MediaForm',
		'viewfile',
		'getanchors'
	);

	/**
	 * @return string
	 */
	public function getTemplateViewFile()
	{
		return SSViewer::get_templates_by_class(get_class($this), '_viewfile', __CLASS__);
	}

	/**
	 * @var Controller
	 */
	protected $controller;

	/**
	 * @var string
	 */
	protected $name;

	public function __construct($controller, $name)
	{
		parent::__construct();

		$this->controller = $controller;
		$this->name = $name;
	}

	public function forTemplate()
	{
		return sprintf(
			'<div id="cms-editor-dialogs" data-url-linkform="%s" data-url-mediaform="%s"></div>',
			Controller::join_links($this->controller->Link(), $this->name, 'LinkForm', 'forTemplate'),
			Controller::join_links($this->controller->Link(), $this->name, 'MediaForm', 'forTemplate')
		);
	}

	/**
	 * Searches the SiteTree for display in the dropdown
	 *
	 * @param string $sourceObject
	 * @param string $labelField
	 * @param string $search
	 * @return DataList
	 */
	public function siteTreeSearchCallback($sourceObject, $labelField, $search)
	{
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
	public function LinkForm()
	{
		$siteTree = TreeDropdownField::create('internal', _t('HTMLEditorField.PAGE', "Page"),
			'SilverStripe\\CMS\\Model\\SiteTree', 'ID', 'MenuTitle', true);
		// mimic the SiteTree::getMenuTitle(), which is bypassed when the search is performed
		$siteTree->setSearchFunction(array($this, 'siteTreeSearchCallback'));

		$numericLabelTmpl = '<span class="step-label"><span class="flyout">Step %d.</span>'
			. '<span class="title">%s</span></span>';
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
						DBField::create_field(
							'HTMLFragment',
							sprintf($numericLabelTmpl, '1', _t('HTMLEditorField.LINKTO', 'Link type'))
						),
						array(
							'internal' => _t('HTMLEditorField.LINKINTERNAL', 'Link to a page on this site'),
							'external' => _t('HTMLEditorField.LINKEXTERNAL', 'Link to another website'),
							'anchor' => _t('HTMLEditorField.LINKANCHOR', 'Link to an anchor on this page'),
							'email' => _t('HTMLEditorField.LINKEMAIL', 'Link to an email address'),
							'file' => _t('HTMLEditorField.LINKFILE', 'Link to download a file'),
						),
						'internal'
					),
					LiteralField::create('Step2',
						'<div class="step2">'
						. sprintf($numericLabelTmpl, '2', _t('HTMLEditorField.LINKDETAILS', 'Link details')) . '</div>'
					),
					$siteTree,
					TextField::create('external', _t('HTMLEditorField.URL', 'URL'), 'http://'),
					EmailField::create('email', _t('HTMLEditorField.EMAIL', 'Email address')),
					$fileField = UploadField::create('file', _t('HTMLEditorField.FILE', 'SilverStripe\\Assets\\File')),
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

		$headerWrap->setName('HeaderWrap');
		$headerWrap->addExtraClass('CompositeField composite cms-content-header form-group--no-label ');
		$contentComposite->setName('ContentBody');
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
	protected function getAttachParentID()
	{
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
	public function MediaForm()
	{
		// TODO Handle through GridState within field - currently this state set too late to be useful here (during
		// request handling)
		$parentID = $this->getAttachParentID();

		$fileFieldConfig = GridFieldConfig::create()->addComponents(
			new GridFieldSortableHeader(),
			new GridFieldFilterHeader(),
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
		/** @var GridFieldDataColumns $columns */
		$columns = $fileField->getConfig()->getComponentByType('SilverStripe\\Forms\\GridField\\GridFieldDataColumns');
		$columns->setDisplayFields(array(
			'StripThumbnail' => false,
			'Title' => _t('File.Title'),
			'Created' => File::singleton()->fieldLabel('Created'),
		));
		$columns->setFieldCasting(array(
			'Created' => 'DBDatetime->Nice'
		));

		$fromCMS = new CompositeField(
			$select = TreeDropdownField::create('ParentID', "", 'SilverStripe\\Assets\\Folder')
				->addExtraClass('noborder')
				->setValue($parentID),
			$fileField
		);

		$fromCMS->addExtraClass('content ss-uploadfield htmleditorfield-from-cms');
		$select->addExtraClass('content-select');


		$URLDescription = _t('HTMLEditorField.URLDESCRIPTION',
			'Insert videos and images from the web into your page simply by entering the URL of the file. Make sure you have the rights or permissions before sharing media directly from the web.<br /><br />Please note that files are not added to the file store of the CMS but embeds the file from its original location, if for some reason the file is no longer available in its original location it will no longer be viewable on this page.');
		$fromWeb = new CompositeField(
			$description = new LiteralField('URLDescription',
				'<div class="url-description">' . $URLDescription . '</div>'),
			$remoteURL = new TextField('RemoteURL', 'http://'),
			new LiteralField('addURLImage',
				'<button type="button" class="action ui-action-constructive ui-button field font-icon-plus add-url">' .
				_t('HTMLEditorField.BUTTONADDURL', 'Add url') . '</button>')
		);

		$remoteURL->addExtraClass('remoteurl');
		$fromWeb->addExtraClass('content ss-uploadfield htmleditorfield-from-web');

		Requirements::css(FRAMEWORK_DIR . '/client/dist/styles/AssetUploadField.css');
		$computerUploadField = UploadField::create('AssetUploadField', '');
		$computerUploadField->setConfig('previewMaxWidth', 40);
		$computerUploadField->setConfig('previewMaxHeight', 30);
		$computerUploadField->addExtraClass('toolbar toolbar--content ss-assetuploadfield htmleditorfield-from-computer');
		$computerUploadField->removeExtraClass('ss-uploadfield');
		$computerUploadField->setTemplate('SilverStripe\\Forms\\HTMLEditorField_UploadField');
		$computerUploadField->setFolderName(Upload::config()->get('uploads_folder'));

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
					_t('HTMLEditorField.INSERTMEDIA', 'Insert media from')) .
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
	protected function viewfile_getLocalFileByID($id)
	{
		/** @var File $file */
		$file = DataObject::get_by_id('SilverStripe\\Assets\\File', $id);
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
	 * @throws HTTPResponse_Exception
	 */
	protected function viewfile_getRemoteFileByURL($fileUrl)
	{
		if (!Director::is_absolute_url($fileUrl)) {
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
	 * @return HTTPResponse_Exception
	 */
	protected function getErrorFor($message, $code = 400)
	{
		$exception = new HTTPResponse_Exception($message, $code);
		$exception->getResponse()->addHeader('X-Status', $message);
		return $exception;
	}

	/**
	 * View of a single file, either on the filesystem or on the web.
	 *
	 * @throws HTTPResponse_Exception
	 * @param HTTPRequest $request
	 * @return string
	 */
	public function viewfile($request)
	{
		$file = null;
		$url = null;
		// Get file and url by request method
		if ($fileUrl = $request->getVar('FileURL')) {
			// Get remote url
			list($file, $url) = $this->viewfile_getRemoteFileByURL($fileUrl);
		} elseif ($id = $request->getVar('ID')) {
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
		if (!$url) {
			throw $this->getErrorFor(_t(
				"HTMLEditorField_Toolbar.ERROR_NOTFOUND",
				'Unable to find file to view'
			));
		}

		// Instantiate file wrapper and get fields based on its type
		// Check if appCategory is an image and exists on the local system, otherwise use Embed to reference a
		// remote image
		$fileCategory = $this->getFileCategory($url, $file);
		switch ($fileCategory) {
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
				if ($file) {
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
		))->renderWith($this->getTemplateViewFile());
	}

	/**
	 * Guess file category from either a file or url
	 *
	 * @param string $url
	 * @param File $file
	 * @return string
	 */
	protected function getFileCategory($url, $file)
	{
		if ($file) {
			return $file->appCategory();
		}
		if ($url) {
			return File::get_app_category(File::get_file_extension($url));
		}
		return null;
	}

	/**
	 * Find all anchors available on the given page.
	 *
	 * @return array
	 * @throws HTTPResponse_Exception
	 */
	public function getanchors()
	{
		$id = (int)$this->getRequest()->getVar('PageID');
		$anchors = array();

		if (($page = Page::get()->byID($id)) && !empty($page)) {
			if (!$page->canView()) {
				throw new HTTPResponse_Exception(
					_t(
						'HTMLEditorField.ANCHORSCANNOTACCESSPAGE',
						'You are not permitted to access the content of the target page.'
					),
					403
				);
			}

			// Parse the shortcodes so [img id=x] doesn't end up as anchor x
			$htmlValue = $page->obj('Content')->forTemplate();

			// Similar to the regex found in HTMLEditorField.js / getAnchors method.
			if (preg_match_all(
				"/\\s+(name|id)\\s*=\\s*([\"'])([^\\2\\s>]*?)\\2|\\s+(name|id)\\s*=\\s*([^\"']+)[\\s +>]/im",
				$htmlValue,
				$matches
			)) {
				$anchors = array_values(array_unique(array_filter(
						array_merge($matches[3], $matches[5]))
				));
			}

		} else {
			throw new HTTPResponse_Exception(
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
	protected function getFieldsForFile($url, HTMLEditorField_File $file)
	{
		$fields = $this->extend('getFieldsForFile', $url, $file);
		if (!$fields) {
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
	protected function getFiles($parentID = null)
	{
		$exts = $this->getAllowedExtensions();
		$dotExts = array_map(function ($ext) {
			return ".{$ext}";
		}, $exts);
		$files = File::get()->filter('Name:EndsWith', $dotExts);

		// Limit by folder (if required)
		if ($parentID) {
			$files = $files->filter('ParentID', $parentID);
		}

		return $files;
	}

	/**
	 * @return array All extensions which can be handled by the different views.
	 */
	protected function getAllowedExtensions()
	{
		$exts = array('jpg', 'gif', 'png', 'swf', 'jpeg');
		$this->extend('updateAllowedExtensions', $exts);
		return $exts;
	}

}
