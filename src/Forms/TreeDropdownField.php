<?php

namespace SilverStripe\Forms;

use SilverStripe\Assets\Folder;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\Hierarchy\MarkedSet;
use SilverStripe\View\ViewableData;
use Exception;
use InvalidArgumentException;

/**
 * Dropdown-like field that allows you to select an item from a hierarchical
 * AJAX-expandable tree.
 *
 * Creates a field which opens a dropdown (actually a div via javascript
 * included for you) which contains a tree with the ability to select a singular
 * item for the value of the field. This field has the ability to store one-to-one
 * joins related to hierarchy or a hierarchy based filter.
 *
 * **Note:** your source object must use an implementation of hierarchy for this
 * field to generate the tree correctly, e.g. {@link Group}, {@link SiteTree} etc.
 *
 * All operations are carried out through javascript and provides no fallback
 * to non JS.
 *
 * <b>Usage</b>.
 *
 * <code>
 * static $has_one = array(
 *   'RightContent' => 'SiteTree'
 * );
 *
 * function getCMSFields() {
 * ...
 * $treedropdownfield = new TreeDropdownField("RightContentID", "Choose a page to show on the right:", "SiteTree");
 * ..
 * }
 * </code>
 *
 * This will generate a tree allowing the user to expand and contract subsections
 * to find the appropriate page to save to the field.
 *
 * Caution: The form field does not include any JavaScript or CSS when used outside of the CMS context,
 * since the required frontend dependencies are included through CMS bundling.
 *
 * @see TreeMultiselectField for the same implementation allowing multiple selections
 * @see DropdownField for a simple dropdown field.
 * @see CheckboxSetField for multiple selections through checkboxes.
 * @see OptionsetField for single selections via radiobuttons.
 */
class TreeDropdownField extends FormField
{
    private static $url_handlers = array(
        '$Action!/$ID' => '$Action'
    );

    private static $allowed_actions = array(
        'tree'
    );

    /**
     * Class name for underlying object
     *
     * @var string
     */
    protected $sourceObject = null;

    /**
     * Name of key field on underlying object
     *
     * @var string
     */
    protected $keyField = null;

    /**
     * Name of lavel field on underlying object
     *
     * @var string
     */
    protected $labelField = null;

    /**
     * Callback for filtering records
     *
     * @var callable
     */
    protected $filterCallback = null;

    /**
     * Callback for marking record as disabled
     *
     * @var callable
     */
    protected $disableCallback = null;

    /**
     * Callback for searching records. This callback takes the following arguments:
     *  - sourceObject Object class to search
     *  - labelField Label field
     *  - search Search text
     *
     * @var callable
     */
    protected $searchCallback = null;

    /**
     * Filter for base record
     *
     * @var int
     */
    protected $baseID = 0;

    /**
     * Default child method in Hierarchy->getChildrenAsUL
     *
     * @var string
     */
    protected $childrenMethod = 'AllChildrenIncludingDeleted';

    /**
     * Default child counting method in Hierarchy->getChildrenAsUL
     *
     * @var string
     */
    protected $numChildrenMethod = 'numChildren';

    /**
     * Current string value for search text to filter on
     *
     * @var string
     */
    protected $search = null;

    /**
     * List of ids in current search result (keys are ids, values are true)
     *
     * @var array
     */
    protected $searchIds = [];

    /**
     * Determine if search should be shown
     *
     * @var bool
     */
    protected $showSearch = false;

    /**
     * List of ids which have their search expanded (keys are ids, values are true)
     *
     * @var array
     */
    protected $searchExpanded = [];

