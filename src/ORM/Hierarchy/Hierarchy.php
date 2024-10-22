<?php

namespace SilverStripe\ORM\Hierarchy;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataList;
use SilverStripe\Model\List\SS_List;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use Exception;
use SilverStripe\Model\ModelData;
use SilverStripe\ORM\HiddenClass;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

/**
 * DataObjects that use the Hierarchy extension can be be organised as a hierarchy, with children and parents. The most
 * obvious example of this is SiteTree.
 *
 * @property int $ParentID
 * @method DataObject Parent()
 * @extends Extension<DataObject&static>
 */
class Hierarchy extends Extension
{
    /**
     * The name of the dedicated sort field, if there is one.
     * Will be null if there's no field for sorting this model.
     * Does not affect default_sort which needs to be configured separately.
     */
    private static ?string $sort_field = null;

    /**
     * The default child class for this model.
     * Note that this is intended for use with CMSMain and may not be respected with other model management methods.
     */
    private static ?string $default_child = null;

    /**
     * The default parent class for this model.
     * Note that this is intended for use with CMSMain and may not be respected with other model management methods.
     */
    private static ?string $default_parent = null;

    /**
     * Indicates what kind of children this model can have.
     * This can be an array of allowed child classes, or the string "none" -
     * indicating that this model can't have children.
     * If a classname is prefixed by "*", such as "*App\Model\MyModel", then only that
     * class is allowed - no subclasses. Otherwise, the class and all its
     * subclasses are allowed.
     * To control allowed children on root level (no parent), use {@link $can_be_root}.
     *
     * Leaving this array empty means this model can have children of any class that is a subclass
     * of the first class in its class hierarchy to have the Hierarchy extension, including records of the same class.
     *
     * Note that this is intended for use with CMSMain and may not be respected with other model management methods.
     */
    private static array $allowed_children = [];

    /**
     * Controls whether a record can be in the root of the hierarchy.
     * Note that this is intended for use with CMSMain and may not be respected with other model management methods.
     */
    private static bool $can_be_root = true;

    /**
     * The lower bounds for the amount of nodes to mark. If set, the logic will expand nodes until it reaches at least
     * this number, and then stops. Root nodes will always show regardless of this setting. Further nodes can be
     * lazy-loaded via ajax. This isn't a hard limit. Example: On a value of 10, with 20 root nodes, each having 30
     * children, the actual node count will be 50 (all root nodes plus first expanded child).
     *
     * @config
     * @var int
     */
    private static $node_threshold_total = 50;

    /**
     * Limit on the maximum children a specific node can display. Serves as a hard limit to avoid exceeding available
     * server resources in generating the tree, and browser resources in rendering it. Nodes with children exceeding
     * this value typically won't display any children, although this is configurable through the $nodeCountCallback
     * parameter in {@link getChildrenAsUL()}. "Root" nodes will always show all children, regardless of this setting.
     *
     * @config
     * @var int
     */
    private static $node_threshold_leaf = 250;

    /**
     * A list of classnames to exclude from display in both the CMS and front end
     * displays. ->Children() and ->AllChildren affected.
     * Especially useful for big sets of pages like listings
     * If you use this, and still need the classes to be editable
     * then add a model admin for the class
     * Note: Does not filter subclasses (non-inheriting)
     *
     * @var array
     * @config
     */
    private static $hide_from_hierarchy = [];

    /**
     * A list of classnames to exclude from display in the page tree views of the CMS,
     * unlike $hide_from_hierarchy above which effects both CMS and front end.
     * Especially useful for big sets of pages like listings
     * If you use this, and still need the classes to be editable
     * then add a model admin for the class
     * Note: Does not filter subclasses (non-inheriting)
     *
     * @var array
     * @config
     */
    private static $hide_from_cms_tree = [];

    /**
     * Used to enable or disable the prepopulation of the numchildren cache.
     * Defaults to true.
     *
     * @config
     * @var boolean
     */
    private static $prepopulate_numchildren_cache = true;

    /**
     * Prevent virtual page virtualising these fields
     *
     * @config
     * @var array
     */
    private static $non_virtual_fields = [
        '_cache_children',
    ];

