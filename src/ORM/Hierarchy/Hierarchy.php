<?php

namespace SilverStripe\ORM\Hierarchy;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Versioned\Versioned;
use Exception;

/**
 * DataObjects that use the Hierarchy extension can be be organised as a hierarchy, with children and parents. The most
 * obvious example of this is SiteTree.
 *
 * @property int $ParentID
 * @property DataObject|Hierarchy $owner
 * @method DataObject Parent()
 */
class Hierarchy extends DataExtension
{
    /**
     * The lower bounds for the amount of nodes to mark. If set, the logic will expand nodes until it reaches at least
     * this number, and then stops. Root nodes will always show regardless of this settting. Further nodes can be
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
    private static $hide_from_hierarchy = array();

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
    private static $hide_from_cms_tree = array();

    /**
     * Prevent virtual page virtualising these fields
     *
     * @config
     * @var array
     */
    private static $non_virtual_fields = [
        '_cache_children',
        '_cache_numChildren',
    ];

    public static function get_extra_config($class, $extension, $args)
    {
        return array(
            'has_one' => array('Parent' => $class)
        );
    }

    /**
     * Validate the owner object - check for existence of infinite loops.
     *
     * @param ValidationResult $validationResult
     */
    public function validate(ValidationResult $validationResult)
    {
        // The object is new, won't be looping.
        /** @var DataObject|Hierarchy $owner */
        $owner = $this->owner;
        if (!$owner->ID) {
            return;
        }
        // The object has no parent, won't be looping.
        if (!$owner->ParentID) {
            return;
        }
        // The parent has not changed, skip the check for performance reasons.
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
                        array('type' => get_class($owner))
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
        $idList = array();
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
            if (!in_array($child->ID, $idList)) {
                $idList[] = $child->ID;
                $this->loadDescendantIDListInto($idList, $child);
            }
        }
    }

    /**
     * Get the children for this DataObject filtered by canView()
     *
     * @return SS_List
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
     * @return DataList
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
     * @return ArrayList
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
     * @return DataList
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
     * augumented by {@link augmentNumChildrenCountQuery()}.
     *
     * @param bool $cache Whether to retrieve values from cache
     * @return int
     */
    public function numChildren($cache = true)
    {
        // Load if caching
        if ($cache) {
            $numChildren = $this->owner->_cache_numChildren;
            if (isset($numChildren)) {
                return $numChildren;
            }
        }

        // We call stageChildren(), because Children() has canView() filtering
        $numChildren = (int)$this->owner->stageChildren(true)->Count();

        // Save if caching
        if ($cache) {
            $this->owner->_cache_numChildren = $numChildren;
        }
        return $numChildren;
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
            && in_array($controller->getAction(), array("treeview", "listview", "getsubtree"));
    }

    /**
     * Return children in the stage site.
     *
     * @param bool $showAll Include all of the elements, even those not shown in the menus. Only applicable when
     *                      extension is applied to {@link SiteTree}.
     * @return DataList
     */
    public function stageChildren($showAll = false)
    {
        $hideFromHierarchy = $this->owner->config()->hide_from_hierarchy;
        $hideFromCMSTree = $this->owner->config()->hide_from_cms_tree;
        $baseClass = $this->owner->baseClass();
        $staged = DataObject::get($baseClass)
                ->filter('ParentID', (int)$this->owner->ID)
                ->exclude('ID', (int)$this->owner->ID);
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
     * @return DataList
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
        $children = DataObject::get($owner->baseClass())
            ->filter('ParentID', (int)$owner->ID)
            ->exclude('ID', (int)$owner->ID)
            ->setDataQueryParam(array(
                'Versioned.mode' => $onlyDeletedFromStage ? 'stage_unique' : 'stage',
                'Versioned.stage' => 'Live'
            ));
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
     * @return DataObject
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
     * @return ArrayList
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
        $crumbs = array();
        $ancestors = array_reverse($this->owner->getAncestors()->toArray());
        /** @var DataObject $ancestor */
        foreach ($ancestors as $ancestor) {
            $crumbs[] = $ancestor->getTitle();
        }
        $crumbs[] = $this->owner->getTitle();
        return implode($separator, $crumbs);
    }

    /**
     * Flush all Hierarchy caches:
     * - Children (instance)
     * - NumChildren (instance)
     */
    public function flushCache()
    {
        $this->owner->_cache_children = null;
        $this->owner->_cache_numChildren = null;
    }
}
