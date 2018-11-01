<?php

namespace SilverStripe\Forms\GridField;

use InvalidArgumentException;
use LogicException;
use SilverStripe\Control\HasRequestHandler;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\HTML;

/**
 * Displays a {@link SS_List} in a grid format.
 *
 * GridField is a field that takes an SS_List and displays it in an table with rows and columns.
 * It reminds of the old TableFields but works with SS_List types and only loads the necessary
 * rows from the list.
 *
 * The minimum configuration is to pass in name and title of the field and a SS_List.
 *
 * <code>
 * $gridField = new GridField('ExampleGrid', 'Example grid', new DataList('Page'));
 * </code>
 *
 * Caution: The form field does not include any JavaScript or CSS when used outside of the CMS context,
 * since the required frontend dependencies are included through CMS bundling.
 *
 * @see SS_List
 *
 * @property GridState_Data $State The gridstate of this object
 */
class GridField extends FormField
{
    /**
     * @var array
     */
    private static $allowed_actions = array(
        'index',
        'gridFieldAlterAction',
    );

    /**
     * Data source.
     *
     * @var SS_List
     */
    protected $list = null;

    /**
     * Class name of the DataObject that the GridField will display.
     *
     * Defaults to the value of $this->list->dataClass.
     *
     * @var string
     */
    protected $modelClassName = '';

    /**
     * Current state of the GridField.
     *
     * @var GridState
     */
    protected $state = null;

    /**
     * @var GridFieldConfig
     */
    protected $config = null;

    /**
     * Components list.
     *
     * @var array
     */
    protected $components = array();

    /**
     * Internal dispatcher for column handlers.
     *
     * Keys are column names and values are GridField_ColumnProvider objects.
     *
     * @var array
     */
    protected $columnDispatch = null;

    /**
     * Map of callbacks for custom data fields.
     *
     * @var array
     */
    protected $customDataFields = array();

    /**
     * @var string
     */
    protected $name = '';

    /**
     * A whitelist of readonly component classes allowed if performReadonlyTransform is called.
     *
     * @var array
     */
    protected $readonlyComponents = [
        GridField_ActionMenu::class,
        GridFieldConfig_RecordViewer::class,
        GridFieldButtonRow::class,
        GridFieldDataColumns::class,
        GridFieldDetailForm::class,
        GridFieldLazyLoader::class,
        GridFieldPageCount::class,
        GridFieldPaginator::class,
        GridFieldFilterHeader::class,
        GridFieldSortableHeader::class,
        GridFieldToolbarHeader::class,
        GridFieldViewButton::class,
        GridState_Component::class,
    ];

    /**
     * Pattern used for looking up
     */
    const FRAGMENT_REGEX = '/\$DefineFragment\(([a-z0-9\-_]+)\)/i';

    /**
     * @param string $name
     * @param string $title
     * @param SS_List $dataList
     * @param GridFieldConfig $config
     */
    public function __construct($name, $title = null, SS_List $dataList = null, GridFieldConfig $config = null)
    {
        parent::__construct($name, $title, null);

        $this->name = $name;

        if ($dataList) {
            $this->setList($dataList);
        }

        if (!$config) {
            $config = GridFieldConfig_Base::create();
        }

        $this->setConfig($config);

        $this->state = new GridState($this);

        $this->addExtraClass('grid-field');
    }

    /**
     * @param HTTPRequest $request
     *
     * @return string
     */
    public function index($request)
    {
        return $this->gridFieldAlterAction(array(), $this->getForm(), $request);
    }

    /**
     * Set the modelClass (data object) that this field will get it column headers from.
     *
     * If no $displayFields has been set, the display fields will be $summary_fields.
     *
     * @see GridFieldDataColumns::getDisplayFields()
     *
     * @param string $modelClassName
     *
     * @return $this
     */
    public function setModelClass($modelClassName)
    {
        $this->modelClassName = $modelClassName;

        return $this;
    }

