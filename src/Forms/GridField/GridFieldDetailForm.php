<?php

namespace SilverStripe\Forms\GridField;

use Closure;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Validator;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Filterable;

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
class GridFieldDetailForm implements GridField_URLHandler
{

    use Extensible;

    /**
     * @var string
     */
    protected $template = null;

    /**
     *
     * @var string
     */
    protected $name;

    /**
     * @var Validator The form validator used for both add and edit fields.
     */
    protected $validator;

    /**
     * @var FieldList Falls back to {@link DataObject->getCMSFields()} if not defined.
     */
    protected $fields;

    /**
     * @var string
     */
    protected $itemRequestClass;

    /**
     * @var callable With two parameters: $form and $component
     */
    protected $itemEditFormCallback;

    public function getURLHandlers($gridField)
    {
        return array(
            'item/$ID' => 'handleItem'
        );
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
     */
    public function __construct($name = 'DetailForm')
    {
        $this->name = $name;
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

        /** @var DataObject $record */
        if (is_numeric($request->param('ID'))) {
            /** @var Filterable $dataList */
            $dataList = $gridField->getList();
            $record = $dataList->byID($request->param("ID"));
        } else {
            $record = Injector::inst()->create($gridField->getModelClass());
        }

        $handler = $this->getItemRequestHandler($gridField, $record, $requestHandler);

        // if no validator has been set on the GridField and the record has a
        // CMS validator, use that.
        if (!$this->getValidator() && ClassInfo::hasMethod($record, 'getCMSValidator')) {
            $this->setValidator($record->getCMSValidator());
        }

        return $handler->handleRequest($request);
    }

    /**
     * Build a request handler for the given record
     *
     * @param GridField $gridField
     * @param DataObject $record
     * @param RequestHandler $requestHandler
     * @return GridFieldDetailForm_ItemRequest
     */
    protected function getItemRequestHandler($gridField, $record, $requestHandler)
    {
        $class = $this->getItemRequestClass();
        $this->extend('updateItemRequestClass', $class, $gridField, $record, $requestHandler);
        /** @var GridFieldDetailForm_ItemRequest $handler */
        $handler = Injector::inst()->createWithArgs(
            $class,
            array($gridField, $this, $record, $requestHandler, $this->name)
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
     * @return String
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
     * @return String
     */
    public function getName()
    {
        return $this->name;
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
        } elseif (ClassInfo::exists(static::class . "_ItemRequest")) {
            return static::class . "_ItemRequest";
        } else {
            return __CLASS__ . '_ItemRequest';
        }
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
