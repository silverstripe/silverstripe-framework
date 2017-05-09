<?php

namespace SilverStripe\Forms\HTMLEditor;

use SilverStripe\Admin\Forms\EditorExternalLinkFormFactory;
use SilverStripe\Assets\File;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\SSViewer;

/**
 * Toolbar shared by all instances of {@link HTMLEditorField}, to avoid too much markup duplication.
 *  Needs to be inserted manually into the template in order to function - see {@link LeftAndMain->EditorToolbar()}.
 */
class HTMLEditorField_Toolbar extends RequestHandler
{

    private static $allowed_actions = array(
        'LinkForm',
        'EditorExternalLink',
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
            '<div id="cms-editor-dialogs" data-url-linkform="%s"></div>',
            Controller::join_links($this->controller->Link(), $this->name, 'LinkForm', 'forTemplate')
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
     * @skipUpgrade
     * @return Form
     */
    public function LinkForm()
    {
        $siteTree = TreeDropdownField::create(
            'internal',
            _t(__CLASS__.'.PAGE', "Page"),
            SiteTree::class,
            'ID',
            'MenuTitle',
            true
        );
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
                        sprintf(
                            '<h3 class="htmleditorfield-linkform-heading insert">%s</h3>',
                            _t(__CLASS__.'.LINK', 'Insert Link')
                        )
                    )
                ),
                $contentComposite = new CompositeField(
                    OptionsetField::create(
                        'LinkType',
                        DBField::create_field(
                            'HTMLFragment',
                            sprintf($numericLabelTmpl, '1', _t(__CLASS__.'.LINKTO', 'Link type'))
                        ),
                        array(
                            'internal' => _t(__CLASS__.'.LINKINTERNAL', 'Link to a page on this site'),
                            'external' => _t(__CLASS__.'.LINKEXTERNAL', 'Link to another website'),
                            'anchor' => _t(__CLASS__.'.LINKANCHOR', 'Link to an anchor on this page'),
                            'email' => _t(__CLASS__.'.LINKEMAIL', 'Link to an email address'),
                            'file' => _t(__CLASS__.'.LINKFILE', 'Link to download a file'),
                        ),
                        'internal'
                    ),
                    LiteralField::create(
                        'Step2',
                        '<div class="step2">'
                        . sprintf($numericLabelTmpl, '2', _t(__CLASS__.'.LINKDETAILS', 'Link details')) . '</div>'
                    ),
                    $siteTree,
                    TextField::create('external', _t(__CLASS__.'.URL', 'URL'), 'http://'),
                    EmailField::create('email', _t(__CLASS__.'.EMAIL', 'Email address')),
                    $fileField = TreeDropdownField::create(
                        'file',
                        _t(__CLASS__.'.FILE', 'File'),
                        File::class,
                        'ID',
                        'Name'
                    ),
                    TextField::create('Anchor', _t(__CLASS__.'.ANCHORVALUE', 'Anchor')),
                    TextField::create('Subject', _t(__CLASS__.'.SUBJECT', 'Email subject')),
                    TextField::create('Description', _t(__CLASS__.'.LINKDESCR', 'Link description')),
                    CheckboxField::create(
                        'TargetBlank',
                        _t(__CLASS__.'.LINKOPENNEWWIN', 'Open link in a new window?')
                    ),
                    HiddenField::create('Locale', null, $this->controller->Locale)
                )
            ),
            new FieldList()
        );

        $headerWrap->setName('HeaderWrap');
        $headerWrap->addExtraClass('CompositeField composite cms-content-header form-group--no-label ');
        $contentComposite->setName('ContentBody');
        $contentComposite->addExtraClass('ss-insert-link content');

        $form->unsetValidator();
        $form->loadDataFrom($this);
        $form->addExtraClass('htmleditorfield-form htmleditorfield-linkform cms-linkform-content');

        $this->extend('updateLinkForm', $form);

        return $form;
    }
    
    /**
     * Builds and returns the external link form
     *
     * @return null|Form
     */
    public function EditorExternalLink($id = null)
    {
        /** @var EditorExternalLinkFormFactory $factory */
        $factory = Injector::inst()->get(EditorExternalLinkFormFactory::class);
        if ($factory) {
            return $factory->getForm($this->controller, "{$this->name}/EditorExternalLink");
        }
        return null;
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
        $file = DataObject::get_by_id(File::class, $id);
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
                "SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField_Toolbar.ERROR_ABSOLUTE",
                "Only absolute urls can be embedded"
            ));
        }
        $scheme = strtolower(parse_url($fileUrl, PHP_URL_SCHEME));
        $allowed_schemes = self::config()->get('fileurl_scheme_whitelist');
        if (!$scheme || ($allowed_schemes && !in_array($scheme, $allowed_schemes))) {
            throw $this->getErrorFor(_t(
                "SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField_Toolbar.ERROR_SCHEME",
                "This file scheme is not included in the whitelist"
            ));
        }
        $domain = strtolower(parse_url($fileUrl, PHP_URL_HOST));
        $allowed_domains = self::config()->get('fileurl_domain_whitelist');
        if (!$domain || ($allowed_domains && !in_array($domain, $allowed_domains))) {
            throw $this->getErrorFor(_t(
                "SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField_Toolbar.ERROR_HOSTNAME",
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
                "SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField_Toolbar.ERROR_ID",
                'Need either "ID" or "FileURL" parameter to identify the file'
            ));
        }

        // Validate file exists
        if (!$url) {
            throw $this->getErrorFor(_t(
                "SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField_Toolbar.ERROR_NOTFOUND",
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
                        "SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField_Toolbar.ERROR_OEMBED_REMOTE",
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

        if (($page = SiteTree::get()->byID($id)) && !empty($page)) {
            if (!$page->canView()) {
                throw new HTTPResponse_Exception(
                    _t(
                        'SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField.ANCHORSCANNOTACCESSPAGE',
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
                    array_merge($matches[3], $matches[5])
                )));
            }
        } else {
            throw new HTTPResponse_Exception(
                _t('SilverStripe\\Forms\\HTMLEditor\\HTMLEditorField.ANCHORSPAGENOTFOUND', 'Target page not found.'),
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