    /**
     * Returns a data class that is a DataObject type that this GridField should look like.
     *
     * @return string
     *
     * @throws LogicException
     */
    public function getModelClass()
    {
        if ($this->modelClassName) {
            return $this->modelClassName;
        }

        /** @var DataList|ArrayList $list */
        $list = $this->list;
        if ($list && $list->hasMethod('dataClass')) {
            $class = $list->dataClass();

            if ($class) {
                return $class;
            }
        }

        throw new LogicException(
            'GridField doesn\'t have a modelClassName, so it doesn\'t know the columns of this grid.'
        );
    }

    /**
     * Overload the readonly components for this gridfield.
     *
     * @param array $components an array map of component class references to whitelist for a readonly version.
     */
    public function setReadonlyComponents(array $components)
    {
        $this->readonlyComponents = $components;
    }

    /**
     * Return the readonly components
     *
     * @return array a map of component classes.
     */
    public function getReadonlyComponents()
    {
        return $this->readonlyComponents;
    }

    /**
     * Custom Readonly transformation to remove actions which shouldn't be present for a readonly state.
     *
     * @return GridField
     */
    public function performReadonlyTransformation()
    {
        $copy = clone $this;
        $copy->setReadonly(true);
        $copyConfig = $copy->getConfig();

        // get the whitelist for allowable readonly components
        $allowedComponents = $this->getReadonlyComponents();
        foreach ($this->getConfig()->getComponents() as $component) {
            // if a component doesn't exist, remove it from the readonly version.
            if (!in_array(get_class($component), $allowedComponents)) {
                $copyConfig->removeComponent($component);
            }
        }

        // As the edit button may have been removed, add a view button if it doesn't have one
        if (!$copyConfig->getComponentByType(GridFieldViewButton::class)) {
            $copyConfig->addComponent(new GridFieldViewButton);
        }

        return $copy;
    }

    /**
     * Disabling the gridfield should have the same affect as making it readonly (removing all action items).
     *
     * @return GridField
     */
    public function performDisabledTransformation()
    {
        parent::performDisabledTransformation();

        return $this->performReadonlyTransformation();
    }

    /**
     * @return GridFieldConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param GridFieldConfig $config
     *
     * @return $this
     */
    public function setConfig(GridFieldConfig $config)
    {
        $this->config = $config;

        if (!$this->config->getComponentByType(GridState_Component::class)) {
            $this->config->addComponent(new GridState_Component());
        }

        return $this;
    }

    /**
     * @param bool $readonly
     *
     * @return $this
     */
    public function setReadonly($readonly)
    {
        parent::setReadonly($readonly);
        $this->getState()->Readonly = $readonly;
        return $this;
    }

    /**
     * @return ArrayList
     */
    public function getComponents()
    {
        return $this->config->getComponents();
    }

    /**
     * Cast an arbitrary value with the help of a $castingDefinition.
     *
     * @todo refactor this into GridFieldComponent
     *
     * @param mixed $value
     * @param string|array $castingDefinition
     *
     * @return mixed
     */
    public function getCastedValue($value, $castingDefinition)
    {
        $castingParams = array();

        if (is_array($castingDefinition)) {
            $castingParams = $castingDefinition;
            array_shift($castingParams);
            $castingDefinition = array_shift($castingDefinition);
        }

        if (strpos($castingDefinition, '->') === false) {
            $castingFieldType = $castingDefinition;
            $castingField = DBField::create_field($castingFieldType, $value);

            return call_user_func_array(array($castingField, 'XML'), $castingParams);
        }

        list($castingFieldType, $castingMethod) = explode('->', $castingDefinition);

        $castingField = DBField::create_field($castingFieldType, $value);

        return call_user_func_array(array($castingField, $castingMethod), $castingParams);
    }

    /**
     * Set the data source.
     *
     * @param SS_List $list
     *
     * @return $this
     */
    public function setList(SS_List $list)
    {
        $this->list = $list;

        return $this;
    }

