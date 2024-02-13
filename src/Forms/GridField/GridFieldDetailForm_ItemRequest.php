<?php

namespace SilverStripe\Forms\GridField;

use LogicException;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Convert;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\PolymorphicHasManyList;
use SilverStripe\ORM\RelationList;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\View\ArrayData;
use SilverStripe\View\HTML;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ViewableData;

class GridFieldDetailForm_ItemRequest extends RequestHandler
{
    use GridFieldStateAware;

    private static $allowed_actions = [
        'edit',
        'view',
        'ItemEditForm'
    ];

    /**
     * The default form actions available to this item request
     *
     * e.g [
     *     'showPagination': true,
     *     'showAdd': true
     * ]
     *
     * @var array
     */
    private static $formActions = [];

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
     * @var ViewableData
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

    private static $url_handlers = [
        '$Action!' => '$Action',
        '' => 'edit',
    ];

    /**
     *
     * @param GridField $gridField
     * @param GridFieldDetailForm $component
     * @param ViewableData&DataObjectInterface $record
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
        // Assume item can be viewed if canView() isn't implemented
        if ($this->record->hasMethod('canView') && !$this->record->canView()) {
            $this->httpError(403, _t(
                __CLASS__ . '.ViewPermissionsFailure',
                'It seems you don\'t have the necessary permissions to view "{ObjectTitle}"',
                ['ObjectTitle' => $this->getModelName()]
            ));
        }

        $controller = $this->getToplevelController();

        $form = $this->ItemEditForm();
        $form->makeReadonly();

        $data = ArrayData::create([
            'Backlink'     => $controller->Link(),
            'ItemEditForm' => $form
        ]);
        $return = $data->renderWith($this->getTemplates());

        if ($request->isAjax()) {
            return $return;
        } else {
            return $controller->customise(['Content' => $return]);
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

        $return = $this->customise([
            'Backlink' => $controller->hasMethod('Backlink') ? $controller->Backlink() : $controller->Link(),
            'ItemEditForm' => $form,
        ])->renderWith($this->getTemplates());

        if ($request->isAjax()) {
            return $return;
        } else {
            // If not requested by ajax, we need to render it within the controller context+template
            return $controller->customise([
                'Content' => $return,
            ]);
        }
    }

    /**
     * Builds an item edit form.  The arguments to getCMSFields() are the popupController and
     * popupFormName, however this is an experimental API and may change.
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

        // If we are creating a new record in a has-many list, then
        // pre-populate the record's foreign key.
        if ($list instanceof HasManyList && !$this->record->isInDB()) {
            $key = $list->getForeignKey();
            $id = $list->getForeignID();
            $this->record->$key = $id;
            // If the list is polymorphic, add the foreign class as well.
            if ($list instanceof PolymorphicHasManyList) {
                $classKey = $list->getForeignClassKey();
                $class = $list->getForeignClass();
                $this->record->$classKey = $class;

                // If the has_one relation storing the data can handle multiple reciprocal has_many relations,
                // make sure we tell it which has_many relation this belongs to.
                $relation = $list->getForeignRelation();
                if ($relation) {
                    $relationKey = $list->getForeignRelationKey();
                    $this->record->$relationKey = $relation;
                }
            }
        }

        // Assume item can be viewed if canView() isn't implemented
        if ($this->record->hasMethod('canView') && !$this->record->canView()) {
            $controller = $this->getToplevelController();
            return $controller->httpError(403, _t(
                __CLASS__ . '.ViewPermissionsFailure',
                'It seems you don\'t have the necessary permissions to view "{ObjectTitle}"',
                ['ObjectTitle' => $this->getModelName()]
            ));
        }

        $fields = $this->component->getFields();
        if (!$fields) {
            if (!$this->record->hasMethod('getCMSFields')) {
                $modelClass = get_class($this->record);
                throw new LogicException(
                    'Cannot dynamically determine form fields. Pass the fields to GridFieldDetailForm::setFields()'
                    . " or implement a getCMSFields() method on {$modelClass}"
                );
            }
            $fields = $this->record->getCMSFields();
        }

        // If we are creating a new record in a has-many list, then
        // Disable the form field as it has no effect.
        if ($list instanceof HasManyList && !$this->record->isInDB()) {
            $key = $list->getForeignKey();

            if ($field = $fields->dataFieldByName($key)) {
                $fields->makeFieldReadonly($field);
            }
        }

        $form = Form::create(
            $this,
            'ItemEditForm',
            $fields,
            $this->getFormActions(),
            $this->component->getValidator()
        );

        $form->loadDataFrom($this->record, $this->record->ID == 0 ? Form::MERGE_IGNORE_FALSEISH : Form::MERGE_DEFAULT);

        if ($this->record->ID && (!$this->record->hasMethod('canEdit') || !$this->record->canEdit())) {
            // Restrict editing of existing records
            $form->makeReadonly();
            // Hack to re-enable delete button if user can delete
            if ($this->record->hasMethod('canDelete') && $this->record->canDelete()) {
                $form->Actions()->fieldByName('action_doDelete')->setReadonly(false);
            }
        } elseif (!$this->record->ID
            && (!$this->record->hasMethod('canCreate') || !$this->record->canCreate(null, $this->getCreateContext()))
        ) {
            // Restrict creation of new records
            $form->makeReadonly();
        }

        // Load many_many extraData for record.
        // Fields with the correct 'ManyMany' namespace need to be added manually through getCMSFields().
        if ($list instanceof ManyManyList) {
            $extraData = $list->getExtraData('', $this->record->ID);
            $form->loadDataFrom(['ManyMany' => $extraData]);
        }

        $toplevelController = $this->getToplevelController();
        if ($toplevelController && $toplevelController instanceof LeftAndMain) {
            // Always show with base template (full width, no other panels),
            // regardless of overloaded CMS controller templates.
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
     * Build context for verifying canCreate
     * @see GridFieldAddNewButton::getHTMLFragments()
     *
     * @return array
     */
    protected function getCreateContext()
    {
        $gridField = $this->gridField;
        $context = [];
        if ($gridField->getList() instanceof RelationList) {
            $record = $gridField->getForm()->getRecord();
            if ($record && $record instanceof DataObject) {
                $context['Parent'] = $record;
            }
        }
        return $context;
    }

