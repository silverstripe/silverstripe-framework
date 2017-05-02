<?php

namespace SilverStripe\Security;

use InvalidArgumentException;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\Versioned\Versioned;

/**
 * Calculates batch permissions for nested objects for:
 *  - canView: Supports 'Anyone' type
 *  - canEdit
 *  - canDelete: Includes special logic for ensuring parent objects can only be deleted if their children can
 *    be deleted also.
 */
class InheritedPermissions
{
    use Injectable;

    const DELETE = 'delete';
    const VIEW = 'view';
    const EDIT = 'edit';

    /**
     * Anyone canView permission
     */
    const ANYONE = 'Anyone';

    /**
     * Restrict to logged in users
     */
    const LOGGED_IN_USERS = 'LoggedInUsers';

    /**
     * Restrict to specific groups
     */
    const ONLY_THESE_USERS = 'OnlyTheseUsers';

    /**
     * Inherit from parent
     */
    const INHERIT = 'Inherit';

    /**
     * Class name
     *
     * @var string
     */
    protected $baseClass = null;

    /**
     * Object for evaluating top level permissions designed as "Inherit"
     *
     * @var RootPermissions
     */
    protected $rootPermissions = null;

    /**
     * Global permissions required to edit.
     * If empty no global permissions are required
     *
     * @var array
     */
    protected $globalEditPermissions = [];

    /**
     * Cache of permissions
     *
     * @var array
     */
    protected $cachePermissions = [];

    /**
     * @param RootPermissions $callback
     * @return $this
     */
    public function setRootPermissions(RootPermissions $callback)
    {
        $this->rootPermissions = $callback;
        return $this;
    }

    /**
     * Global permissions required to edit
     *
     * @param array $permissions
     * @return $this
     */
    public function setGlobalEditPermissions($permissions)
    {
        $this->globalEditPermissions = $permissions;
        return $this;
    }

    /**
     * @return array
     */
    public function getGlobalEditPermissions()
    {
        return $this->globalEditPermissions;
    }

    /**
     * Get root permissions handler, or null if no handler
     *
     * @return RootPermissions|null
     */
    public function getRootPermissions()
    {
        return $this->rootPermissions;
    }

    /**
     * Get base class
     *
     * @return string
     */
    public function getBaseClass()
    {
        return $this->baseClass;
    }

    /**
     * Set base class
     *
     * @param string $baseClass
     * @return $this
     */
    public function setBaseClass($baseClass)
    {
        if (!is_a($baseClass, DataObject::class, true)) {
            throw new InvalidArgumentException('Invalid DataObject class: ' . $baseClass);
        }
        $this->baseClass = $baseClass;
        return $this;
    }

    /**
     * Pre-populate the cache of canEdit, canView, canDelete, canPublish permissions. This method will use the static
     * can_(perm)_multiple method for efficiency.
     *
     * @param string $permission    The permission: edit, view, publish, approve, etc.
     * @param array $ids           An array of page IDs
     */
    public function prePopulatePermissionCache($permission = 'edit', $ids = [])
    {
        switch ($permission) {
            case self::EDIT:
                $this->canEditMultiple($ids, Member::currentUser(), false);
                break;
            case self::VIEW:
                $this->canViewMultiple($ids, Member::currentUser(), false);
                break;
            case self::DELETE:
                $this->canDeleteMultiple($ids, Member::currentUser(), false);
                break;
            default:
                throw new InvalidArgumentException("Invalid permission type $permission");
        }
    }