    /**
     * Get the data source.
     *
     * @return SS_List
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * Get the data source after applying every {@link GridField_DataManipulator} to it.
     *
     * @return SS_List
     */
    public function getManipulatedList()
    {
        $list = $this->getList();

        foreach ($this->getComponents() as $item) {
            if ($item instanceof GridField_DataManipulator) {
                $list = $item->getManipulatedData($this, $list);
            }
        }

        return $list;
    }

    /**
     * Get the current GridState_Data or the GridState.
     *
     * @param bool $getData
     *
     * @return GridState_Data|GridState
     */
    public function getState($getData = true)
    {
        if ($getData) {
            return $this->state->getData();
        }

        return $this->state;
    }

    /**
     * Returns the whole gridfield rendered with all the attached components.
     *
     * @param array $properties
     * @return string
     */
    public function FieldHolder($properties = array())
    {
        $columns = $this->getColumns();

        $list = $this->getManipulatedList();

        $content = array(
            'before' => '',
            'after' => '',
            'header' => '',
            'footer' => '',
        );

        foreach ($this->getComponents() as $item) {
            if ($item instanceof GridField_HTMLProvider) {
                $fragments = $item->getHTMLFragments($this);

                if ($fragments) {
                    foreach ($fragments as $fragmentKey => $fragmentValue) {
                        $fragmentKey = strtolower($fragmentKey);

                        if (!isset($content[$fragmentKey])) {
                            $content[$fragmentKey] = '';
                        }

                        $content[$fragmentKey] .= $fragmentValue . "\n";
                    }
                }
            }
        }

        foreach ($content as $contentKey => $contentValue) {
            $content[$contentKey] = trim($contentValue);
        }

        // Replace custom fragments and check which fragments are defined. Circular dependencies
        // are detected by disallowing any item to be deferred more than 5 times.

        $fragmentDefined = array(
            'header' => true,
            'footer' => true,
            'before' => true,
            'after' => true,
        );
        $fragmentDeferred = [];

        // TODO: Break the below into separate reducer methods

        // Continue looping if any placeholders exist
        while (array_filter($content, function ($value) {
            return preg_match(self::FRAGMENT_REGEX, $value);
        })) {
            foreach ($content as $contentKey => $contentValue) {
                // Skip if this specific content has no placeholders
                if (!preg_match_all(self::FRAGMENT_REGEX, $contentValue, $matches)) {
                    continue;
                }
                foreach ($matches[1] as $match) {
                    $fragmentName = strtolower($match);
                    $fragmentDefined[$fragmentName] = true;

                    $fragment = '';

                    if (isset($content[$fragmentName])) {
                        $fragment = $content[$fragmentName];
                    }

                    // If the fragment still has a fragment definition in it, when we should defer
                    // this item until later.

                    if (preg_match(self::FRAGMENT_REGEX, $fragment, $matches)) {
                        if (isset($fragmentDeferred[$contentKey]) && $fragmentDeferred[$contentKey] > 5) {
                            throw new LogicException(sprintf(
                                'GridField HTML fragment "%s" and "%s" appear to have a circular dependency.',
                                $fragmentName,
                                $matches[1]
                            ));
                        }

                        unset($content[$contentKey]);

                        $content[$contentKey] = $contentValue;

                        if (!isset($fragmentDeferred[$contentKey])) {
                            $fragmentDeferred[$contentKey] = 0;
                        }

                        $fragmentDeferred[$contentKey]++;

                        break;
                    } else {
                        $content[$contentKey] = preg_replace(
                            sprintf('/\$DefineFragment\(%s\)/i', $fragmentName),
                            $fragment,
                            $content[$contentKey]
                        );
                    }
                }
            }
        }

        // Check for any undefined fragments, and if so throw an exception.
        // While we're at it, trim whitespace off the elements.

        foreach ($content as $contentKey => $contentValue) {
            if (empty($fragmentDefined[$contentKey])) {
                throw new LogicException(sprintf(
                    'GridField HTML fragment "%s" was given content, but not defined. Perhaps there is a supporting GridField component you need to add?',
                    $contentKey
                ));
            }
        }

        $total = count($list);

        if ($total > 0) {
            $rows = array();

            foreach ($list as $index => $record) {
                if ($record->hasMethod('canView') && !$record->canView()) {
                    continue;
                }

                $rowContent = '';

                foreach ($this->getColumns() as $column) {
                    $colContent = $this->getColumnContent($record, $column);

                    // Null means this columns should be skipped altogether.

                    if ($colContent === null) {
                        continue;
                    }

                    $colAttributes = $this->getColumnAttributes($record, $column);

                    $rowContent .= $this->newCell(
                        $total,
                        $index,
                        $record,
                        $colAttributes,
                        $colContent
                    );
                }

                $rowAttributes = $this->getRowAttributes($total, $index, $record);

                $rows[] = $this->newRow($total, $index, $record, $rowAttributes, $rowContent);
            }
            $content['body'] = implode("\n", $rows);
        }

        // Display a message when the grid field is empty.
        if (empty($content['body'])) {
            $cell = HTML::createTag(
                'td',
                array(
                    'colspan' => count($columns),
                ),
                _t('SilverStripe\\Forms\\GridField\\GridField.NoItemsFound', 'No items found')
            );

            $row = HTML::createTag(
                'tr',
                array(
                    'class' => 'ss-gridfield-item ss-gridfield-no-items',
                ),
                $cell
            );

            $content['body'] = $row;
        }

        $header = $this->getOptionalTableHeader($content);
        $body = $this->getOptionalTableBody($content);
        $footer = $this->getOptionalTableFooter($content);

        $this->addExtraClass('ss-gridfield grid-field field');

        $fieldsetAttributes = array_diff_key(
            $this->getAttributes(),
            array(
                'value' => false,
                'type' => false,
                'name' => false,
            )
        );

        $fieldsetAttributes['data-name'] = $this->getName();

        $tableId = null;

        if ($this->id) {
            $tableId = $this->id;
        }

        $tableAttributes = array(
            'id' => $tableId,
            'class' => 'table grid-field__table',
            'cellpadding' => '0',
            'cellspacing' => '0'
        );

        if ($this->getDescription()) {
            $content['after'] .= HTML::createTag(
                'span',
                array('class' => 'description'),
                $this->getDescription()
            );
        }

        $table = HTML::createTag(
            'table',
            $tableAttributes,
            $header . "\n" . $footer . "\n" . $body
        );

        return HTML::createTag(
            'fieldset',
            $fieldsetAttributes,
            $content['before'] . $table . $content['after']
        );
    }