    /**
     * A cache used by numChildren().
     * Clear through {@link flushCache()}.
     * version (int)0 means not on this stage.
     */
    protected static array $cache_numChildren = [];

    /**
     * Used as a cache for allowedChildren()
     * Drastically reduces admin page load when there are a lot of subclass types
     */
    protected static array $cache_allowedChildren = [];

    public static function get_extra_config($class, $extension, $args)
    {
        return [
            'has_one' => ['Parent' => $class]
        ];
    }

    /**
     * Validate the owner object - check for existence of infinite loops.
     */
    protected function updateValidate(ValidationResult $validationResult)
    {
        $owner = $this->owner;
        $this->validateNonCyclicalHierarchy($validationResult);

        // "Can be root" validation
        if (!$owner::config()->get('can_be_root') && !$owner->ParentID) {
            $validationResult->addError(
                _t(
                    __CLASS__ . '.TypeOnRootNotAllowed',
                    'Model type "{type}" is not allowed on the root level',
                    ['type' => $owner->i18n_singular_name()]
                ),
                ValidationResult::TYPE_ERROR,
                'CAN_BE_ROOT'
            );
        }

        // Allowed children validation
        $parent = $owner->getParent();
        if ($parent && $parent->exists()) {
            // No need to check for subclasses or instanceof, as allowedChildren() already
            // deconstructs any inheritance trees already.
            $allowed = $parent->allowedChildren();
            $subject = $owner->hasMethod('getRecordForAllowedChildrenValidation')
                ? $owner->getRecordForAllowedChildrenValidation()
                : $owner;
            if (!in_array($subject->ClassName, $allowed ?? [])) {
                $validationResult->addError(
                    _t(
                        __CLASS__ . '.ChildTypeNotAllowed',
                        'Model type "{type}" not allowed as child of this parent record',
                        ['type' => $subject->i18n_singular_name()]
                    ),
                    ValidationResult::TYPE_ERROR,
                    'ALLOWED_CHILDREN'
                );
            }
        }
    }

    private function validateNonCyclicalHierarchy(ValidationResult $validationResult): void
    {
        $owner = $this->owner;
        // The object is new, won't be looping.
        if (!$owner->ID) {
            return;
        }
        // The object has no parent, won't be looping.
        if (!$owner->ParentID) {
            return;
        }
        // The parent has not changed, skip the checks for performance reasons.
        if (!$owner->isChanged('ParentID')) {
            return;
        }

        // Walk the hierarchy upwards until we reach the top, or until we reach the originating node again.
        $node = $owner;
        while ($node && $node->ParentID) {
            if ((int)$node->ParentID === (int)$owner->ID) {
                // Hierarchy is looping.
                $validationResult->addError(
                    _t(
                        __CLASS__ . '.InfiniteLoopNotAllowed',
                        'Infinite loop found within the "{type}" hierarchy. Please change the parent to resolve this',
                        'First argument is the class that makes up the hierarchy.',
                        ['type' => get_class($owner)]
                    ),
                    'bad',
                    'INFINITE_LOOP'
                );
                break;
            }
            $node = $node->Parent();
        }
    }

    /**
     * Get a list of this DataObject's and all it's descendants IDs.
     *
     * @return int[]
     */
    public function getDescendantIDList()
    {
        $idList = [];
        $this->loadDescendantIDListInto($idList);
        return $idList;
    }

    /**
     * Get a list of this DataObject's and all it's descendants ID, and put them in $idList.
     *
     * @param array $idList Array to put results in.
     * @param DataObject|Hierarchy $node
     */
    protected function loadDescendantIDListInto(&$idList, $node = null)
    {
        if (!$node) {
            $node = $this->owner;
        }
        $children = $node->AllChildren();
        foreach ($children as $child) {
            if (!in_array($child->ID, $idList ?? [])) {
                $idList[] = $child->ID;
                $this->loadDescendantIDListInto($idList, $child);
            }
        }
    }

