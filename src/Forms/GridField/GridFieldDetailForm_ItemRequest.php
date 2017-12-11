<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;

class GridFieldDetailForm_ItemRequest extends RequestHandler
{

    private static $allowed_actions = array(
        'edit',
        'view',
        'ItemEditForm'
    );

    /**
     *
     * @var GridField
     */
    protected $gridField;

    /**
     *
     * @var GridFieldDetailForm
     */
    protected $component;

    /**
     * @var DataObject
     */
    protected $record;

    /**
     * This represents the current parent RequestHandler (which does not necessarily need to be a Controller).
     * It allows us to traverse the RequestHandler chain upwards to reach the Controller stack.
     *
     * @var RequestHandler
     */
    protected $popupController;

    /**
     *
     * @var string
     */
    protected $popupFormName;

    /**
     * @var String
     */
    protected $template = null;

    private static $url_handlers = array(
        '$Action!' => '$Action',
        '' => 'edit',
    );

    /**
     *
     * @param GridField $gridField
     * @param GridFieldDetailForm $component
     * @param DataObject $record
     * @param RequestHandler $requestHandler
     * @param string $popupFormName
     */
    public function __construct($gridField, $component, $record, $requestHandler, $popupFormName)
    {
        $this->gridField = $gridField;
        $this->component = $component;
        $this->record = $record;
        $this->popupController = $requestHandler;
        $this->popupFormName = $popupFormName;
        parent::__construct();
    }

    public function Link($action = null)
    {
        return Controller::join_links(
            $this->gridField->Link('item'),
            $this->record->ID ? $this->record->ID : 'new',
            $action
        );
    }

    /**
     * @param HTTPRequest $request
     * @return mixed
     */
    public function view($request)
    {
        if (!$this->record->canView()) {
            $this->httpError(403);
        }

        $controller = $this->getToplevelController();

        $form = $this->ItemEditForm();
        $form->makeReadonly();

        $data = new ArrayData(array(
            'Backlink'     => $controller->Link(),
            'ItemEditForm' => $form
        ));
        $return = $data->renderWith($this->getTemplates());

        if ($request->isAjax()) {
            return $return;
        } else {
            return $controller->customise(array('Content' => $return));
        }
    }

    /**
     * @param HTTPRequest $request
     * @return mixed
     */
    public function edit($request)
    {
        $controller = $this->getToplevelController();
        $form = $this->ItemEditForm();

        $return = $this->customise(array(
            'Backlink' => $controller->hasMethod('Backlink') ? $controller->Backlink() : $controller->Link(),
            'ItemEditForm' => $form,
        ))->renderWith($this->getTemplates());

        if ($request->isAjax()) {
            return $return;
        } else {
            // If not requested by ajax, we need to render it within the controller context+template
            return $controller->customise(array(
                // TODO CMS coupling
                'Content' => $return,
            ));
        }
    }