    /**
     * @param int $total
     * @param int $index
     * @param DataObject $record
     * @param array $attributes
     * @param string $content
     *
     * @return string
     */
    protected function newCell($total, $index, $record, $attributes, $content)
    {
        return HTML::createTag(
            'td',
            $attributes,
            $content
        );
    }

    /**
     * @param int $total
     * @param int $index
     * @param DataObject $record
     * @param array $attributes
     * @param string $content
     *
     * @return string
     */
    protected function newRow($total, $index, $record, $attributes, $content)
    {
        return HTML::createTag(
            'tr',
            $attributes,
            $content
        );
    }

    /**
     * @param int $total
     * @param int $index
     * @param DataObject $record
     *
     * @return array
     */
    protected function getRowAttributes($total, $index, $record)
    {
        $rowClasses = $this->newRowClasses($total, $index, $record);

        return array(
            'class' => implode(' ', $rowClasses),
            'data-id' => $record->ID,
            'data-class' => $record->ClassName,
        );
    }

    /**
     * @param int $total
     * @param int $index
     * @param DataObject $record
     *
     * @return array
     */
    protected function newRowClasses($total, $index, $record)
    {
        $classes = array('ss-gridfield-item');

        if ($index == 0) {
            $classes[] = 'first';
        }

        if ($index == $total - 1) {
            $classes[] = 'last';
        }

        if ($index % 2) {
            $classes[] = 'even';
        } else {
            $classes[] = 'odd';
        }

        $this->extend('updateNewRowClasses', $classes, $total, $index, $record);

        return $classes;
    }

