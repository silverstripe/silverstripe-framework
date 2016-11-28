<?php

namespace SilverStripe\Forms;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Control\Controller;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\ORM\DataList;
use SilverStripe\View\Requirements;

/**
 * File selection popup for attaching existing files.
 */
class UploadField_SelectHandler extends RequestHandler
{

    /**
     * @var UploadField
     */
    protected $parent;

    /**
     * @var string
     */
    protected $folderName;

    /**
     * Set pagination quantity for file list field
     *
     * @config
     * @var int
     */
    private static $page_size = 11;

    private static $url_handlers = array(
        '$Action!' => '$Action',
        '' => 'index',
    );

    private static $allowed_actions = array(
        'Form'
    );

    public function __construct($parent, $folderName = null)
    {
        $this->parent = $parent;
        $this->folderName = $folderName;

        parent::__construct();
    }

    public function index()
    {
        // Requires a separate JS file, because we can't reach into the iframe with entwine.
        Requirements::javascript(ltrim(FRAMEWORK_ADMIN_DIR . '/client/dist/js/UploadField_select.js', '/'));
        return $this->renderWith('SilverStripe\\Admin\\CMSDialog');
    }

    /**
     * @param string $action
     * @return string
     */
    public function Link($action = null)
    {
        return Controller::join_links($this->parent->Link(), '/select/', $action);
    }

    /**
     * Build the file selection form.
     *
     * @skipUpgrade
     * @return Form
     */
    public function Form()
    {
        // Find out the requested folder ID.
        $folderID = $this->parent->getRequest()->requestVar('ParentID');
        if ($folderID === null && $this->parent->getDisplayFolderName()) {
            $folder = Folder::find_or_make($this->parent->getDisplayFolderName());
            $folderID = $folder ? $folder->ID : 0;
        }

        // Construct the form
        $action = new FormAction('doAttach', _t('UploadField.AttachFile', 'Attach file(s)'));
        $action->addExtraClass('ss-ui-action-constructive icon-accept');
        $form = new Form(
            $this,
            'Form',
            new FieldList($this->getListField($folderID)),
            new FieldList($action)
        );

        // Add a class so we can reach the form from the frontend.
        $form->addExtraClass('uploadfield-form');

        return $form;
    }

    /**
     * @param int $folderID The ID of the folder to display.
     * @return FormField
     */
    protected function getListField($folderID)
    {
        // Generate the folder selection field.
        /** @skipUpgrade */
        $folderField = new TreeDropdownField(
            'ParentID',
            _t('HTMLEditorField.FOLDER', 'Folder'),
            'SilverStripe\\Assets\\Folder'
        );
        $folderField->setValue($folderID);

        // Generate the file list field.
        $config = GridFieldConfig::create();
        $config->addComponent(new GridFieldSortableHeader());
        $config->addComponent(new GridFieldFilterHeader());
        $config->addComponent($colsComponent = new GridFieldDataColumns());
        $colsComponent->setDisplayFields(array(
            'StripThumbnail' => '',
            'Title' => File::singleton()->fieldLabel('Title'),
            'Created' => File::singleton()->fieldLabel('Created'),
            'Size' => File::singleton()->fieldLabel('Size')
        ));
        $colsComponent->setFieldCasting(array(
            'Created' => 'DBDatetime->Nice'
        ));

        // Set configurable pagination for file list field
        $pageSize = Config::inst()->get(get_class($this), 'page_size');
        $config->addComponent(new GridFieldPaginator($pageSize));

        // If relation is to be autoset, we need to make sure we only list compatible objects.
        $baseClass = $this->parent->getRelationAutosetClass();

        // Create the data source for the list of files within the current directory.
        $files = DataList::create($baseClass)->exclude('ClassName', 'SilverStripe\\Assets\\Folder');
        if ($folderID) {
            $files = $files->filter('ParentID', $folderID);
        }

        $fileField = new GridField('Files', false, $files, $config);
        $fileField->setAttribute('data-selectable', true);
        if ($this->parent->getAllowedMaxFileNumber() !== 1) {
            $fileField->setAttribute('data-multiselect', true);
        }

        $selectComposite = new CompositeField(
            $folderField,
            $fileField
        );

        return $selectComposite;
    }

    public function doAttach($data, $form)
    {
        // Popup-window attach does not require server side action, as it is implemented via JS
    }
}