    /**
     * CAVEAT: for search to work properly $labelField must be a database field,
     * or you need to setSearchFunction.
     *
     * @param string $name the field name
     * @param string $title the field label
     * @param string $sourceObject A DataObject class name with the {@link Hierarchy} extension.
     * @param string $keyField to field on the source class to save as the
     *      field value (default ID).
     * @param string $labelField the field name to show as the human-readable
     *      value on the tree (default Title).
     * @param bool $showSearch enable the ability to search the tree by
     *      entering the text in the input field.
     */
    public function __construct(
        $name,
        $title = null,
        $sourceObject = null,
        $keyField = 'ID',
        $labelField = 'TreeTitle',
        $showSearch = true
    ) {
        if (!is_a($sourceObject, DataObject::class, true)) {
            throw new InvalidArgumentException("SourceObject must be a DataObject subclass");
        }
        if (!DataObject::has_extension($sourceObject, Hierarchy::class)) {
            throw new InvalidArgumentException("SourceObject must have Hierarchy extension");
        }
        $this->sourceObject = $sourceObject;
        $this->keyField     = $keyField;
        $this->labelField   = $labelField;
        $this->showSearch   = $showSearch;

        // Extra settings for Folders
        if (strcasecmp($sourceObject, Folder::class) === 0) {
            $this->childrenMethod = 'ChildFolders';
            $this->numChildrenMethod = 'numChildFolders';
        }

        $this->addExtraClass('single');

        parent::__construct($name, $title);
    }

    /**
     * Set the ID of the root node of the tree. This defaults to 0 - i.e.
     * displays the whole tree.
     *
     * @param int $ID
     * @return $this
     */
    public function setTreeBaseID($ID)
    {
        $this->baseID = (int) $ID;
        return $this;
    }

    /**
     * Set a callback used to filter the values of the tree before
     * displaying to the user.
     *
     * @param callback $callback
     * @return $this
     */
    public function setFilterFunction($callback)
    {
        if (!is_callable($callback, true)) {
            throw new InvalidArgumentException('TreeDropdownField->setFilterCallback(): not passed a valid callback');
        }

        $this->filterCallback = $callback;
        return $this;
    }

    /**
     * Set a callback used to disable checkboxes for some items in the tree
     *
     * @param callback $callback
     * @return $this
     */
    public function setDisableFunction($callback)
    {
        if (!is_callable($callback, true)) {
            throw new InvalidArgumentException('TreeDropdownField->setDisableFunction(): not passed a valid callback');
        }

        $this->disableCallback = $callback;
        return $this;
    }

    /**
     * Set a callback used to search the hierarchy globally, even before
     * applying the filter.
     *
     * @param callback $callback
     * @return $this
     */
    public function setSearchFunction($callback)
    {
        if (!is_callable($callback, true)) {
            throw new InvalidArgumentException('TreeDropdownField->setSearchFunction(): not passed a valid callback');
        }

        $this->searchCallback = $callback;
        return $this;
    }

    /**
     * Check if search is shown
     *
     * @return bool
     */
    public function getShowSearch()
    {
        return $this->showSearch;
    }

    /**
     * @param bool $bool
     * @return $this
     */
    public function setShowSearch($bool)
    {
        $this->showSearch = $bool;
        return $this;
    }

    /**
     * @param string $method The parameter to ChildrenMethod to use when calling Hierarchy->getChildrenAsUL in
     * {@link Hierarchy}. The method specified determines the structure of the returned list. Use "ChildFolders"
     * in place of the default to get a drop-down listing with only folders, i.e. not including the child elements in
     * the currently selected folder. setNumChildrenMethod() should be used as well for proper functioning.
     *
     * See {@link Hierarchy} for a complete list of possible methods.
     * @return $this
     */
    public function setChildrenMethod($method)
    {
        $this->childrenMethod = $method;
        return $this;
    }

    /**
     * @param string $method The parameter to numChildrenMethod to use when calling Hierarchy->getChildrenAsUL in
     * {@link Hierarchy}. Should be used in conjunction with setChildrenMethod().
     *
     * @return $this
     */
    public function setNumChildrenMethod($method)
    {
        $this->numChildrenMethod = $method;
        return $this;
    }

