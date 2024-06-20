<?php

namespace SilverStripe\Forms\GridField;

use Closure;
use LogicException;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\HTTPStreamResponse;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FieldsValidator;
use SilverStripe\Forms\Validator;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ViewableData;

/**
 * Provides view and edit forms at GridField-specific URLs.
 *
 * These can be placed into pop-ups by an appropriate front-end.
 *
 * Usually added to a {@link GridField} alongside of a
 * {@link GridFieldEditButton} which takes care of linking the
 * individual rows to their edit view.
 *
 * The URLs provided will be off the following form:
 *  - <FormURL>/field/<GridFieldName>/item/<RecordID>
 *  - <FormURL>/field/<GridFieldName>/item/<RecordID>/edit
 */
class GridFieldDetailForm extends AbstractGridFieldComponent implements GridField_URLHandler
{

    use Extensible, GridFieldStateAware;

    /**
     * @var string
     */
    protected $template = null;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $showPagination;

    /**
     * @var bool
     */
    protected $showAdd;

    /**
     * @var Validator The form validator used for both add and edit fields.
     */
    protected $validator;

    /**
     * @var FieldList Falls back to {@link $record->getCMSFields()} if not defined.
     */
    protected $fields;

    /**
     * @var string
     */
    protected $itemRequestClass;

    /**
     * If true, will redirect to missing records if they are found elsewhere
     * @var bool
     */
    protected $redirectMissingRecords = false;

    /**
     * @var callable With two parameters: $form and $component
     */
    protected $itemEditFormCallback;

    public function getURLHandlers($gridField)
    {
        return [
            'item/$ID' => 'handleItem'
        ];
    }

    /**
     * Create a popup component. The two arguments will specify how the popup form's HTML and
     * behaviour is created.  The given controller will be customised, putting the edit form into the
     * template with the given name.
     *
     * The arguments are experimental API's to support partial content to be passed back to whatever
     * controller who wants to display the getCMSFields
     *
     * @param string $name The name of the edit form to place into the pop-up form
     * @param bool $showPagination Whether the `Previous` and `Next` buttons should display or not, leave as null to use default
     * @param bool $showAdd Whether the `Add` button should display or not, leave as null to use default
     */
    public function __construct($name = null, $showPagination = null, $showAdd = null)
    {
        $this->setName($name ?: 'DetailForm');
        $this->setShowPagination($showPagination);
        $this->setShowAdd($showAdd);
    }

    /**
     *
     * @param GridField $gridField
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function handleItem($gridField, $request)
    {
        // Our getController could either give us a true Controller, if this is the top-level GridField.
        // It could also give us a RequestHandler in the form of GridFieldDetailForm_ItemRequest if this is a
        // nested GridField.
        $requestHandler = $gridField->getForm()->getController();
        $record = $this->getRecordFromRequest($gridField, $request);
        if (!$record) {
            // Look for the record elsewhere in the CMS
            $redirectDest = $this->getLostRecordRedirection($gridField, $request);
            // Don't allow infinite redirections
            if ($redirectDest) {
                // Mark the remainder of the URL as parsed to trigger an immediate redirection
                while (!$request->allParsed()) {
                    $request->shift();
                }
                return (new HTTPResponse())->redirect($redirectDest);
            }

            return $requestHandler->httpError(404, 'That record was not found');
        }
        $handler = $this->getItemRequestHandler($gridField, $record, $requestHandler);
        $manager = $this->getStateManager();
        if ($gridStateStr = $manager->getStateFromRequest($gridField, $request)) {
            $gridField->getState(false)->setValue($gridStateStr);
        }

        // if no validator has been set on the GridField then use the Validators from the record.
        if (!$this->getValidator()) {
            if ($record->hasMethod('getCMSCompositeValidator')) {
                $validator = $record->getCMSCompositeValidator();
            } else {
                $validator = FieldsValidator::create();
            }
            $this->setValidator($validator);
        }

        return $handler->handleRequest($request);
    }

    protected function getRecordFromRequest(GridField $gridField, HTTPRequest $request): ?ViewableData
    {
        /** @var ViewableData $record */
        if (is_numeric($request->param('ID'))) {
            $dataList = $gridField->getList();
            $record = $dataList->byID($request->param('ID'));
        } else {
            $record = Injector::inst()->create($gridField->getModelClass());
        }

        if ($record && !$record->hasField('ID')) {
            throw new LogicException(get_class($record) . ' must have an ID field.');
        }