    /**
     * @return CompositeField Returns the right aligned toolbar group field along with its FormAction's
     */
    protected function getRightGroupField()
    {
        $rightGroup = CompositeField::create()->setName('RightGroup');
        $rightGroup->addExtraClass('ml-auto');
        $rightGroup->setFieldHolderTemplate(get_class($rightGroup) . '_holder_buttongroup');

        $previousAndNextGroup = CompositeField::create()->setName('PreviousAndNextGroup');
        $previousAndNextGroup->addExtraClass('btn-group--circular mr-2');
        $previousAndNextGroup->setFieldHolderTemplate(CompositeField::class . '_holder_buttongroup');

        $component = $this->gridField->getConfig()->getComponentByType(GridFieldDetailForm::class);
        $paginator = $this->getGridField()->getConfig()->getComponentByType(GridFieldPaginator::class);
        $gridState = $this->getGridField()->getState();
        if ($component && $paginator && $component->getShowPagination()) {
            $previousIsDisabled = !$this->getPreviousRecordID();
            $nextIsDisabled = !$this->getNextRecordID();

            $previousAndNextGroup->push(
                LiteralField::create(
                    'previous-record',
                    HTML::createTag($previousIsDisabled ? 'span' : 'a', [
                        'href' => $previousIsDisabled ? '#' : $this->getEditLinkForAdjacentRecord(-1),
                        'data-grid-state' => $previousIsDisabled ? $gridState : $this->getGridStateForAdjacentRecord(-1),
                        'title' => _t(__CLASS__ . '.PREVIOUS', 'Go to previous record'),
                        'aria-label' => _t(__CLASS__ . '.PREVIOUS', 'Go to previous record'),
                        'class' => 'btn btn-secondary font-icon-left-open action--previous discard-confirmation'
                            . ($previousIsDisabled ? ' disabled' : ''),
                    ])
                )
            );

            $previousAndNextGroup->push(
                LiteralField::create(
                    'next-record',
                    HTML::createTag($nextIsDisabled ? 'span' : 'a', [
                        'href' => $nextIsDisabled ? '#' : $this->getEditLinkForAdjacentRecord(+1),
                        'data-grid-state' => $nextIsDisabled ? $gridState : $this->getGridStateForAdjacentRecord(+1),
                        'title' => _t(__CLASS__ . '.NEXT', 'Go to next record'),
                        'aria-label' => _t(__CLASS__ . '.NEXT', 'Go to next record'),
                        'class' => 'btn btn-secondary font-icon-right-open action--next discard-confirmation'
                            . ($nextIsDisabled ? ' disabled' : ''),
                    ])
                )
            );
        }

        $rightGroup->push($previousAndNextGroup);

        if ($component && $component->getShowAdd() && $this->record->hasMethod('canCreate') && $this->record->canCreate()) {
            $rightGroup->push(
                LiteralField::create(
                    'new-record',
                    HTML::createTag('a', [
                        'href' => Controller::join_links($this->gridField->Link('item'), 'new'),
                        'data-grid-state' => $gridState,
                        'title' => _t(__CLASS__ . '.NEW', 'Add new record'),
                        'aria-label' => _t(__CLASS__ . '.NEW', 'Add new record'),
                        'class' => 'btn btn-primary font-icon-plus-thin btn--circular action--new discard-confirmation',
                    ])
                )
            );
        }

        return $rightGroup;
    }