    /**
     * Duplicates each child of this record recursively and returns the top-level duplicate record.
     * If there is a sort field, new sort values are set for the duplicates to retain their sort order.
     */
    public function duplicateWithChildren(): DataObject
    {
        $owner = $this->getOwner();
        $clone = $owner->duplicate();
        $children = $owner->AllChildren();
        $sortField = $owner->getSortField();

        $sort = 1;
        foreach ($children as $child) {
            $childClone = $child->duplicateWithChildren();
            $childClone->ParentID = $clone->ID;
            if ($sortField) {
                //retain sort order by manually setting sort values
                $childClone->$sortField = $sort;
                $sort++;
            }
            $childClone->write();
        }

        return $clone;
    }

    /**
     * Get the children for this DataObject filtered by canView()
     *
     * @return SS_List<DataObject&static>
     */
    public function Children()
    {
        $children = $this->owner->_cache_children;
        if ($children) {
            return $children;
        }

        $children = $this
            ->owner
            ->stageChildren(false)
            ->filterByCallback(function (DataObject $record) {
                return $record->canView();
            });
        $this->owner->_cache_children = $children;
        return $children;
    }

    /**
     * Return all children, including those 'not in menus'.
     *
     * @return DataList<DataObject&static>
     */
    public function AllChildren()
    {
        return $this->owner->stageChildren(true);
    }

    /**
     * Return all children, including those that have been deleted but are still in live.
     * - Deleted children will be marked as "DeletedFromStage"
     * - Added children will be marked as "AddedToStage"
     * - Modified children will be marked as "ModifiedOnStage"
     * - Everything else has "SameOnStage" set, as an indicator that this information has been looked up.
     *
     * @return ArrayList<DataObject&static>
     */
    public function AllChildrenIncludingDeleted()
    {
        /** @var DataObject|Hierarchy|Versioned $owner */
        $owner = $this->owner;
        $stageChildren = $owner->stageChildren(true);

        // Add live site content that doesn't exist on the stage site, if required.
        if ($owner->hasExtension(Versioned::class) && $owner->hasStages()) {
            // Next, go through the live children.  Only some of these will be listed
            $liveChildren = $owner->liveChildren(true, true);
            if ($liveChildren) {
                $merged = new ArrayList();
                $merged->merge($stageChildren);
                $merged->merge($liveChildren);
                $stageChildren = $merged;
            }
        }
        $owner->extend("augmentAllChildrenIncludingDeleted", $stageChildren);
        return $stageChildren;
    }

    /**
     * Return all the children that this page had, including pages that were deleted from both stage & live.
     *
     * @return DataList<DataObject&static>
     * @throws Exception
     */
    public function AllHistoricalChildren()
    {
        /** @var DataObject|Versioned|Hierarchy $owner */
        $owner = $this->owner;
        if (!$owner->hasExtension(Versioned::class) || !$owner->hasStages()) {
            throw new Exception(
                'Hierarchy->AllHistoricalChildren() only works with Versioned extension applied with staging'
            );
        }

        $baseTable = $owner->baseTable();
        $parentIDColumn = $owner->getSchema()->sqlColumnForField($owner, 'ParentID');
        return Versioned::get_including_deleted(
            $owner->baseClass(),
            [ $parentIDColumn => $owner->ID ],
            "\"{$baseTable}\".\"ID\" ASC"
        );
    }

    /**
     * Return the number of children that this page ever had, including pages that were deleted.
     *
     * @return int
     */
    public function numHistoricalChildren()
    {
        return $this->AllHistoricalChildren()->count();
    }