        return $record;
    }

    /**
     * Try and find another URL at which the given record can be edited.
     * If redirectMissingRecords is true and the record has a CMSEditLink method, that value will be returned.
     * This only works when the list passed to the GridField is a {@link DataList}.
     *
     * @param $gridField The current GridField
     * @param $id The ID of the record to open
     */
    public function getLostRecordRedirection(GridField $gridField, HTTPRequest $request, ?int $id = null): ?string
    {

        if (!$this->redirectMissingRecords) {
            return null;
        }

        // If not supplied, look up the ID from the request
        if ($id === null && is_numeric($request->param('ID'))) {
            $id = (int)$request->param('ID');
        }

        if (!$id) {
            return null;
        }

        $list = $gridField->getList();
        if (!$list instanceof DataList) {
            throw new \LogicException('List is not of type DataList, cannot determine redirection target');
        }

        $existing = DataObject::get($list->dataClass())->byID($id);
        if ($existing && $existing->hasMethod('CMSEditLink')) {
            $link = $existing->CMSEditLink();
        }

        if ($link && $link == $request->getURL()) {
            throw new \LogicException(sprintf(
                'Infinite redirection to "%s" detected in GridFieldDetailForm->getLostRecordRedirection()',
                $link
            ));
        }

        return $link;
    }

    /**
     * Build a request handler for the given record
     *
     * @param GridField $gridField
     * @param ViewableData $record
     * @param RequestHandler $requestHandler
     * @return GridFieldDetailForm_ItemRequest
     */
    protected function getItemRequestHandler($gridField, $record, $requestHandler)
    {
        $class = $this->getItemRequestClass();
        $assignedClass = $this->itemRequestClass;
        $this->extend('updateItemRequestClass', $class, $gridField, $record, $requestHandler, $assignedClass);
        /** @var GridFieldDetailForm_ItemRequest $handler */
        $handler = Injector::inst()->createWithArgs(
            $class,
            [$gridField, $this, $record, $requestHandler, $this->name]
        );
        if ($template = $this->getTemplate()) {
            $handler->setTemplate($template);
        }
        $this->extend('updateItemRequestHandler', $handler);
        return $handler;
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
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Enable redirection to missing records.
     *
     * If a GridField shows a filtered list, and the record is not in the list but exists in the
     * database, and the record has a CMSEditLink method, then the system will redirect to the
     * URL returned by that method.
     */
    public function setRedirectMissingRecords(bool $redirectMissingRecords): GridFieldDetailForm
    {
        $this->redirectMissingRecords = $redirectMissingRecords;
        return $this;
    }

    /**
     * Return the status of redirection to missing records.
     * @see setRedirectMissingRecordssetRedirectMissingRecords
     */
    public function getRedirectMissingRecords(): bool
    {
        return $this->redirectMissingRecords;
    }

    /**
     * @return bool
     */
    protected function getDefaultShowPagination()
    {
        $formActionsConfig = GridFieldDetailForm_ItemRequest::config()->get('formActions');
        return isset($formActionsConfig['showPagination']) ? (bool) $formActionsConfig['showPagination'] : false;
    }

    /**
     * @return bool
     */
    public function getShowPagination()
    {
        if ($this->showPagination === null) {
            return $this->getDefaultShowPagination();
        }

        return (bool) $this->showPagination;
    }

    /**
     * @param bool|null $showPagination
     * @return GridFieldDetailForm
     */
    public function setShowPagination($showPagination)
    {
        $this->showPagination = $showPagination;
        return $this;
    }

    /**
     * @return bool
     */
    protected function getDefaultShowAdd()
    {
        $formActionsConfig = GridFieldDetailForm_ItemRequest::config()->get('formActions');
        return isset($formActionsConfig['showAdd']) ? (bool) $formActionsConfig['showAdd'] : false;
    }

    /**
     * @return bool
     */
    public function getShowAdd()
    {
        if ($this->showAdd === null) {
            return $this->getDefaultShowAdd();
        }

        return (bool) $this->showAdd;
    }

    /**
     * @param bool|null $showAdd
     * @return GridFieldDetailForm
     */
    public function setShowAdd($showAdd)
    {
        $this->showAdd = $showAdd;
        return $this;
    }

    /**
     * @param Validator $validator
     * @return $this
     */
    public function setValidator(Validator $validator)
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * @return Validator
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * @param FieldList $fields
     * @return $this
     */
    public function setFields(FieldList $fields)
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * @return FieldList
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param string $class
     * @return $this
     */
    public function setItemRequestClass($class)
    {
        $this->itemRequestClass = $class;
        return $this;
    }

    /**
     * @return string name of {@see GridFieldDetailForm_ItemRequest} subclass
     */
    public function getItemRequestClass()
    {
        if ($this->itemRequestClass) {
            return $this->itemRequestClass;
        } elseif (ClassInfo::exists(static::class . '_ItemRequest')) {
            return static::class . '_ItemRequest';
        }
        return GridFieldDetailForm_ItemRequest::class;
    }

    /**
     * @param Closure $cb Make changes on the edit form after constructing it.
     * @return $this
     */
    public function setItemEditFormCallback(Closure $cb)
    {
        $this->itemEditFormCallback = $cb;
        return $this;
    }

    /**
     * @return Closure
     */
    public function getItemEditFormCallback()
    {
        return $this->itemEditFormCallback;
    }
}