    /**
     * Builds an item edit form.  The arguments to getCMSFields() are the popupController and
     * popupFormName, however this is an experimental API and may change.
     *
     * @todo In the future, we will probably need to come up with a tigher object representing a partially
     * complete controller with gaps for extra functionality.  This, for example, would be a better way
     * of letting Security/login put its log-in form inside a UI specified elsewhere.
     *
     * @return Form|HTTPResponse
     */
    public function ItemEditForm()
    {
        $list = $this->gridField->getList();

        if (empty($this->record)) {
            $controller = $this->getToplevelController();
            $url = $controller->getRequest()->getURL();
            $noActionURL = $controller->removeAction($url);
            $controller->getResponse()->removeHeader('Location');   //clear the existing redirect
            return $controller->redirect($noActionURL, 302);
        }

        $canView = $this->record->canView();
        $canEdit = $this->record->canEdit();
        $canDelete = $this->record->canDelete();
        $canCreate = $this->record->canCreate();

        if (!$canView) {
            $controller = $this->getToplevelController();
            // TODO More friendly error
            return $controller->httpError(403);
        }

        // Build actions
        $actions = $this->getFormActions();

        // If we are creating a new record in a has-many list, then
        // pre-populate the record's foreign key.
        if ($list instanceof HasManyList && !$this->record->isInDB()) {
            $key = $list->getForeignKey();
            $id = $list->getForeignID();
            $this->record->$key = $id;
        }

        $fields = $this->component->getFields();
        if (!$fields) {
            $fields = $this->record->getCMSFields();
        }

        // If we are creating a new record in a has-many list, then
        // Disable the form field as it has no effect.
        if ($list instanceof HasManyList) {
            $key = $list->getForeignKey();

            if ($field = $fields->dataFieldByName($key)) {
                $fields->makeFieldReadonly($field);
            }
        }

        $form = new Form(
            $this,
            'ItemEditForm',
            $fields,
            $actions,
            $this->component->getValidator()
        );

        $form->loadDataFrom($this->record, $this->record->ID == 0 ? Form::MERGE_IGNORE_FALSEISH : Form::MERGE_DEFAULT);

        if ($this->record->ID && !$canEdit) {
            // Restrict editing of existing records
            $form->makeReadonly();
            // Hack to re-enable delete button if user can delete
            if ($canDelete) {
                $form->Actions()->fieldByName('action_doDelete')->setReadonly(false);
            }
        } elseif (!$this->record->ID && !$canCreate) {
            // Restrict creation of new records
            $form->makeReadonly();
        }

        // Load many_many extraData for record.
        // Fields with the correct 'ManyMany' namespace need to be added manually through getCMSFields().
        if ($list instanceof ManyManyList) {
            $extraData = $list->getExtraData('', $this->record->ID);
            $form->loadDataFrom(array('ManyMany' => $extraData));
        }

        // TODO Coupling with CMS
        $toplevelController = $this->getToplevelController();
        if ($toplevelController && $toplevelController instanceof LeftAndMain) {
            // Always show with base template (full width, no other panels),
            // regardless of overloaded CMS controller templates.
            // TODO Allow customization, e.g. to display an edit form alongside a search form from the CMS controller
            $form->setTemplate([
                'type' => 'Includes',
                'SilverStripe\\Admin\\LeftAndMain_EditForm',
            ]);
            $form->addExtraClass('cms-content cms-edit-form center fill-height flexbox-area-grow');
            $form->setAttribute('data-pjax-fragment', 'CurrentForm Content');
            if ($form->Fields()->hasTabSet()) {
                $form->Fields()->findOrMakeTab('Root')->setTemplate('SilverStripe\\Forms\\CMSTabSet');
                $form->addExtraClass('cms-tabset');
            }

            $form->Backlink = $this->getBackLink();
        }

        $cb = $this->component->getItemEditFormCallback();
        if ($cb) {
            $cb($form, $this);
        }
        $this->extend("updateItemEditForm", $form);
        return $form;
    }

    /**
     * Build the set of form field actions for this DataObject
     *
     * @return FieldList
     */
    protected function getFormActions()
    {
        $canEdit = $this->record->canEdit();
        $canDelete = $this->record->canDelete();
        $actions = new FieldList();
        if ($this->record->ID !== 0) {
            if ($canEdit) {
                $actions->push(FormAction::create('doSave', _t('SilverStripe\\Forms\\GridField\\GridFieldDetailForm.Save', 'Save'))
                    ->setUseButtonTag(true)
                    ->addExtraClass('btn-primary font-icon-save'));
            }

            if ($canDelete) {
                $actions->push(FormAction::create('doDelete', _t('SilverStripe\\Forms\\GridField\\GridFieldDetailForm.Delete', 'Delete'))
                    ->setUseButtonTag(true)
                    ->addExtraClass('btn-outline-danger btn-hide-outline font-icon-trash-bin action-delete'));
            }
        } else { // adding new record
            //Change the Save label to 'Create'
            $actions->push(FormAction::create('doSave', _t('SilverStripe\\Forms\\GridField\\GridFieldDetailForm.Create', 'Create'))
                ->setUseButtonTag(true)
                ->addExtraClass('btn-primary font-icon-plus'));

            // Add a Cancel link which is a button-like link and link back to one level up.
            $crumbs = $this->Breadcrumbs();
            if ($crumbs && $crumbs->count() >= 2) {
                $oneLevelUp = $crumbs->offsetGet($crumbs->count() - 2);
                $text = sprintf(
                    "<a class=\"%s\" href=\"%s\">%s</a>",
                    "crumb btn btn-secondary cms-panel-link", // CSS classes
                    $oneLevelUp->Link, // url
                    _t('SilverStripe\\Forms\\GridField\\GridFieldDetailForm.CancelBtn', 'Cancel') // label
                );
                $actions->push(new LiteralField('cancelbutton', $text));
            }
        }
        $this->extend('updateFormActions', $actions);
        return $actions;
    }

