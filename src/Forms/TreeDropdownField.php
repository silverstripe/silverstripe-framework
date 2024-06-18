<?php

namespace SilverStripe\Forms;

use Exception;
use InvalidArgumentException;
use SilverStripe\Assets\Folder;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\ORM\Hierarchy\MarkedSet;

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
 * use SilverStripe\CMS\Model\SiteTree;
 * ...
 * static $has_one = array(
 *   'RightContent' => SiteTree::class
 * );
 *
 * function getCMSFields() {
 * ...
 * $treedropdownfield = new TreeDropdownField("RightContentID", "Choose a page to show on the right:", SiteTree::class);
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
class TreeDropdownField extends FormField implements HasOneRelationFieldInterface
{
    protected $schemaDataType = TreeDropdownField::SCHEMA_DATA_TYPE_SINGLESELECT;

    protected $schemaComponent = 'TreeDropdownField';

    private static $search_filter = 'PartialMatch';

    private static $url_handlers = [
        '$Action!/$ID' => '$Action'
    ];

    private static $allowed_actions = [
        'tree'
    ];

    /**
     * @config
     * @var int
     * @see {@link Hierarchy::$node_threshold_total}.
     */
    private static $node_threshold_total = 30;

    /**
     * @var string
     */
    protected $emptyString = null;

    /**
     * @var bool
     */
    protected $hasEmptyDefault = false;

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
     * Name of label field on underlying object
     *
     * @var string
     */
    protected $labelField = null;

    /**
     * Similar to labelField but for non-html equivalent of field
     *
     * @var string
     */
    protected $titleField = 'Title';

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
     * This includes parents of search result children which may not be an actual result
     *
     * @var array
     */
    protected $searchIds = [];

    /**
     * List of ids which matches the search result
     * This excludes parents of search result children
     *
     * @var array
     */
    protected $realSearchIds = [];

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
     * Show full path for selected options, only applies for single select
     * @var bool
     */
    protected $showSelectedPath = false;

    /**
     * @var array
     */
    protected static $cacheKeyCache = [];

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
        $this->setSourceObject($sourceObject);
        $this->setKeyField($keyField);
        $this->setLabelField($labelField);
        $this->setShowSearch($showSearch);

        // Extra settings for Folders
        if (strcasecmp($sourceObject ?? '', Folder::class) === 0) {
            $this->setChildrenMethod('ChildFolders');
            $this->setNumChildrenMethod('numChildFolders');
        }

        $this->addExtraClass('single');

        // Set a default value of 0 instead of null
        // Because TreedropdownField requires SourceObject to have the Hierarchy extension, make the default
        // value the same as the default value for a RelationID, which is 0.
        $value = 0;

        parent::__construct($name, $title, $value);
    }

    /**
     * Set the ID of the root node of the tree. This defaults to 0 - i.e.
     * displays the whole tree.
     *
     * @return int
     */
    public function getTreeBaseID()
    {
        return $this->baseID;
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
     * Get a callback used to filter the values of the tree before
     * displaying to the user.
     *
     * @return callable
     */
    public function getFilterFunction()
    {
        return $this->filterCallback;
    }

    /**
     * Set a callback used to filter the values of the tree before
     * displaying to the user.
     *
     * @param callable $callback
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
     * Get the callback used to disable checkboxes for some items in the tree
     *
     * @return callable
     */
    public function getDisableFunction()
    {
        return $this->disableCallback;
    }

    /**
     * Set a callback used to disable checkboxes for some items in the tree
     *
     * @param callable $callback
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
     * @return callable
     */
    public function getSearchFunction()
    {
        return $this->searchCallback;
    }

    /**
     * Set a callback used to search the hierarchy globally, even before
     * applying the filter.
     *
     * @param callable $callback
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
     * Get method to invoke on each node to get the child collection
     *
     * @return string
     */
    public function getChildrenMethod()
    {
        return $this->childrenMethod;
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
     * Get method to invoke on nodes to count children
     *
     * @return string
     */
    public function getNumChildrenMethod()
    {
        return $this->numChildrenMethod;
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

    public function extraClass()
    {
        return implode(' ', [parent::extraClass(), ($this->getShowSearch() ? "searchable" : null)]);
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

        // pre-process the tree - search needs to operate globally, not locally as marking filter does
        if ($this->search) {
            $this->populateIDs();
        }

        $obj = null;
        $sourceObject = $this->getSourceObject();

        // Precache numChildren count if possible.
        if ($this->getNumChildrenMethod() == 'numChildren') {
            // We're not calling `Hierarchy::prepopulateTreeDataCache()` because we're not customising results based
            // on version or Fluent locales. So there would be no performance gain from additional caching.
            Hierarchy::prepopulate_numchildren_cache($sourceObject);
        }

        if ($id && !$request->requestVar('forceFullTree')) {
            $obj = DataObject::get_by_id($sourceObject, $id);
            $isSubTree = true;
            if (!$obj) {
                throw new Exception(
                    "TreeDropdownField->tree(): the object #$id of type $sourceObject could not be found"
                );
            }
        } else {
            if ($this->getTreeBaseID()) {
                $obj = DataObject::get_by_id($sourceObject, $this->getTreeBaseID());
            }

            if (!$this->getTreeBaseID() || !$obj) {
                $obj = DataObject::singleton($sourceObject);
            }
        }

        // Create marking set
        $markingSet = MarkedSet::create(
            $obj,
            $this->getChildrenMethod(),
            $this->getNumChildrenMethod(),
            $this->config()->get('node_threshold_total')
        );

        // Set filter on searched nodes
        if ($this->getFilterFunction() || $this->search) {
            // Rely on filtering to limit tree
            $markingSet->setMarkingFilterFunction(function ($node) {
                return $this->filterMarking($node);
            });
            $markingSet->setLimitingEnabled(false);
        }

        // Begin marking
        $markingSet->markPartialTree();

        // Explicitly mark our search results if necessary
        foreach ($this->searchIds as $id => $marked) {
            if ($marked) {
                $object = $this->objectForKey($id);
                if (!$object) {
                    continue;
                }
                $markingSet->markToExpose($object);
            }
        }

        // Allow to pass values to be selected within the ajax request
        $value = $request->requestVar('forceValue') ?: $this->value;
        if ($value && ($values = preg_split('/,\s*/', $value ?? ''))) {
            foreach ($values as $value) {
                if (!$value || $value == 'unchanged') {
                    continue;
                }

                $object = $this->objectForKey($value);
                if (!$object) {
                    continue;
                }
                $markingSet->markToExpose($object);
            }
        }

        // Set title formatter
        $customised = function (DataObject $child) use ($isSubTree) {
            return [
                'name' => $this->getName(),
                'id' => $child->obj($this->getKeyField()),
                'title' => $child->obj($this->getTitleField()),
                'treetitle' => $child->obj($this->getLabelField()),
                'disabled' => $this->nodeIsDisabled($child),
                'isSubTree' => $isSubTree
            ];
        };

        // Determine output format
        if ($request->requestVar('format') === 'json') {
            // Format JSON output
            $json = $markingSet
                ->getChildrenAsArray($customised);

            if ($request->requestVar('flatList')) {
                // format and filter $json here
                $json['children'] = $this->flattenChildrenArray($json['children']);
            }
            return HTTPResponse::create()
                ->addHeader('Content-Type', 'application/json')
                ->setBody(json_encode($json));
        } else {
            // Return basic html
            $html = $markingSet->renderChildren(
                [TreeDropdownField::class . '_HTML', 'type' => 'Includes'],
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
        $callback = $this->getFilterFunction();
        if ($callback && !call_user_func($callback, $node)) {
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
        $callback = $this->getDisableFunction();
        return $callback && call_user_func($callback, $node);
    }

    /**
     * Attributes to be given for this field type
     * @return array
     */
    public function getAttributes()
    {
        $attributes = [
            'class' => $this->extraClass(),
            'id' => $this->ID(),
            'data-schema' => json_encode($this->getSchemaData()),
            'data-state' => json_encode($this->getSchemaState()),
        ];

        $attributes = array_merge($attributes, $this->attributes);

        $this->extend('updateAttributes', $attributes);

        return $attributes;
    }

    /**
     * HTML-encoded label for this node, including css classes and other markup.
     *
     * @param string $field
     * @return $this
     */
    public function setLabelField($field)
    {
        $this->labelField = $field;
        return $this;
    }

    /**
     * HTML-encoded label for this node, including css classes and other markup.
     *
     * @return string
     */
    public function getLabelField()
    {
        return $this->labelField;
    }

    /**
     * Field to use for plain text item titles.
     *
     * @return string
     */
    public function getTitleField()
    {
        return $this->titleField;
    }

    /**
     * Set field to use for item title
     *
     * @param string $field
     * @return $this
     */
    public function setTitleField($field)
    {
        $this->titleField = $field;
        return $this;
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
     * @return string
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
     * Get class of source object
     *
     * @return string
     */
    public function getSourceObject()
    {
        return $this->sourceObject;
    }

    /**
     * Flattens a given list of children array items, so the data is no longer
     * structured in a hierarchy
     *
     * NOTE: uses {@link TreeDropdownField::$realSearchIds} to filter items by if there is a search
     *
     * @param array $children - the list of children, which could contain their own children
     * @param array $parentTitles - a list of parent titles, which we use to construct the contextString
     * @return array - flattened list of children
     */
    protected function flattenChildrenArray($children, $parentTitles = [])
    {
        $output = [];

        foreach ($children as $child) {
            $childTitles = array_merge($parentTitles, [$child['title']]);
            $grandChildren = $child['children'];
            $contextString = implode('/', $parentTitles);

            $child['contextString'] = ($contextString !== '') ? $contextString . '/' : '';
            unset($child['children']);

            if (!$this->search || in_array($child['id'], $this->realSearchIds ?? [])) {
                $output[] = $child;
            }
            $output = array_merge($output, $this->flattenChildrenArray($grandChildren, $childTitles));
        }

        return $output;
    }

    /**
     * Populate $this->searchIds with the IDs of the pages matching the searched parameter and their parents.
     * Reverse-constructs the tree starting from the leaves. Initially taken from CMSSiteTreeFilter, but modified
     * with pluggable search function.
     */
    protected function populateIDs()
    {
        // get all the leaves to be displayed
        $res = $this->getSearchResults();

        if (!$res) {
            return;
        }

        // iteratively fetch the parents in bulk, until all the leaves can be accessed using the tree control
        foreach ($res as $row) {
            if ($row->ParentID) {
                $parents[$row->ParentID] = true;
            }
            $this->searchIds[$row->ID] = true;
        }
        $this->realSearchIds = $res->column();

        $sourceObject = $this->getSourceObject();

        while (!empty($parents)) {
            $items = DataObject::get($sourceObject)
                ->filter("ID", array_keys($parents ?? []));
            $parents = [];

            foreach ($items as $item) {
                if ($item->ParentID) {
                    $parents[$item->ParentID] = true;
                }
                $this->searchIds[$item->ID] = true;
                $this->searchExpanded[$item->ID] = true;
            }
        }
    }

    /**
     * Get the DataObjects that matches the searched parameter.
     *
     * @return DataList
     */
    protected function getSearchResults()
    {
        $callback = $this->getSearchFunction();
        if ($callback) {
            return call_user_func($callback, $this->getSourceObject(), $this->getLabelField(), $this->search);
        }

        $sourceObject = $this->getSourceObject();
        $filters = [];
        $sourceObjectInstance = DataObject::singleton($sourceObject);
        $candidates = array_unique([
            $this->getLabelField(),
            $this->getTitleField(),
            'Title',
            'Name'
        ]);

        $searchFilter = $this->config()->get('search_filter') ?? 'PartialMatch';
        foreach ($candidates as $candidate) {
            if ($sourceObjectInstance->hasDatabaseField($candidate)) {
                $filters["{$candidate}:{$searchFilter}"] = $this->search;
            }
        }

        if (empty($filters)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot query by %s.%s, not a valid database column',
                $sourceObject,
                $this->getTitleField()
            ));
        }
        return DataObject::get($this->getSourceObject())->filterAny($filters);
    }

    /**
     * Get the object where the $keyField is equal to a certain value
     *
     * @param string|int $key
     * @return DataObject|null
     */
    protected function objectForKey($key)
    {
        if (!is_string($key) && !is_int($key)) {
            return null;
        }
        return DataObject::get($this->getSourceObject())
            ->filter($this->getKeyField(), $key)
            ->first();
    }

    /**
     * Changes this field to the readonly field.
     */
    public function performReadonlyTransformation()
    {
        $copy = $this->castedCopy(TreeDropdownField_Readonly::class);
        $copy->setKeyField($this->getKeyField());
        $copy->setLabelField($this->getLabelField());
        $this->setTitleField($this->getTitleField());
        $copy->setSourceObject($this->getSourceObject());
        return $copy;
    }

    public function castedCopy($classOrCopy)
    {
        $field = $classOrCopy;

        if (!is_object($field)) {
            $field = new $classOrCopy($this->name, $this->title, $this->getSourceObject());
        }

        return parent::castedCopy($field);
    }

    public function getSchemaStateDefaults()
    {
        $data = parent::getSchemaStateDefaults();
        /** @var Hierarchy|DataObject $record */
        $record = $this->Value() ? $this->objectForKey($this->Value()) : null;

        $data['data']['cacheKey'] = $this->getCacheKey();
        $data['data']['showSelectedPath'] = $this->getShowSelectedPath();
        if ($record) {
            $titlePath = '';

            if ($this->getShowSelectedPath()) {
                $ancestors = $record->getAncestors(true)->reverse();

                foreach ($ancestors as $parent) {
                    $title = $parent->obj($this->getTitleField())->getValue();
                    $titlePath .= $title . '/';
                }
            }
            $data['data']['valueObject'] = [
                'id' => $record->obj($this->getKeyField())->getValue(),
                'title' => $record->obj($this->getTitleField())->getValue(),
                'treetitle' => $record->obj($this->getLabelField())->getSchemaValue(),
                'titlePath' => $titlePath,
            ];
        }

        return $data;
    }

    /**
     * Ensure cache is keyed by last modified datetime of the underlying list.
     * Caches the key for the respective underlying list types, since it doesn't need to query again.
     *
     * @return DBDatetime
     */
    protected function getCacheKey()
    {
        $target = $this->getSourceObject();
        if (!isset(TreeDropdownField::$cacheKeyCache[$target])) {
            TreeDropdownField::$cacheKeyCache[$target] = DataList::create($target)->max('LastEdited');
        }
        return TreeDropdownField::$cacheKeyCache[$target];
    }

    public function getSchemaDataDefaults()
    {
        $data = parent::getSchemaDataDefaults();
        $data['data'] = array_merge($data['data'], [
            'urlTree' => $this->Link('tree'),
            'showSearch' => $this->getShowSearch(),
            'treeBaseId' => $this->getTreeBaseID(),
            'emptyString' => $this->getEmptyString(),
            'hasEmptyDefault' => $this->getHasEmptyDefault(),
            'multiple' => false,
        ]);

        return $data;
    }

    /**
     * @param boolean $bool
     * @return TreeDropdownField Self reference
     */
    public function setHasEmptyDefault($bool)
    {
        $this->hasEmptyDefault = $bool;
        return $this;
    }

    /**
     * @return bool
     */
    public function getHasEmptyDefault()
    {
        return $this->hasEmptyDefault;
    }

    /**
     * Set the default selection label, e.g. "select...".
     * Defaults to an empty string. Automatically sets
     * {@link $hasEmptyDefault} to true.
     *
     * @param string $string
     * @return $this
     */
    public function setEmptyString($string)
    {
        $this->setHasEmptyDefault(true);
        $this->emptyString = $string;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmptyString()
    {
        if ($this->emptyString !== null) {
            return $this->emptyString;
        }

        $item = DataObject::singleton($this->getSourceObject());
        $emptyString = _t(
            'SilverStripe\\Forms\\DropdownField.SEARCH_OR_CHOOSE_MODEL',
            '(Search or choose {name})',
            ['name' => $item->i18n_singular_name()]
        );
        return $emptyString;
    }

    /**
     * @return bool
     */
    public function getShowSelectedPath()
    {
        return $this->showSelectedPath;
    }

    /**
     * @param bool $showSelectedPath
     * @return TreeDropdownField
     */
    public function setShowSelectedPath($showSelectedPath)
    {
        $this->showSelectedPath = $showSelectedPath;
        return $this;
    }

    /**
     * @return array
     */
    public function getSchemaValidation()
    {
        $validationList = parent::getSchemaValidation();
        if (array_key_exists('required', $validationList)) {
            $validationList['required'] = ['extraEmptyValues' => ['0']];
        }
        return $validationList;
    }
}