    /**
     * This method is NOT a full replacement for the individual can*() methods, e.g. {@link canEdit()}. Rather than
     * checking (potentially slow) PHP logic, it relies on the database group associations, e.g. the "CanEditType" field
     * plus the "SiteTree_EditorGroups" many-many table. By batch checking multiple records, we can combine the queries
     * efficiently.
     *
     * Caches based on $typeField data. To invalidate the cache, use {@link SiteTree::reset()} or set the $useCached
     * property to FALSE.
     *
     * @param string $type Either edit, view, or create
     * @param array $ids Array of IDs
     * @param Member $member Member
     * @param array $globalPermission If the member doesn't have this permission code, don't bother iterating deeper
     * @param bool $useCached Enables use of cache. Cache will be populated even if this is false.
     * @return array An map of <a href='psi_element://SiteTree'>SiteTree</a> ID keys to boolean values
     * ID keys to boolean values
     */
    protected function batchPermissionCheck(
        $type,
        $ids,
        Member $member = null,
        $globalPermission = [],
        $useCached = true
    ) {
        // Validate ids
        $ids = array_filter($ids, 'is_numeric');
        if (empty($ids)) {
            return [];
        }

        // Default result: nothing editable
        $result = array_fill_keys($ids, false);

        // Validate member permission
        // Only VIEW allows anonymous (Anyone) permissions
        $memberID = $member ? (int)$member->ID : 0;
        if (!$memberID && $type !== self::VIEW) {
            return $result;
        }

        // Look in the cache for values
        $cacheKey = "{$type}-{$memberID}";
        if ($useCached && isset($this->cachePermissions[$cacheKey])) {
            $cachedValues = array_intersect_key($this->cachePermissions[$cacheKey], $result);

            // If we can't find everything in the cache, then look up the remainder separately
            $uncachedIDs = array_keys(array_diff_key($result, $this->cachePermissions[$cacheKey]));
            if ($uncachedIDs) {
                $uncachedValues = $this->batchPermissionCheck($type, $uncachedIDs, $member, $globalPermission, false);
                return $cachedValues + $uncachedValues;
            }
            return $cachedValues;
        }

        // If a member doesn't have a certain permission then they can't edit anything
        if ($globalPermission && !Permission::checkMember($member, $globalPermission)) {
            return $result;
        }

        // Get the groups that the given member belongs to
        $groupIDsSQLList = '0';
        if ($memberID) {
            $groupIDs = $member->Groups()->column("ID");
            $groupIDsSQLList = implode(", ", $groupIDs) ?: '0';
        }

        // Check if record is versioned
        if ($this->isVersioned()) {
            // Check all records for each stage and merge
            $combinedStageResult = [];
            foreach ([ Versioned::DRAFT, Versioned::LIVE ] as $stage) {
                $stageRecords = Versioned::get_by_stage($this->getBaseClass(), $stage)
                    ->byIDs($ids);
                // Exclude previously calculated records from later stage calculations
                if ($combinedStageResult) {
                    $stageRecords = $stageRecords->exclude('ID', array_keys($combinedStageResult));
                }
                $stageResult = $this->batchPermissionCheckForStage(
                    $type,
                    $globalPermission,
                    $stageRecords,
                    $groupIDsSQLList,
                    $member
                );
                // Note: Draft stage takes precedence over live, but only if draft exists
                $combinedStageResult = $combinedStageResult + $stageResult;
            }
        } else {
            // Unstaged result
            $stageRecords = DataObject::get($this->getBaseClass())->byIDs($ids);
            $combinedStageResult = $this->batchPermissionCheckForStage(
                $type,
                $globalPermission,
                $stageRecords,
                $groupIDsSQLList,
                $member
            );
        }

        // Cache the results
        if (empty($this->cachePermissions[$cacheKey])) {
            $this->cachePermissions[$cacheKey] = [];
        }
        if ($combinedStageResult) {
            $this->cachePermissions[$cacheKey] = $combinedStageResult + $this->cachePermissions[$cacheKey];
        }
        return $combinedStageResult;
    }