    /**
     * @param array $properties
     * @return string
     */
    public function Field($properties = array())
    {
        $item = DataObject::singleton($this->sourceObject);
        $emptyTitle = _t(
            'DropdownField.CHOOSE_MODEL',
            '(Choose {name})',
            ['name' => $item->i18n_singular_name()]
        );

        $record = $this->Value() ? $this->objectForKey($this->Value()) : null;
        if ($record instanceof ViewableData) {
            $title = $record->obj($this->labelField)->forTemplate();
        } elseif ($record) {
            $title = Convert::raw2xml($record->{$this->labelField});
        } else {
            $title = $emptyTitle;
        }

        // TODO Implement for TreeMultiSelectField
        $metadata = array(
            'id' => $record ? $record->ID : null,
            'ClassName' => $record ? $record->ClassName : $this->sourceObject
        );

        $properties = array_merge(
            $properties,
            array(
                'Title' => $title,
                'EmptyTitle' => $emptyTitle,
                'Metadata' => ($metadata) ? Convert::raw2json($metadata) : null,
            )
        );

        return parent::Field($properties);
    }

    public function extraClass()
    {
        return implode(' ', array(parent::extraClass(), ($this->showSearch ? "searchable" : null)));
    }

    /**
     * Get the whole tree of a part of the tree via an AJAX request.
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     * @throws Exception
     */
    public function tree(HTTPRequest $request)
    {
        // Regular source specification
        $isSubTree = false;

        $this->search = $request->requestVar('search');
        $id = (is_numeric($request->latestParam('ID')))
            ? (int)$request->latestParam('ID')
            : (int)$request->requestVar('ID');

        /** @var DataObject|Hierarchy $obj */
        $obj = null;
        if ($id && !$request->requestVar('forceFullTree')) {
            $obj = DataObject::get_by_id($this->sourceObject, $id);
            $isSubTree = true;
            if (!$obj) {
                throw new Exception(
                    "TreeDropdownField->tree(): the object #$id of type $this->sourceObject could not be found"
                );
            }
        } else {
            if ($this->baseID) {
                $obj = DataObject::get_by_id($this->sourceObject, $this->baseID);
            }

            if (!$this->baseID || !$obj) {
                $obj = DataObject::singleton($this->sourceObject);
            }
        }

        // pre-process the tree - search needs to operate globally, not locally as marking filter does
        if ($this->search) {
            $this->populateIDs();
        }

        // Create marking set
        $markingSet = MarkedSet::create($obj, $this->childrenMethod, $this->numChildrenMethod, 30);

        // Set filter on searched nodes
        if ($this->filterCallback || $this->search) {
            // Rely on filtering to limit tree
            $markingSet->setMarkingFilterFunction(function ($node) {
                return $this->filterMarking($node);
            });
            $markingSet->setLimitingEnabled(false);
        }

        // Begin marking
        $markingSet->markPartialTree();

        // Allow to pass values to be selected within the ajax request
        $value = $request->requestVar('forceValue') ?: $this->value;
        if ($value && ($values = preg_split('/,\s*/', $value))) {
            foreach ($values as $value) {
                if (!$value || $value == 'unchanged') {
                    continue;
                }

                $markingSet->markToExpose($this->objectForKey($value));
            }
        }

        // Set title formatter
        $customised = function (DataObject $child) use ($isSubTree) {
            return [
                'name' => $this->getName(),
                'id' => $child->obj($this->keyField),
                'title' => $child->obj($this->labelField),
                'disabled' => $this->nodeIsDisabled($child),
                'isSubTree' => $isSubTree
            ];
        };

        // Determine output format
        if ($request->requestVar('format') === 'json') {
            // Format JSON output
            $json = $markingSet
                ->getChildrenAsArray($customised);
            return HTTPResponse::create()
                ->addHeader('Content-Type', 'application/json')
                ->setBody(json_encode($json));
        } else {
            // Return basic html
            $html = $markingSet->renderChildren(
                [self::class . '_HTML', 'type' => 'Includes'],
                $customised
            );
            return HTTPResponse::create()
                ->addHeader('Content-Type', 'text/html')
                ->setBody($html);
        }
    }