    /**
     * Return the number of direct children. By default, values are cached after the first invocation. Can be
     * augmented by {@link augmentNumChildrenCountQuery()}.
     *
     * @param bool $cache Whether to retrieve values from cache
     * @return int
     */
    public function numChildren($cache = true)
    {
        $baseClass = $this->owner->baseClass();
        $cacheType = 'numChildren';
        $id = $this->owner->ID;

        // cached call
        if ($cache) {
            if (isset(Hierarchy::$cache_numChildren[$baseClass][$cacheType][$id])) {
                return Hierarchy::$cache_numChildren[$baseClass][$cacheType][$id];
            } elseif (isset(Hierarchy::$cache_numChildren[$baseClass][$cacheType]['_complete'])) {
                // If the cache is complete and we didn't find our ID in the cache, it means this object is childless.
                return 0;
            }
        }

        // We call stageChildren(), because Children() has canView() filtering
        $numChildren = (int)$this->owner->stageChildren(true)->Count();

        // Save if caching
        if ($cache) {
            Hierarchy::$cache_numChildren[$baseClass][$cacheType][$id] = $numChildren;
        }

        return $numChildren;
    }

    /**
     * Pre-populate any appropriate caches prior to rendering a tree.
     * This is used to allow for the efficient rendering of tree views, notably in the CMS.
     * In the case of Hierarchy, it caches numChildren values. Other extensions can provide an
     * onPrepopulateTreeDataCache(DataList $recordList = null, array $options) methods to hook
     * into this event as well.
     *
     * @param DataList|array $recordList The list of records to prepopulate caches for. Null for all records.
     * @param array $options A map of hints about what should be cached. "numChildrenMethod" and
     *                       "childrenMethod" are allowed keys.
     */
    public function prepopulateTreeDataCache($recordList = null, array $options = [])
    {
        if (empty($options['numChildrenMethod']) || $options['numChildrenMethod'] === 'numChildren') {
            $idList = is_array($recordList) ? $recordList :
                ($recordList instanceof DataList ? $recordList->column('ID') : null);
            Hierarchy::prepopulate_numchildren_cache($this->getHierarchyBaseClass(), $idList);
        }

        $this->owner->extend('onPrepopulateTreeDataCache', $recordList, $options);
    }

    /**
     * Pre-populate the cache for Versioned::get_versionnumber_by_stage() for
     * a list of record IDs, for more efficient database querying.  If $idList
     * is null, then every record will be pre-cached.
     *
     * @param string $baseClass
     * @param array $idList
     */
    public static function prepopulate_numchildren_cache($baseClass, $idList = null)
    {
        if (!Config::inst()->get(static::class, 'prepopulate_numchildren_cache')) {
            return;
        }

        /** @var DataObject&static $dummyObject */
        $dummyObject = DataObject::singleton($baseClass);
        $baseTable = $dummyObject->baseTable();

        $idColumn = Convert::symbol2sql("{$baseTable}.ID");

        // Get the stageChildren() result of a dummy object and break down into a generic query
        $query = $dummyObject->stageChildren(true, true)->dataQuery()->query();

        // optional ID-list filter
        if ($idList) {
            // Validate the ID list
            foreach ($idList as $id) {
                if (!is_numeric($id)) {
                    throw new \InvalidArgumentException(
                        "Bad ID passed to Versioned::prepopulate_numchildren_cache() in \$idList: " . $id
                    );
                }
            }
            $query->addWhere(['"ParentID" IN (' . DB::placeholders($idList) . ')' => $idList]);
        }

        $query->setOrderBy(null);

        $query->setSelect([
            '"ParentID"',
            "COUNT(DISTINCT $idColumn) AS \"NumChildren\"",
        ]);
        $query->setGroupBy([Convert::symbol2sql("ParentID")]);

        $numChildren = $query->execute()->map();
        Hierarchy::$cache_numChildren[$baseClass]['numChildren'] = $numChildren;
        if (!$idList) {
            // If all objects are being cached, mark this cache as complete
            // to avoid counting children of childless object.
            Hierarchy::$cache_numChildren[$baseClass]['numChildren']['_complete'] = true;
        }
    }

    /**
     * Returns the class name of the default class for children of this page.
     * Note that this is intended for use with CMSMain and may not be respected with other model management methods.
     */
    public function defaultChild(): ?string
    {
        $owner = $this->getOwner();
        $default = $owner::config()->get('default_child');
        $allowed = $this->allowedChildren();
        if (empty($allowed)) {
            return null;
        }
        if (!$default || !in_array($default, $allowed)) {
            $default = reset($allowed);
        }
        return $default;
    }