    /**
     * @param string $type
     * @param array $globalPermission List of global permissions
     * @param DataList $stageRecords List of records to check for this stage
     * @param string $groupIDsSQLList Group IDs this member belongs to
     * @param Member $member
     * @return array
     */
    protected function batchPermissionCheckForStage(
        $type,
        $globalPermission,
        DataList $stageRecords,
        $groupIDsSQLList,
        Member $member = null
    ) {
        // Initialise all IDs to false
        $result = array_fill_keys($stageRecords->column('ID'), false);

        // Get the uninherited permissions
        $typeField = $this->getPermissionField($type);
        if ($member && $member->ID) {
            // Determine if this member matches any of the group or other rules
            $groupJoinTable = $this->getJoinTable($type);
            $baseTable = DataObject::getSchema()->baseDataTable($this->getBaseClass());
            $uninheritedPermissions = $stageRecords
                ->where([
                    "(\"$typeField\" IN (?, ?) OR " .
                    "(\"$typeField\" = ? AND \"$groupJoinTable\".\"{$baseTable}ID\" IS NOT NULL))"
                    => [
                        self::ANYONE,
                        self::LOGGED_IN_USERS,
                        self::ONLY_THESE_USERS
                    ]
                ])
                ->leftJoin(
                    $groupJoinTable,
                    "\"$groupJoinTable\".\"{$baseTable}ID\" = \"{$baseTable}\".\"ID\" AND " .
                    "\"$groupJoinTable\".\"GroupID\" IN ($groupIDsSQLList)"
                )->column('ID');
        } else {
            // Only view pages with ViewType = Anyone if not logged in
            $uninheritedPermissions = $stageRecords
                ->filter($typeField, self::ANYONE)
                ->column('ID');
        }

        if ($uninheritedPermissions) {
            // Set all the relevant items in $result to true
            $result = array_fill_keys($uninheritedPermissions, true) + $result;
        }

        // Group $potentiallyInherited by ParentID; we'll look at the permission of all those parents and
        // then see which ones the user has permission on
        $groupedByParent = [];
        $potentiallyInherited = $stageRecords->filter($typeField, self::INHERIT);
        foreach ($potentiallyInherited as $item) {
            /** @var DataObject|Hierarchy $item */
            if ($item->ParentID) {
                if (!isset($groupedByParent[$item->ParentID])) {
                    $groupedByParent[$item->ParentID] = [];
                }
                $groupedByParent[$item->ParentID][] = $item->ID;
            } else {
                // Fail over to root permission check for Inherit and ParentID = 0
                $result[$item->ID] = $this->checkRootPermission($type, $member);
            }
        }

        // Copy permissions from parent to child
        if ($groupedByParent) {
            $actuallyInherited = $this->batchPermissionCheck(
                $type,
                array_keys($groupedByParent),
                $member,
                $globalPermission
            );
            if ($actuallyInherited) {
                $parentIDs = array_keys(array_filter($actuallyInherited));
                foreach ($parentIDs as $parentID) {
                    // Set all the relevant items in $result to true
                    $result = array_fill_keys($groupedByParent[$parentID], true) + $result;
                }
            }
        }
        return $result;
    }

    /**
     * Get the 'can edit' information for a number of SiteTree pages.
     *
     * @param array $ids An array of IDs of the objects to look up
     * @param Member $member Member object
     * @param bool $useCached Return values from the permission cache if they exist
     * @return array A map where the IDs are keys and the values are
     * booleans stating whether the given object can be edited
     */
    public function canEditMultiple($ids, Member $member = null, $useCached = true)
    {
        return $this->batchPermissionCheck(
            self::EDIT,
            $ids,
            $member,
            $this->getGlobalEditPermissions(),
            $useCached
        );
    }

    /**
     * Get the canView information for a number of objects
     *
     * @param array $ids
     * @param Member $member
     * @param bool $useCached
     * @return mixed
     */
    public function canViewMultiple($ids, Member $member = null, $useCached = true)
    {
        return $this->batchPermissionCheck(self::VIEW, $ids, $member, [], $useCached);
    }

    /**
     * Get the 'can edit' information for a number of SiteTree pages.
     *
     * @param array $ids An array of IDs of the objects pages to look up
     * @param Member $member Member object
     * @param bool $useCached Return values from the permission cache if they exist
     * @return array
     */
    public function canDeleteMultiple($ids, Member $member = null, $useCached = true)
    {
        // Validate ids
        $ids = array_filter($ids, 'is_numeric');
        if (empty($ids)) {
            return [];
        }
        $result = array_fill_keys($ids, false);

        // Validate member permission
        if (!$member || !$member->ID) {
            return $result;
        }
        $deletable = [];

        // Look in the cache for values
        $cacheKey = "delete-{$member->ID}";
        if ($useCached && isset($this->cachePermissions[$cacheKey])) {
            $cachedValues = array_intersect_key($this->cachePermissions[$cacheKey], $result);

            // If we can't find everything in the cache, then look up the remainder separately
            $uncachedIDs = array_keys(array_diff_key($result, $this->cachePermissions[$cacheKey]));
            if ($uncachedIDs) {
                $uncachedValues = $this->canDeleteMultiple($uncachedIDs, $member, false);
                return $cachedValues + $uncachedValues;
            }
            return $cachedValues;
        }

        // You can only delete pages that you can edit
        $editableIDs = array_keys(array_filter($this->canEditMultiple($ids, $member)));
        if ($editableIDs) {
            // You can only delete pages whose children you can delete
            $childRecords = DataObject::get($this->baseClass)
                ->filter('ParentID', $editableIDs);

            // Find out the children that can be deleted
            $children = $childRecords->map("ID", "ParentID");
            $childIDs = $children->keys();
            if ($childIDs) {
                $deletableChildren = $this->canDeleteMultiple($childIDs, $member);

                // Get a list of all the parents that have no undeletable children
                $deletableParents = array_fill_keys($editableIDs, true);
                foreach ($deletableChildren as $id => $canDelete) {
                    if (!$canDelete) {
                        unset($deletableParents[$children[$id]]);
                    }
                }

                // Use that to filter the list of deletable parents that have children
                $deletableParents = array_keys($deletableParents);

                // Also get the $ids that don't have children
                $parents = array_unique($children->values());
                $deletableLeafNodes = array_diff($editableIDs, $parents);

                // Combine the two
                $deletable = array_merge($deletableParents, $deletableLeafNodes);
            } else {
                $deletable = $editableIDs;
            }
        }

        // Convert the array of deletable IDs into a map of the original IDs with true/false as the value
        return array_fill_keys($deletable, true) + array_fill_keys($ids, false);
    }