    /**
     * Traverse the nested RequestHandlers until we reach something that's not GridFieldDetailForm_ItemRequest.
     * This allows us to access the Controller responsible for invoking the top-level GridField.
     * This should be equivalent to getting the controller off the top of the controller stack via Controller::curr(),
     * but allows us to avoid accessing the global state.
     *
     * GridFieldDetailForm_ItemRequests are RequestHandlers, and as such they are not part of the controller stack.
     *
     * @return Controller
     */
    protected function getToplevelController()
    {
        $c = $this->popupController;
        while ($c && $c instanceof GridFieldDetailForm_ItemRequest) {
            $c = $c->getController();
        }
        return $c;
    }

    protected function getBackLink()
    {
        // TODO Coupling with CMS
        $backlink = '';
        $toplevelController = $this->getToplevelController();
        if ($toplevelController && $toplevelController instanceof LeftAndMain) {
            if ($toplevelController->hasMethod('Backlink')) {
                $backlink = $toplevelController->Backlink();
            } elseif ($this->popupController->hasMethod('Breadcrumbs')) {
                $parents = $this->popupController->Breadcrumbs(false)->items;
                $backlink = array_pop($parents)->Link;
            }
        }
        if (!$backlink) {
            $backlink = $toplevelController->Link();
        }

        return $backlink;
    }

    /**
     * Get the list of extra data from the $record as saved into it by
     * {@see Form::saveInto()}
     *
     * Handles detection of falsey values explicitly saved into the
     * DataObject by formfields
     *
     * @param DataObject $record
     * @param SS_List $list
     * @return array List of data to write to the relation
     */
    protected function getExtraSavedData($record, $list)
    {
        // Skip extra data if not ManyManyList
        if (!($list instanceof ManyManyList)) {
            return null;
        }

        $data = array();
        foreach ($list->getExtraFields() as $field => $dbSpec) {
            $savedField = "ManyMany[{$field}]";
            if ($record->hasField($savedField)) {
                $data[$field] = $record->getField($savedField);
            }
        }
        return $data;
    }

    public function doSave($data, $form)
    {
        $isNewRecord = $this->record->ID == 0;

        // Check permission
        if (!$this->record->canEdit()) {
            return $this->httpError(403);
        }

        // Save from form data
        $this->saveFormIntoRecord($data, $form);

        $link = '<a href="' . $this->Link('edit') . '">"'
            . htmlspecialchars($this->record->Title, ENT_QUOTES)
            . '"</a>';
        $message = _t(
            'SilverStripe\\Forms\\GridField\\GridFieldDetailForm.Saved',
            'Saved {name} {link}',
            array(
                'name' => $this->record->i18n_singular_name(),
                'link' => $link
            )
        );

        $form->sessionMessage($message, 'good', ValidationResult::CAST_HTML);

        // Redirect after save
        return $this->redirectAfterSave($isNewRecord);
    }

    /**
     * Response object for this request after a successful save
     *
     * @param bool $isNewRecord True if this record was just created
     * @return HTTPResponse|DBHTMLText
     */
    protected function redirectAfterSave($isNewRecord)
    {
        $controller = $this->getToplevelController();
        if ($isNewRecord) {
            return $controller->redirect($this->Link());
        } elseif ($this->gridField->getList()->byID($this->record->ID)) {
            // Return new view, as we can't do a "virtual redirect" via the CMS Ajax
            // to the same URL (it assumes that its content is already current, and doesn't reload)
            return $this->edit($controller->getRequest());
        } else {
            // Changes to the record properties might've excluded the record from
            // a filtered list, so return back to the main view if it can't be found
            $url = $controller->getRequest()->getURL();
            $noActionURL = $controller->removeAction($url);
            $controller->getRequest()->addHeader('X-Pjax', 'Content');
            return $controller->redirect($noActionURL, 302);
        }
    }

    public function httpError($errorCode, $errorMessage = null)
    {
        $controller = $this->getToplevelController();
        return $controller->httpError($errorCode, $errorMessage);
    }