    /**
     * Returns the class name of the default class for the parent of this page.
     * Note that this is intended for use with CMSMain and may not be respected with other model management methods.
     * Doesn't check the allowedChildren config for the parent class.
     */
    public function defaultParent(): ?string
    {
        return $this->getOwner()::config()->get('default_parent');
    }

    /**
     * Returns an array of the class names of classes that are allowed to be children of this class.
     * Note that this is intended for use with CMSMain and may not be respected with other model management methods.
     *
     * @return string[]
     */
    public function allowedChildren(): array
    {
        $owner = $this->getOwner();
        if (isset(static::$cache_allowedChildren[$owner->ClassName])) {
            $allowedChildren = static::$cache_allowedChildren[$owner->ClassName];
        } else {
            // Get config from the highest class in the hierarchy to define it.
            // This avoids merged config, meaning each class that defines the allowed children defines it from scratch.
            $baseClass = $this->getHierarchyBaseClass();
            $class = get_class($owner);
            $candidates = null;
            while ($class) {
                if (Config::inst()->exists($class, 'allowed_children', Config::UNINHERITED)) {
                    $candidates = Config::inst()->get($class, 'allowed_children', Config::UNINHERITED);
                    break;
                }
                // Stop checking if we've hit the first class in the class hierarchy which has this extension
                if ($class === $baseClass) {
                    break;
                }
                $class = get_parent_class($class);
            }
            if ($candidates === 'none') {
                return [];
            }

            // If we're using a superclass, check if we've already processed its allowed children list
            if ($class !== $owner->ClassName && isset(static::$cache_allowedChildren[$class])) {
                $allowedChildren = static::$cache_allowedChildren[$class];
                static::$cache_allowedChildren[$owner->ClassName] = $allowedChildren;
                return $allowedChildren;
            }

            // Set the highest available class (and implicitly its subclasses) as being allowed.
            if (!$candidates) {
                $candidates = [$baseClass];
            }

            // Parse candidate list
            $allowedChildren = [];
            foreach ((array)$candidates as $candidate) {
                // If a classname is prefixed by "*", such as "*App\Model\MyModel", then only that class is allowed - no subclasses.
                // Otherwise, the class and all its subclasses are allowed.
                if (substr($candidate, 0, 1) == '*') {
                    $allowedChildren[] = substr($candidate, 1);
                } elseif ($subclasses = ClassInfo::subclassesFor($candidate)) {
                    foreach ($subclasses as $subclass) {
                        if (!is_a($subclass, HiddenClass::class, true)) {
                            $allowedChildren[] = $subclass;
                        }
                    }
                }
            }
            static::$cache_allowedChildren[$owner->ClassName] = $allowedChildren;
            // Make sure we don't have to re-process if this is the allowed children set of a superclass
            if ($class !== $owner->ClassName) {
                static::$cache_allowedChildren[$class] = $allowedChildren;
            }
        }
        $owner->extend('updateAllowedChildren', $allowedChildren);

        return $allowedChildren;
    }

    /**
     * Checks if we're on a controller where we should filter. ie. Are we loading the SiteTree?
     *
     * @return bool
     */
    public function showingCMSTree()
    {
        if (!Controller::has_curr() || !class_exists(LeftAndMain::class)) {
            return false;
        }
        $controller = Controller::curr();
        return $controller instanceof LeftAndMain
            && in_array($controller->getAction(), ["treeview", "listview", "getsubtree"]);
    }

    /**
     * Return the CSS classes to apply to this node in the CMS tree.
     */
    public function CMSTreeClasses(): string
    {
        $owner = $this->getOwner();
        $classes = sprintf('class-%s', Convert::raw2htmlid(get_class($owner)));

        if (!$owner->canAddChildren()) {
            $classes .= " nochildren";
        }

        if (!$owner->canEdit() && !$owner->canAddChildren()) {
            if (!$owner->canView()) {
                $classes .= " disabled";
            } else {
                $classes .= " edit-disabled";
            }
        }

        $owner->invokeWithExtensions('updateCMSTreeClasses', $classes);
        return $classes;
    }