    /**
     * Marking public function for the tree, which combines different filters sensibly.
     * If a filter function has been set, that will be called. And if search text is set,
     * filter on that too. Return true if all applicable conditions are true, false otherwise.
     *
     * @param DataObject $node
     * @return bool
     */
    public function filterMarking($node)
    {
        if ($this->filterCallback && !call_user_func($this->filterCallback, $node)) {
            return false;
        }

        if ($this->search) {
            return isset($this->searchIds[$node->ID]) && $this->searchIds[$node->ID] ? true : false;
        }

        return true;
    }

    /**
     * Marking a specific node in the tree as disabled
     * @param $node
     * @return boolean
     */
    public function nodeIsDisabled($node)
    {
        return ($this->disableCallback && call_user_func($this->disableCallback, $node));
    }

    /**
     * @param string $field
     * @return $this
     */
    public function setLabelField($field)
    {
        $this->labelField = $field;
        return $this;
    }

    /**
     * @return String
     */
    public function getLabelField()
    {
        return $this->labelField;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function setKeyField($field)
    {
        $this->keyField = $field;
        return $this;
    }

    /**
     * @return String
     */
    public function getKeyField()
    {
        return $this->keyField;
    }

    /**
     * @param string $class
     * @return $this
     */
    public function setSourceObject($class)
    {
        $this->sourceObject = $class;
        return $this;
    }

    /**
     * @return String
     */
    public function getSourceObject()
    {
        return $this->sourceObject;
    }

    /**
     * Populate $this->searchIds with the IDs of the pages matching the searched parameter and their parents.
     * Reverse-constructs the tree starting from the leaves. Initially taken from CMSSiteTreeFilter, but modified
     * with pluggable search function.
     */
    protected function populateIDs()
    {
        // get all the leaves to be displayed
        if ($this->searchCallback) {
            $res = call_user_func($this->searchCallback, $this->sourceObject, $this->labelField, $this->search);
        } else {
            $sourceObject = $this->sourceObject;
            $filters = array();
            if (singleton($sourceObject)->hasDatabaseField($this->labelField)) {
                $filters["{$this->labelField}:PartialMatch"]  = $this->search;
            } else {
                if (singleton($sourceObject)->hasDatabaseField('Title')) {
                    $filters["Title:PartialMatch"] = $this->search;
                }
                if (singleton($sourceObject)->hasDatabaseField('Name')) {
                    $filters["Name:PartialMatch"] = $this->search;
                }
            }

            if (empty($filters)) {
                throw new InvalidArgumentException(sprintf(
                    'Cannot query by %s.%s, not a valid database column',
                    $sourceObject,
                    $this->labelField
                ));
            }
            $res = DataObject::get($this->sourceObject)->filterAny($filters);
        }

        if ($res) {
            // iteratively fetch the parents in bulk, until all the leaves can be accessed using the tree control
            foreach ($res as $row) {
                if ($row->ParentID) {
                    $parents[$row->ParentID] = true;
                }
                $this->searchIds[$row->ID] = true;
            }

            $sourceObject = $this->sourceObject;

            while (!empty($parents)) {
                $items = DataObject::get($sourceObject)
                    ->filter("ID", array_keys($parents));
                $parents = array();

                foreach ($items as $item) {
                    if ($item->ParentID) {
                        $parents[$item->ParentID] = true;
                    }
                    $this->searchIds[$item->ID] = true;
                    $this->searchExpanded[$item->ID] = true;
                }
            }
        }
    }

    /**
     * Get the object where the $keyField is equal to a certain value
     *
     * @param string|int $key
     * @return DataObject
     */
    protected function objectForKey($key)
    {
        return DataObject::get($this->sourceObject)
            ->filter($this->keyField, $key)
            ->first();
    }

    /**
     * Changes this field to the readonly field.
     */
    public function performReadonlyTransformation()
    {
        /** @var TreeDropdownField_Readonly $copy */
        $copy = $this->castedCopy(TreeDropdownField_Readonly::class);
        $copy->setKeyField($this->keyField);
        $copy->setLabelField($this->labelField);
        $copy->setSourceObject($this->sourceObject);
        return $copy;
    }
}