    /**
     * Check delete permission for a single record ID
     *
     * @param int $id
     * @param Member $member
     * @return bool
     */
    public function canDelete($id, Member $member = null)
    {
        // No ID: Check root permission
        if (!$id) {
            return $this->checkRootPermission(self::DELETE, $member);
        }

        // Regular canEdit logic is handled by canEditMultiple
        $results = $this->canDeleteMultiple(
            [ $id ],
            $member
        );

        // Check if in result
        return isset($results[$id]) ? $results[$id] : false;
    }

    /**
     * Check edit permission for a single record ID
     *
     * @param int $id
     * @param Member $member
     * @return bool
     */
    public function canEdit($id, Member $member = null)
    {
        // No ID: Check root permission
        if (!$id) {
            return $this->checkRootPermission(self::EDIT, $member);
        }

        // Regular canEdit logic is handled by canEditMultiple
        $results = $this->canEditMultiple(
            [ $id ],
            $member
        );

        // Check if in result
        return isset($results[$id]) ? $results[$id] : false;
    }

    /**
     * Check view permission for a single record ID
     *
     * @param int $id
     * @param Member $member
     * @return bool
     */
    public function canView($id, Member $member = null)
    {
        // No ID: Check root permission
        if (!$id) {
            return $this->checkRootPermission(self::VIEW, $member);
        }

        // Regular canView logic is handled by canViewMultiple
        $results = $this->canViewMultiple(
            [ $id ],
            $member
        );

        // Check if in result
        return isset($results[$id]) ? $results[$id] : false;
    }

    /**
     * Get field to check for permission type for the given check
     *
     * @param string $type
     * @return string
     */
    protected function getPermissionField($type)
    {
        switch ($type) {
            case self::DELETE:
                // Delete uses edit type - Drop through
            case self::EDIT:
                return 'CanEditType';
            case self::VIEW:
                return 'CanViewType';
            default:
                throw new InvalidArgumentException("Invalid argument type $type");
        }
    }

    /**
     * Get join table for type
     *
     * @param string $type
     * @return string
     */
    protected function getJoinTable($type)
    {
        $table = DataObject::getSchema()->tableName($this->baseClass);
        switch ($type) {
            case self::DELETE:
                // Delete uses edit type - Drop through
            case self::EDIT:
                return "{$table}_EditorGroups";
            case self::VIEW:
                return "{$table}_ViewerGroups";
            default:
                throw new InvalidArgumentException("Invalid argument type $type");
        }
    }

    /**
     * Determine root permission for a givion check
     *
     * @param string $type Method to check
     * @param Member $member
     * @return bool
     */
    protected function checkRootPermission($type, Member $member = null)
    {
        $rootPermissionHandler = $this->getRootPermissions();
        if (!$rootPermissionHandler) {
            return false;
        }
        switch ($type) {
            case self::VIEW:
                return $rootPermissionHandler->canView($member);
            case self::EDIT:
                return $rootPermissionHandler->canEdit($member);
            case self::DELETE:
                return $rootPermissionHandler->canDelete($member);
            default:
                return false;
        }
    }

    /**
     * Check if this model has versioning
     *
     * @return bool
     */
    protected function isVersioned()
    {
        if (!class_exists(Versioned::class)) {
            return false;
        }
        $singleton = DataObject::singleton($this->getBaseClass());
        return $singleton->hasExtension(Versioned::class);
    }

    /**
     * Clear cache
     *
     * @return $this
     */
    public function clearCache()
    {
        $this->cachePermissions = [];
        return $this;
    }
}