    /**
     * Build the set of form field actions for the record being handled
     *
     * @return FieldList
     */
    protected function getFormActions()
    {
        $manager = $this->getStateManager();

        $actions = FieldList::create();
        $majorActions = CompositeField::create()->setName('MajorActions');
        $majorActions->setFieldHolderTemplate(get_class($majorActions) . '_holder_buttongroup');
        $actions->push($majorActions);

        if ($this->record->ID !== null && $this->record->ID !== 0) { // existing record
            if ($this->record->hasMethod('canEdit') && $this->record->canEdit()) {
                if (!($this->record instanceof DataObjectInterface)) {
                    throw new LogicException(get_class($this->record) . ' must implement ' . DataObjectInterface::class);
                }

                $noChangesClasses = 'btn-outline-primary font-icon-tick';
                $majorActions->push(FormAction::create('doSave', _t('SilverStripe\\Forms\\GridField\\GridFieldDetailForm.Save', 'Save'))
                    ->addExtraClass($noChangesClasses)
                    ->setAttribute('data-btn-alternate-add', 'btn-primary font-icon-save')
                    ->setAttribute('data-btn-alternate-remove', $noChangesClasses)
                    ->setUseButtonTag(true)
                    ->setAttribute('data-text-alternate', _t('SilverStripe\\CMS\\Controllers\\CMSMain.SAVEDRAFT', 'Save')));
            }

            if ($this->record->hasMethod('canDelete') && $this->record->canDelete()) {
                if (!($this->record instanceof DataObjectInterface)) {
                    throw new LogicException(get_class($this->record) . ' must implement ' . DataObjectInterface::class);
                }
                $actions->insertAfter('MajorActions', FormAction::create('doDelete', _t('SilverStripe\\Forms\\GridField\\GridFieldDetailForm.Delete', 'Delete'))
                    ->setUseButtonTag(true)
                    ->addExtraClass('btn-outline-danger btn-hide-outline font-icon-trash-bin action--delete'));
            }

            $gridState = $this->gridField->getState(false);
            $actions->push(HiddenField::create($manager->getStateKey($this->gridField), null, $gridState));

            $actions->push($this->getRightGroupField());
        } else { // adding new record
            //Change the Save label to 'Create'
            $majorActions->push(FormAction::create('doSave', _t('SilverStripe\\Forms\\GridField\\GridFieldDetailForm.Create', 'Create'))
                ->setUseButtonTag(true)
                ->addExtraClass('btn-primary font-icon-plus-thin'));

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
                $actions->insertAfter('MajorActions', LiteralField::create('cancelbutton', $text));
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
        $backlink = '';
        $toplevelController = $this->getToplevelController();
        if ($toplevelController && $toplevelController instanceof LeftAndMain) {
            if ($toplevelController->hasMethod('Backlink')) {
                $backlink = $toplevelController->Backlink();
            } elseif ($this->popupController->hasMethod('Breadcrumbs')) {
                $parents = $this->popupController->Breadcrumbs(false);
                if ($parents && $parents = $parents->items) {
                    $backlink = array_pop($parents)->Link;
                }
            }
        }
        if (!$backlink) {
            $backlink = $toplevelController->Link();
        }

        return $this->gridField->addAllStateToUrl($backlink);
    }

    /**
     * Get the list of extra data from the $record as saved into it by
     * {@see Form::saveInto()}
     *
     * Handles detection of falsey values explicitly saved into the
     * record by formfields
     *
     * @param ViewableData $record
     * @param SS_List $list
     * @return array List of data to write to the relation
     */
    protected function getExtraSavedData($record, $list)
    {
        // Skip extra data if not ManyManyList
        if (!($list instanceof ManyManyList)) {
            return null;
        }

        $data = [];
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
        if (!$this->record->hasMethod('canEdit') || !$this->record->canEdit()) {
            $this->httpError(403, _t(
                __CLASS__ . '.EditPermissionsFailure',
                'It seems you don\'t have the necessary permissions to edit "{ObjectTitle}"',
                ['ObjectTitle' => $this->getModelName()]
            ));
            return null;
        }

        // Save from form data
        $this->saveFormIntoRecord($data, $form);

        $link = '<a href="' . $this->Link('edit') . '">"'
            . htmlspecialchars($this->record->Title ?? '', ENT_QUOTES)
            . '"</a>';
        $message = _t(
            'SilverStripe\\Forms\\GridField\\GridFieldDetailForm.Saved',
            'Saved {name} {link}',
            [
                'name' => $this->getModelName(),
                'link' => $link
            ]
        );

        $form->sessionMessage($message, 'good', ValidationResult::CAST_HTML);

        $message = _t(
            __CLASS__ . '.SAVETOASTMESSAGE',
            'Saved {type} "{title}" successfully.',
            [
                'type' => $this->record->i18n_singular_name(),
                'title' => $this->record->Title
            ]
        );

        $controller = $this->getToplevelController();
        $controller->getResponse()->addHeader('X-Status', rawurlencode($message));

        // Redirect after save
        return $this->redirectAfterSave($isNewRecord);
    }

    /**
     * Gets the edit link for a record
     *
     * @param  int $id The ID of the record in the GridField
     * @return string
     */
    public function getEditLink($id)
    {
        $link = Controller::join_links(
            $this->gridField->Link(),
            'item',
            $id
        );

        return $this->gridField->addAllStateToUrl($link);
    }

    /**
     * Return array of GridField items on current page plus
     * first item on the next page and last item on the previous page
     */
    private function getGridFieldItemAdjacencies(): array
    {
        $list = $this->getGridField()->getManipulatedList();
        $paginator = $this->getGridFieldPaginatorState();
        if (!$paginator) {
            return [];
        }
        $currentPage = $paginator->getData('currentPage');
        $itemsPerPage = $paginator->getData('itemsPerPage');

        $limit = $itemsPerPage + 2;
        $limitOffset = max(0, $itemsPerPage * ($currentPage-1) -1);

        return $list->limit($limit, $limitOffset)->column('ID');
    }

    /**
     * Get the current paginator state
     */
    private function getGridFieldPaginatorState(): ?GridState_Data
    {
        $state = $this->getGridField()->getState(false);
        $gridStateStr = $this->getStateManager()->getStateFromRequest($this->gridField, $this->getRequest());
        if (!empty($gridStateStr)) {
            $state->setValue($gridStateStr);
        }

        return $state->getData()->getData('GridFieldPaginator');
    }

    /**
     * Get the grid state for an adjacent record
     */
    private function getGridStateForAdjacentRecord(int $offset): GridState_Data
    {
        $gridField = $this->getGridField();
        $map = $this->getGridFieldItemAdjacencies();
        if (empty($map)) {
            throw new LogicException('No adjacent records exist');
        }

        $state = clone $gridField->getState();
        $index = array_search($this->record->ID, $map);
        $position = $index + $offset;

        $currentPage = $this->getGridFieldPaginatorState()->getData('currentPage');
        $itemsPerPage = $this->getGridFieldPaginatorState()->getData('itemsPerPage');
        $page = $currentPage;
        $hasMorePages = $this->getNumPages($gridField) > $currentPage;

        if ($position === 0 && $currentPage > 1) {
            $page = $currentPage - 1;
        } elseif ($hasMorePages && $position >= $itemsPerPage + 1) {
            $page = $currentPage + 1;
        }
        $state->GridFieldPaginator->currentPage = (int)$page;

        return $state;
    }

    /**
     * Get the edit link for an adjacent record
     */
    private function getEditLinkForAdjacentRecord(int $offset): string
    {
        $link = Controller::join_links(
            $this->gridField->Link(),
            'item',
            $this->getAdjacentRecordID($offset)
        );
        $state = $this->getGridStateForAdjacentRecord($offset);
        // Get a dummy gridfield so we can set some future state without affecting the current gridfield
        $gridField = clone $this->gridField;
        $gridField->getState(false)->setValue($state);
        return $gridField->addAllStateToUrl($link);
    }

    /**
     * @param int $offset The offset from the current record
     * @return int|bool
     */
    private function getAdjacentRecordID($offset)
    {
        $map = $this->getGridFieldItemAdjacencies();
        if (empty($map)) {
            return false;
        }
        $index = array_search($this->record->ID, $map ?? []);
        $position = $index + $offset;
        return isset($map[$position]) ? $map[$position] : false;
    }

    /**
     * Gets the number of GridField pages
     */
    private function getNumPages(GridField $gridField): int
    {
        return $gridField
                ->getConfig()
                ->getComponentByType(GridFieldPaginator::class)
                ?->getTemplateParameters($gridField)
                ?->toMap()['NumPages'] ?? 1;
    }

    /**
     * Gets the ID of the previous record in the list.
     *
     * @return int
     */
    public function getPreviousRecordID()
    {
        return $this->getAdjacentRecordID(-1);
    }

    /**
     * Gets the ID of the next record in the list.
     *
     * @return int
     */
    public function getNextRecordID()
    {
        return $this->getAdjacentRecordID(1);
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
            $message = $controller->getResponse()->getHeader('X-Status') ?? rawurlencode(_t(__CLASS__ . '.SAVEDUP', 'Saved successfully') ?? '');
            $controller->getResponse()->addHeader('X-Status', $message);
            return $this->edit($controller->getRequest());
        } else {
            // We might be able to redirect to open the record in a different view
            if ($redirectDest = $this->component->getLostRecordRedirection($this->gridField, $controller->getRequest(), $this->record->ID)) {
                return $controller->redirect($redirectDest, 302);
            }

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
     * Loads the given form data into the underlying record and relation
     *
     * @param array $data
     * @param Form $form
     * @throws ValidationException On error
     * @return ViewableData&DataObjectInterface Saved record
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

        // Save form and any extra saved data into this record.
        // Set writeComponents = true to write has-one relations / join records
        $form->saveInto($this->record);
        // https://github.com/silverstripe/silverstripe-assets/issues/365
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
        if (!$this->record->hasMethod('canDelete') || !$this->record->canDelete()) {
            throw new ValidationException(
                _t('SilverStripe\\Forms\\GridField\\GridFieldDetailForm.DeletePermissionsFailure', "No delete permissions")
            );
        }
        $this->record->delete();

        $message = _t(
            'SilverStripe\\Forms\\GridField\\GridFieldDetailForm.Deleted',
            'Deleted {type} "{name}"',
            [
                'type' => $this->getModelName(),
                'name' => $this->record->Title
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
        $controller->getResponse()->addHeader('X-Status', rawurlencode($message));

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
     * @return ViewableData
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
     * @return ArrayList<ArrayData>
     */
    public function Breadcrumbs($unlinked = false)
    {
        if (!$this->popupController->hasMethod('Breadcrumbs')) {
            return null;
        }

        /** @var ArrayList<ArrayData> $items */
        $items = $this->popupController->Breadcrumbs($unlinked);

        if (!$items) {
            $items = ArrayList::create();
        }

        if ($this->record && $this->record->ID) {
            $title = ($this->record->Title) ? $this->record->Title : "#{$this->record->ID}";
            $items->push(ArrayData::create([
                'Title' => $title,
                'Link' => $this->Link()
            ]));
        } else {
            $items->push(ArrayData::create([
                'Title' => _t('SilverStripe\\Forms\\GridField\\GridField.NewRecord', 'New {type}', ['type' => $this->getModelName()]),
                'Link' => false
            ]));
        }

        foreach ($items as $item) {
            if ($item->Link) {
                $item->Link = $this->gridField->addAllStateToUrl($item->Link);
            }
        }

        $this->extend('updateBreadcrumbs', $items);
        return $items;
    }

    private function getModelName(): string
    {
        if ($this->record->hasMethod('i18n_singular_name')) {
            return $this->record->i18n_singular_name();
        }
        return ClassInfo::shortName($this->record);
    }
}