    /**
     * Loads the given form data into the underlying dataobject and relation
     *
     * @param array $data
     * @param Form $form
     * @throws ValidationException On error
     * @return DataObject Saved record
     */
    protected function saveFormIntoRecord($data, $form)
    {
        $list = $this->gridField->getList();

        // Check object matches the correct classname
        if (isset($data['ClassName']) && $data['ClassName'] != $this->record->ClassName) {
            $newClassName = $data['ClassName'];
            // The records originally saved attribute was overwritten by $form->saveInto($record) before.
            // This is necessary for newClassInstance() to work as expected, and trigger change detection
            // on the ClassName attribute
            $this->record->setClassName($this->record->ClassName);
            // Replace $record with a new instance
            $this->record = $this->record->newClassInstance($newClassName);
        }

        // Save form and any extra saved data into this dataobject
        $form->saveInto($this->record);
        $this->record->write();
        $this->extend('onAfterSave', $this->record);

        $extraData = $this->getExtraSavedData($this->record, $list);
        $list->add($this->record, $extraData);

        return $this->record;
    }

    /**
     * @param array $data
     * @param Form $form
     * @return HTTPResponse
     * @throws ValidationException
     */
    public function doDelete($data, $form)
    {
        $title = $this->record->Title;
        if (!$this->record->canDelete()) {
            throw new ValidationException(
                _t('SilverStripe\\Forms\\GridField\\GridFieldDetailForm.DeletePermissionsFailure', "No delete permissions")
            );
        }
        $this->record->delete();

        $message = _t(
            'SilverStripe\\Forms\\GridField\\GridFieldDetailForm.Deleted',
            'Deleted {type} {name}',
            [
                'type' => $this->record->i18n_singular_name(),
                'name' => htmlspecialchars($title, ENT_QUOTES)
            ]
        );

        $toplevelController = $this->getToplevelController();
        if ($toplevelController && $toplevelController instanceof LeftAndMain) {
            $backForm = $toplevelController->getEditForm();
            $backForm->sessionMessage($message, 'good', ValidationResult::CAST_HTML);
        } else {
            $form->sessionMessage($message, 'good', ValidationResult::CAST_HTML);
        }

        //when an item is deleted, redirect to the parent controller
        $controller = $this->getToplevelController();
        $controller->getRequest()->addHeader('X-Pjax', 'Content'); // Force a content refresh

        return $controller->redirect($this->getBackLink(), 302); //redirect back to admin section
    }

    /**
     * @param string $template
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Get list of templates to use
     *
     * @return array
     */
    public function getTemplates()
    {
        $templates = SSViewer::get_templates_by_class($this, '', __CLASS__);
        // Prefer any custom template
        if ($this->getTemplate()) {
            array_unshift($templates, $this->getTemplate());
        }
        return $templates;
    }

    /**
     * @return Controller
     */
    public function getController()
    {
        return $this->popupController;
    }

    /**
     * @return GridField
     */
    public function getGridField()
    {
        return $this->gridField;
    }

    /**
     * @return DataObject
     */
    public function getRecord()
    {
        return $this->record;
    }

    /**
     * CMS-specific functionality: Passes through navigation breadcrumbs
     * to the template, and includes the currently edited record (if any).
     * see {@link LeftAndMain->Breadcrumbs()} for details.
     *
     * @param boolean $unlinked
     * @return ArrayList
     */
    public function Breadcrumbs($unlinked = false)
    {
        if (!$this->popupController->hasMethod('Breadcrumbs')) {
            return null;
        }

        /** @var ArrayList $items */
        $items = $this->popupController->Breadcrumbs($unlinked);

        if ($this->record && $this->record->ID) {
            $title = ($this->record->Title) ? $this->record->Title : "#{$this->record->ID}";
            $items->push(new ArrayData(array(
                'Title' => $title,
                'Link' => $this->Link()
            )));
        } else {
            $items->push(new ArrayData(array(
                'Title' => _t('SilverStripe\\Forms\\GridField\\GridField.NewRecord', 'New {type}', ['type' => $this->record->i18n_singular_name()]),
                'Link' => false
            )));
        }

        $this->extend('updateBreadcrumbs', $items);
        return $items;
    }
}