    /**
     * Find the first class in the inheritance chain that has Hierarchy extension applied
     *
     * @return string
     */
    private function getHierarchyBaseClass(): string
    {
        $ancestry = ClassInfo::ancestry($this->owner);
        $ancestorClass = array_shift($ancestry);
        while ($ancestorClass && !ModelData::has_extension($ancestorClass, Hierarchy::class)) {
            $ancestorClass = array_shift($ancestry);
        }

        return $ancestorClass;
    }

    /**
     * Return children in the stage site.
     *
     * @param bool $showAll Include all of the elements, even those not shown in the menus. Only applicable when
     *                      extension is applied to {@link SiteTree}.
     * @param bool $skipParentIDFilter Set to true to suppress the ParentID and ID where statements.
     * @return DataList<DataObject&static>
     */
    public function stageChildren($showAll = false, $skipParentIDFilter = false)
    {
        $owner = $this->owner;
        $hideFromHierarchy = $owner->config()->hide_from_hierarchy;
        $hideFromCMSTree = $owner->config()->hide_from_cms_tree;
        $class = $this->getHierarchyBaseClass();

        $schema = DataObject::getSchema();
        $tableForParentID = $schema->tableForField($class, 'ParentID');
        $tableForID = $schema->tableForField($class, 'ID');

        $staged = DataObject::get($class)->where(sprintf(
            '%s.%s <> %s.%s',
            Convert::symbol2sql($tableForParentID),
            Convert::symbol2sql("ParentID"),
            Convert::symbol2sql($tableForID),
            Convert::symbol2sql("ID")
        ));

        if (!$skipParentIDFilter) {
            // There's no filtering by ID if we don't have an ID.
            $staged = $staged->filter('ParentID', (int)$this->owner->ID);
        }

        if ($hideFromHierarchy) {
            $staged = $staged->exclude('ClassName', $hideFromHierarchy);
        }
        if ($hideFromCMSTree && $this->showingCMSTree()) {
            $staged = $staged->exclude('ClassName', $hideFromCMSTree);
        }
        if (!$showAll && DataObject::getSchema()->fieldSpec($this->owner, 'ShowInMenus')) {
            $staged = $staged->filter('ShowInMenus', 1);
        }
        $this->owner->extend("augmentStageChildren", $staged, $showAll);
        return $staged;
    }

    /**
     * Return children in the live site, if it exists.
     *
     * @param bool $showAll              Include all of the elements, even those not shown in the menus. Only
     *                                   applicable when extension is applied to {@link SiteTree}.
     * @param bool $onlyDeletedFromStage Only return items that have been deleted from stage
     * @return DataList<DataObject&static>
     * @throws Exception
     */
    public function liveChildren($showAll = false, $onlyDeletedFromStage = false)
    {
        /** @var Versioned|DataObject|Hierarchy $owner */
        $owner = $this->owner;
        if (!$owner->hasExtension(Versioned::class) || !$owner->hasStages()) {
            throw new Exception('Hierarchy->liveChildren() only works with Versioned extension applied with staging');
        }

        $hideFromHierarchy = $owner->config()->hide_from_hierarchy;
        $hideFromCMSTree = $owner->config()->hide_from_cms_tree;
        $children = DataObject::get($this->getHierarchyBaseClass())
            ->filter('ParentID', (int)$owner->ID)
            ->exclude('ID', (int)$owner->ID)
            ->setDataQueryParam([
                'Versioned.mode' => $onlyDeletedFromStage ? 'stage_unique' : 'stage',
                'Versioned.stage' => 'Live'
            ]);
        if ($hideFromHierarchy) {
            $children = $children->exclude('ClassName', $hideFromHierarchy);
        }
        if ($hideFromCMSTree && $this->showingCMSTree()) {
            $children = $children->exclude('ClassName', $hideFromCMSTree);
        }
        if (!$showAll && DataObject::getSchema()->fieldSpec($owner, 'ShowInMenus')) {
            $children = $children->filter('ShowInMenus', 1);
        }

        return $children;
    }