    /**
     * @param array $properties
     * @return string
     */
    public function Field($properties = array())
    {
        $this->extend('onBeforeRender', $this);
        return $this->FieldHolder($properties);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return array_merge(
            parent::getAttributes(),
            array(
                'data-url' => $this->Link(),
            )
        );
    }

    /**
     * Get the columns of this GridField, they are provided by attached GridField_ColumnProvider.
     *
     * @return array
     */
    public function getColumns()
    {
        $columns = array();

        foreach ($this->getComponents() as $item) {
            if ($item instanceof GridField_ColumnProvider) {
                $item->augmentColumns($this, $columns);
            }
        }

        return $columns;
    }

    /**
     * Get the value from a column.
     *
     * @param DataObject $record
     * @param string $column
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getColumnContent($record, $column)
    {
        if (!$this->columnDispatch) {
            $this->buildColumnDispatch();
        }

        if (!empty($this->columnDispatch[$column])) {
            $content = '';

            foreach ($this->columnDispatch[$column] as $handler) {
                /**
                 * @var GridField_ColumnProvider $handler
                 */
                $content .= $handler->getColumnContent($this, $record, $column);
            }

            return $content;
        } else {
            throw new InvalidArgumentException(sprintf(
                'Bad column "%s"',
                $column
            ));
        }
    }

    /**
     * Add additional calculated data fields to be used on this GridField
     *
     * @param array $fields a map of fieldname to callback. The callback will
     *                      be passed the record as an argument.
     */
    public function addDataFields($fields)
    {
        if ($this->customDataFields) {
            $this->customDataFields = array_merge($this->customDataFields, $fields);
        } else {
            $this->customDataFields = $fields;
        }
    }

    /**
     * Get the value of a named field  on the given record.
     *
     * Use of this method ensures that any special rules around the data for this gridfield are
     * followed.
     *
     * @param DataObject $record
     * @param string $fieldName
     *
     * @return mixed
     */
    public function getDataFieldValue($record, $fieldName)
    {
        if (isset($this->customDataFields[$fieldName])) {
            $callback = $this->customDataFields[$fieldName];

            return $callback($record);
        }

        if ($record->hasMethod('relField')) {
            return $record->relField($fieldName);
        }

        if ($record->hasMethod($fieldName)) {
            return $record->$fieldName();
        }

        return $record->$fieldName;
    }

    /**
     * Get extra columns attributes used as HTML attributes.
     *
     * @param DataObject $record
     * @param string $column
     *
     * @return array
     *
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    public function getColumnAttributes($record, $column)
    {
        if (!$this->columnDispatch) {
            $this->buildColumnDispatch();
        }

        if (!empty($this->columnDispatch[$column])) {
            $attributes = array();

            foreach ($this->columnDispatch[$column] as $handler) {
                /**
                 * @var GridField_ColumnProvider $handler
                 */
                $columnAttributes = $handler->getColumnAttributes($this, $record, $column);

                if (is_array($columnAttributes)) {
                    $attributes = array_merge($attributes, $columnAttributes);
                    continue;
                }

                throw new LogicException(sprintf(
                    'Non-array response from %s::getColumnAttributes().',
                    get_class($handler)
                ));
            }

            return $attributes;
        }

        throw new InvalidArgumentException(sprintf(
            'Bad column "%s"',
            $column
        ));
    }

    /**
     * Get metadata for a column.
     *
     * @example "array('Title'=>'Email address')"
     *
     * @param string $column
     *
     * @return array
     *
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    public function getColumnMetadata($column)
    {
        if (!$this->columnDispatch) {
            $this->buildColumnDispatch();
        }

        if (!empty($this->columnDispatch[$column])) {
            $metaData = array();

            foreach ($this->columnDispatch[$column] as $handler) {
                /**
                 * @var GridField_ColumnProvider $handler
                 */
                $columnMetaData = $handler->getColumnMetadata($this, $column);

                if (is_array($columnMetaData)) {
                    $metaData = array_merge($metaData, $columnMetaData);
                    continue;
                }

                throw new LogicException(sprintf(
                    'Non-array response from %s::getColumnMetadata().',
                    get_class($handler)
                ));
            }

            return $metaData;
        }

        throw new InvalidArgumentException(sprintf(
            'Bad column "%s"',
            $column
        ));
    }

    /**
     * Return how many columns the grid will have.
     *
     * @return int
     */
    public function getColumnCount()
    {
        if (!$this->columnDispatch) {
            $this->buildColumnDispatch();
        }

        return count($this->columnDispatch);
    }

    /**
     * Build an columnDispatch that maps a GridField_ColumnProvider to a column for reference later.
     */
    protected function buildColumnDispatch()
    {
        $this->columnDispatch = array();

        foreach ($this->getComponents() as $item) {
            if ($item instanceof GridField_ColumnProvider) {
                $columns = $item->getColumnsHandled($this);

                foreach ($columns as $column) {
                    $this->columnDispatch[$column][] = $item;
                }
            }
        }
    }

    /**
     * This is the action that gets executed when a GridField_AlterAction gets clicked.
     *
     * @param array $data
     * @param Form $form
     * @param HTTPRequest $request
     *
     * @return string
     */
    public function gridFieldAlterAction($data, $form, HTTPRequest $request)
    {
        $data = $request->requestVars();

        // Protection against CSRF attacks
        $token = $this
            ->getForm()
            ->getSecurityToken();
        if (!$token->checkRequest($request)) {
            $this->httpError(400, _t(
                "SilverStripe\\Forms\\Form.CSRF_FAILED_MESSAGE",
                "There seems to have been a technical problem. Please click the back button, " . "refresh your browser, and try again."
            ));
        }

        $name = $this->getName();

        $fieldData = null;

        if (isset($data[$name])) {
            $fieldData = $data[$name];
        }

        $state = $this->getState(false);

        /** @skipUpgrade */
        if (isset($fieldData['GridState'])) {
            $state->setValue($fieldData['GridState']);
        }

        foreach ($data as $dataKey => $dataValue) {
            if (preg_match('/^action_gridFieldAlterAction\?StateID=(.*)/', $dataKey, $matches)) {
                $stateChange = $request->getSession()->get($matches[1]);
                $actionName = $stateChange['actionName'];

                $arguments = array();

                if (isset($stateChange['args'])) {
                    $arguments = $stateChange['args'];
                };

                $html = $this->handleAlterAction($actionName, $arguments, $data);

                if ($html) {
                    return $html;
                }
            }
        }

        if ($request->getHeader('X-Pjax') === 'CurrentField') {
            if ($this->getState()->Readonly === true) {
                $this->performDisabledTransformation();
            }
            return $this->FieldHolder();
        }

        return $form->forTemplate();
    }

    /**
     * Pass an action on the first GridField_ActionProvider that matches the $actionName.
     *
     * @param string $actionName
     * @param mixed $arguments
     * @param array $data
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    public function handleAlterAction($actionName, $arguments, $data)
    {
        $actionName = strtolower($actionName);

        foreach ($this->getComponents() as $component) {
            if ($component instanceof GridField_ActionProvider) {
                $actions = array_map('strtolower', (array) $component->getActions($this));

                if (in_array($actionName, $actions)) {
                    return $component->handleAction($this, $actionName, $arguments, $data);
                }
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Can\'t handle action "%s"',
            $actionName
        ));
    }

    /**
     * Custom request handler that will check component handlers before proceeding to the default
     * implementation.
     *
     * @todo copy less code from RequestHandler.
     *
     * @param HTTPRequest $request
     * @return array|RequestHandler|HTTPResponse|string
     * @throws HTTPResponse_Exception
     */
    public function handleRequest(HTTPRequest $request)
    {
        if ($this->brokenOnConstruct) {
            user_error(
                sprintf(
                    "parent::__construct() needs to be called on %s::__construct()",
                    __CLASS__
                ),
                E_USER_WARNING
            );
        }

        $this->setRequest($request);

        $fieldData = $this->getRequest()->requestVar($this->getName());

        /** @skipUpgrade */
        if ($fieldData && isset($fieldData['GridState'])) {
            $this->getState(false)->setValue($fieldData['GridState']);
        }

        foreach ($this->getComponents() as $component) {
            if ($component instanceof GridField_URLHandler && $urlHandlers = $component->getURLHandlers($this)) {
                foreach ($urlHandlers as $rule => $action) {
                    if ($params = $request->match($rule, true)) {
                        // Actions can reference URL parameters.
                        // e.g. '$Action/$ID/$OtherID' → '$Action'

                        if ($action[0] == '$') {
                            $action = $params[substr($action, 1)];
                        }

                        if (!method_exists($component, 'checkAccessAction') || $component->checkAccessAction($action)) {
                            if (!$action) {
                                $action = "index";
                            }

                            if (!is_string($action)) {
                                throw new LogicException(sprintf(
                                    'Non-string method name: %s',
                                    var_export($action, true)
                                ));
                            }

                            try {
                                $result = $component->$action($this, $request);
                            } catch (HTTPResponse_Exception $responseException) {
                                $result = $responseException->getResponse();
                            }

                            if ($result instanceof HTTPResponse && $result->isError()) {
                                return $result;
                            }

                            if ($this !== $result &&
                                !$request->isEmptyPattern($rule) &&
                                ($result instanceof RequestHandler || $result instanceof HasRequestHandler)
                            ) {
                                if ($result instanceof HasRequestHandler) {
                                    $result = $result->getRequestHandler();
                                }
                                $returnValue = $result->handleRequest($request);

                                if (is_array($returnValue)) {
                                    throw new LogicException(
                                        'GridField_URLHandler handlers can\'t return arrays'
                                    );
                                }

                                return $returnValue;
                            }

                            if ($request->allParsed()) {
                                return $result;
                            }

                            return $this->httpError(
                                404,
                                sprintf(
                                    'I can\'t handle sub-URLs of a %s object.',
                                    get_class($result)
                                )
                            );
                        }
                    }
                }
            }
        }

        return parent::handleRequest($request);
    }

    /**
     * {@inheritdoc}
     */
    public function saveInto(DataObjectInterface $record)
    {
        foreach ($this->getComponents() as $component) {
            if ($component instanceof GridField_SaveHandler) {
                $component->handleSave($this, $record);
            }
        }
    }

    /**
     * @param array $content
     *
     * @return string
     */
    protected function getOptionalTableHeader(array $content)
    {
        if ($content['header']) {
            return HTML::createTag(
                'thead',
                array(),
                $content['header']
            );
        }

        return '';
    }

    /**
     * @param array $content
     *
     * @return string
     */
    protected function getOptionalTableBody(array $content)
    {
        if ($content['body']) {
            return HTML::createTag(
                'tbody',
                array('class' => 'ss-gridfield-items'),
                $content['body']
            );
        }

        return '';
    }

    /**
     * @param $content
     *
     * @return string
     */
    protected function getOptionalTableFooter($content)
    {
        if ($content['footer']) {
            return HTML::createTag(
                'tfoot',
                array(),
                $content['footer']
            );
        }

        return '';
    }
}