    /**
     * Get this object's parent, optionally filtered by an SQL clause. If the clause doesn't match the parent, nothing
     * is returned.
     *
     * @param string $filter
     * @return DataObject&static
     */
    public function getParent($filter = null)
    {
        $parentID = $this->owner->ParentID;
        if (empty($parentID)) {
            return null;
        }
        $baseClass = $this->owner->baseClass();
        $idSQL = $this->owner->getSchema()->sqlColumnForField($baseClass, 'ID');
        return DataObject::get_one($baseClass, [
            [$idSQL => $parentID],
            $filter
        ]);
    }

    /**
     * Return all the parents of this class in a set ordered from the closest to furtherest parent.
     *
     * @param bool $includeSelf
     * @return ArrayList<DataObject&static>
     */
    public function getAncestors($includeSelf = false)
    {
        $ancestors = new ArrayList();
        $object = $this->owner;

        if ($includeSelf) {
            $ancestors->push($object);
        }
        while ($object = $object->getParent()) {
            $ancestors->push($object);
        }

        return $ancestors;
    }

    /**
     * Returns a human-readable, flattened representation of the path to the object, using its {@link Title} attribute.
     *
     * @param string $separator
     * @return string
     */
    public function getBreadcrumbs($separator = ' &raquo; ')
    {
        $crumbs = [];
        $ancestors = array_reverse($this->owner->getAncestors()->toArray() ?? []);
        /** @var DataObject $ancestor */
        foreach ($ancestors as $ancestor) {
            $crumbs[] = $ancestor->getTitle();
        }
        $crumbs[] = $this->owner->getTitle();
        return implode($separator ?? '', $crumbs);
    }

    /**
     * Get the title that will be used in TreeDropdownField and other tree structures.
     */
    public function getTreeTitle(): string
    {
        $owner = $this->getOwner();
        $title = $owner->MenuTitle ?: $owner->Title;
        $owner->extend('updateTreeTitle', $title);
        return Convert::raw2xml($title ?? '');
    }

    /**
     * Get the name of the dedicated sort field, if there is one.
     */
    public function getSortField(): ?string
    {
        return $this->getOwner()::config()->get('sort_field');
    }

    /**
     * Returns true if the current user can add children to this page.
     *
     * Denies permission if any of the following conditions is true:
     * - the record is versioned and archived
     * - canAddChildren() on a extension returns false
     * - canEdit() is not granted
     * - allowed_children is not set to "none"
     */
    public function canAddChildren(?Member $member = null): bool
    {
        $owner = $this->getOwner();
        // Disable adding children to archived records
        if ($owner->hasExtension(Versioned::class) && $owner->isArchived()) {
            return false;
        }

        if (!$member) {
            $member = Security::getCurrentUser();
        }

        // Standard mechanism for accepting permission changes from extensions
        $extended = $owner->extendedCan('canAddChildren', $member);
        if ($extended !== null) {
            return $extended;
        }

        return $owner->canEdit($member) && $owner::config()->get('allowed_children') !== 'none';
    }

    protected function extendCanAddChildren()
    {
        // Prevent canAddChildren from extending itself
        return null;
    }

    /**
     * Flush all Hierarchy caches:
     * - Children (instance)
     * - NumChildren (instance)
     */
    protected function onFlushCache()
    {
        $this->owner->_cache_children = null;
        Hierarchy::$cache_numChildren = [];
    }

    /**
     * Block creating children not allowed for the parent type
     */
    protected function canCreate(?Member $member, array $context): ?bool
    {
        // Parent is added to context through CMSMain
        // Note that not having a parent doesn't necessarily mean this record is being
        // created at the root, so we can't check against can_be_root here.
        $parent = isset($context['Parent']) ? $context['Parent'] : null;
        $parentInHierarchy = ($parent && is_a($parent, $this->getHierarchyBaseClass()));
        if ($parentInHierarchy && !in_array(get_class($this->getOwner()), $parent->allowedChildren())) {
            return false;
        }
        if ($parent?->exists() && $parentInHierarchy && !$parent->canAddChildren($member)) {
            return false;
        }
        return null;
    }
}
