<?php

namespace SilverStripe\Security;

use InvalidArgumentException;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\Hierarchy;
use SilverStripe\Versioned\Versioned;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Cache\MemberCacheFlusher;
use SilverStripe\Dev\Deprecation;

/**
 * Calculates batch permissions for nested objects for:
 *  - canView: Supports 'Anyone' type
 *  - canEdit
 *  - canDelete: Includes special logic for ensuring parent objects can only be deleted if their children can
 *    be deleted also.
 */
class InheritedPermissions implements PermissionChecker, MemberCacheFlusher
{
    use Injectable;

    /**
     * Delete permission
     */
    public const DELETE = 'delete';

    /**
     * View permission
     */
    public const VIEW = 'view';

    /**
     * Edit permission
     */
    public const EDIT = 'edit';

    /**
     * Anyone canView permission
     */
    public const ANYONE = 'Anyone';

    /**
     * Restrict to logged in users
     */
    public const LOGGED_IN_USERS = 'LoggedInUsers';

    /**
     * Restrict to specific groups
     */
    public const ONLY_THESE_USERS = 'OnlyTheseUsers';

    /**
     * Restrict to specific members
     */
    public const ONLY_THESE_MEMBERS = 'OnlyTheseMembers';

    /**
     * Inherit from parent
     */
    public const INHERIT = 'Inherit';

    /**
     * Class name
     *
     * @var string
     */
    protected $baseClass = null;

    /**
     * Object for evaluating top level permissions designed as "Inherit"
     *
     * @var DefaultPermissionChecker
     */
    protected $defaultPermissions = null;

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
     * @var CacheInterface
     */
    protected $cacheService;

    /**
     * Construct new permissions object
     *
     * @param string $baseClass Base class
     * @param CacheInterface $cache
     */
    public function __construct($baseClass, CacheInterface $cache = null)
    {
        if (!is_a($baseClass, DataObject::class, true)) {
            throw new InvalidArgumentException('Invalid DataObject class: ' . $baseClass);
        }

        $this->baseClass = $baseClass;
        $this->cacheService = $cache;

        return $this;
    }

    /**
     * Commits the cache
     */
    public function __destruct()
    {
        // Ensure back-end cache is updated
        if (!empty($this->cachePermissions) && $this->cacheService) {
            foreach ($this->cachePermissions as $key => $permissions) {
                $this->cacheService->set($key, $permissions);
            }
            // Prevent double-destruct
            $this->cachePermissions = [];
        }
    }

    /**
     * Clear the cache for this instance only
     *
     * @param array $memberIDs A list of member IDs
     */
    public function flushMemberCache($memberIDs = null)
    {
        if (!$this->cacheService) {
            return;
        }

        // Hard flush, e.g. flush=1
        if (!$memberIDs) {
            $this->cacheService->clear();
        }

        if ($memberIDs && is_array($memberIDs)) {
            foreach ([InheritedPermissions::VIEW, InheritedPermissions::EDIT, InheritedPermissions::DELETE] as $type) {
                foreach ($memberIDs as $memberID) {
                    $key = $this->generateCacheKey($type, $memberID);
                    $this->cacheService->delete($key);
                }
            }
        }
    }

    /**
     * @param DefaultPermissionChecker $callback
     * @return $this
     */
    public function setDefaultPermissions(DefaultPermissionChecker $callback)
    {
        $this->defaultPermissions = $callback;
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
     * @return DefaultPermissionChecker|null
     */
    public function getDefaultPermissions()
    {
        return $this->defaultPermissions;
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
     * Force pre-calculation of a list of permissions for optimisation
     *
     * @param string $permission
     * @param array $ids
     */
    public function prePopulatePermissionCache($permission = 'edit', $ids = [])
    {
        switch ($permission) {
            case InheritedPermissions::EDIT:
                $this->canEditMultiple($ids, Security::getCurrentUser(), false);
                break;
            case InheritedPermissions::VIEW:
                $this->canViewMultiple($ids, Security::getCurrentUser(), false);
                break;
            case InheritedPermissions::DELETE:
                $this->canDeleteMultiple($ids, Security::getCurrentUser(), false);
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
     * @return array A map of permissions, keys are ID numbers, and values are boolean permission checks
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
        $ids = array_filter($ids ?? [], 'is_numeric');
        if (empty($ids)) {
            return [];
        }

        // Default result: nothing editable
        $result = array_fill_keys($ids ?? [], false);

        // Validate member permission
        // Only VIEW allows anonymous (Anyone) permissions
        $memberID = $member ? (int)$member->ID : 0;
        if (!$memberID && $type !== InheritedPermissions::VIEW) {
            return $result;
        }

        // Look in the cache for values
        $cacheKey = $this->generateCacheKey($type, $memberID);
        $cachePermissions = $this->getCachePermissions($cacheKey);
        if ($useCached && $cachePermissions) {
            $cachedValues = array_intersect_key($cachePermissions ?? [], $result);

            // If we can't find everything in the cache, then look up the remainder separately
            $uncachedIDs = array_keys(array_diff_key($result ?? [], $cachePermissions));
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
            foreach ([Versioned::DRAFT, Versioned::LIVE] as $stage) {
                $stageRecords = Versioned::get_by_stage($this->getBaseClass(), $stage)
                    ->byIDs($ids);
                // Exclude previously calculated records from later stage calculations
                if ($combinedStageResult) {
                    $stageRecords = $stageRecords->exclude('ID', array_keys($combinedStageResult ?? []));
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
        $result = array_fill_keys($stageRecords->column('ID') ?? [], false);

        // Get the uninherited permissions
        $typeField = $this->getPermissionField($type);
        $baseTable = DataObject::getSchema()->baseDataTable($this->getBaseClass());

        if ($member && $member->ID) {
            if (!Permission::checkMember($member, 'ADMIN')) {
                // Determine if this member matches any of the group or other rules
                $groupJoinTable = $this->getGroupJoinTable($type);
                $memberJoinTable = $this->getMemberJoinTable($type);
                $uninheritedPermissions = $stageRecords
                    ->where([
                        "(\"$typeField\" IN (?, ?)"
                        . " OR (\"$typeField\" = ? AND \"$groupJoinTable\".\"{$baseTable}ID\" IS NOT NULL)"
                        . " OR (\"$typeField\" = ? AND \"$memberJoinTable\".\"{$baseTable}ID\" IS NOT NULL)"
                        . ")"
                        => [
                            InheritedPermissions::ANYONE,
                            InheritedPermissions::LOGGED_IN_USERS,
                            InheritedPermissions::ONLY_THESE_USERS,
                            InheritedPermissions::ONLY_THESE_MEMBERS,
                        ]
                    ])
                    ->leftJoin(
                        $groupJoinTable,
                        "\"$groupJoinTable\".\"{$baseTable}ID\" = \"{$baseTable}\".\"ID\" AND " . "\"$groupJoinTable\".\"GroupID\" IN ($groupIDsSQLList)"
                    )->leftJoin(
                        $memberJoinTable,
                        "\"$memberJoinTable\".\"{$baseTable}ID\" = \"{$baseTable}\".\"ID\" AND " . "\"$memberJoinTable\".\"MemberID\" = {$member->ID}"
                    )->column('ID');
            } else {
                $uninheritedPermissions = $stageRecords->column('ID');
            }
        } else {
            // Only view pages with ViewType = Anyone if not logged in
            $uninheritedPermissions = $stageRecords
                ->filter($typeField, InheritedPermissions::ANYONE)
                ->column('ID');
        }

        if ($uninheritedPermissions) {
            // Set all the relevant items in $result to true
            $result = array_fill_keys($uninheritedPermissions ?? [], true) + $result;
        }

        // This looks for any of our subjects who has their permission set to "inherited" in the CMS.
        // We group these and run a batch permission check on all parents. This gives us the result
        // of whether the user has permission to edit this object.
        $groupedByParent = [];
        $potentiallyInherited = $stageRecords->filter($typeField, InheritedPermissions::INHERIT)
            ->orderBy("\"{$baseTable}\".\"ID\"")
            ->dataQuery()
            ->query()
            ->setSelect([
                "\"{$baseTable}\".\"ID\"",
                "\"{$baseTable}\".\"ParentID\""
            ])
            ->execute();

        foreach ($potentiallyInherited as $item) {
            if ($item['ParentID']) {
                if (!isset($groupedByParent[$item['ParentID']])) {
                    $groupedByParent[$item['ParentID']] = [];
                }
                $groupedByParent[$item['ParentID']][] = $item['ID'];
            } else {
                // Fail over to default permission check for Inherit and ParentID = 0
                $result[$item['ID']] = $this->checkDefaultPermissions($type, $member);
            }
        }

        // Copy permissions from parent to child
        if (!empty($groupedByParent)) {
            $actuallyInherited = $this->batchPermissionCheck(
                $type,
                array_keys($groupedByParent ?? []),
                $member,
                $globalPermission
            );
            if ($actuallyInherited) {
                $parentIDs = array_keys(array_filter($actuallyInherited ?? []));
                foreach ($parentIDs as $parentID) {
                    // Set all the relevant items in $result to true
                    $result = array_fill_keys($groupedByParent[$parentID] ?? [], true) + $result;
                }
            }
        }
        return $result;
    }

    /**
     * @param array $ids
     * @param Member|null $member
     * @param bool $useCached
     * @return array
     */
    public function canEditMultiple($ids, Member $member = null, $useCached = true)
    {
        return $this->batchPermissionCheck(
            InheritedPermissions::EDIT,
            $ids,
            $member,
            $this->getGlobalEditPermissions(),
            $useCached
        );
    }

    /**
     * @param array $ids
     * @param Member|null $member
     * @param bool $useCached
     * @return array
     */
    public function canViewMultiple($ids, Member $member = null, $useCached = true)
    {
        return $this->batchPermissionCheck(InheritedPermissions::VIEW, $ids, $member, [], $useCached);
    }

    /**
     * @param array $ids
     * @param Member|null $member
     * @param bool $useCached
     * @return array
     */
    public function canDeleteMultiple($ids, Member $member = null, $useCached = true)
    {
        // Validate ids
        $ids = array_filter($ids ?? [], 'is_numeric');
        if (empty($ids)) {
            return [];
        }
        $result = array_fill_keys($ids ?? [], false);

        // Validate member permission
        if (!$member || !$member->ID) {
            return $result;
        }
        $deletable = [];

        // Look in the cache for values
        $cacheKey = "delete-{$member->ID}";
        $cachePermissions = $this->getCachePermissions($cacheKey);
        if ($useCached && $cachePermissions) {
            $cachedValues = array_intersect_key($cachePermissions[$cacheKey] ?? [], $result);

            // If we can't find everything in the cache, then look up the remainder separately
            $uncachedIDs = array_keys(array_diff_key($result ?? [], $cachePermissions[$cacheKey]));
            if ($uncachedIDs) {
                $uncachedValues = $this->canDeleteMultiple($uncachedIDs, $member, false);
                return $cachedValues + $uncachedValues;
            }
            return $cachedValues;
        }

        // You can only delete pages that you can edit
        $editableIDs = array_keys(array_filter($this->canEditMultiple($ids, $member) ?? []));
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
                $deletableParents = array_fill_keys($editableIDs ?? [], true);
                foreach ($deletableChildren as $id => $canDelete) {
                    if (!$canDelete) {
                        unset($deletableParents[$children[$id]]);
                    }
                }

                // Use that to filter the list of deletable parents that have children
                $deletableParents = array_keys($deletableParents ?? []);

                // Also get the $ids that don't have children
                $parents = array_unique($children->values() ?? []);
                $deletableLeafNodes = array_diff($editableIDs ?? [], $parents);

                // Combine the two
                $deletable = array_merge($deletableParents, $deletableLeafNodes);
            } else {
                $deletable = $editableIDs;
            }
        }

        // Convert the array of deletable IDs into a map of the original IDs with true/false as the value
        return array_fill_keys($deletable ?? [], true) + array_fill_keys($ids ?? [], false);
    }

    /**
     * @param int $id
     * @param Member|null $member
     * @return bool|mixed
     */
    public function canDelete($id, Member $member = null)
    {
        // No ID: Check default permission
        if (!$id) {
            return $this->checkDefaultPermissions(InheritedPermissions::DELETE, $member);
        }

        // Regular canEdit logic is handled by canEditMultiple
        $results = $this->canDeleteMultiple(
            [$id],
            $member
        );

        // Check if in result
        return isset($results[$id]) ? $results[$id] : false;
    }

    /**
     * @param int $id
     * @param Member|null $member
     * @return bool|mixed
     */
    public function canEdit($id, Member $member = null)
    {
        // No ID: Check default permission
        if (!$id) {
            return $this->checkDefaultPermissions(InheritedPermissions::EDIT, $member);
        }

        // Regular canEdit logic is handled by canEditMultiple
        $results = $this->canEditMultiple(
            [$id],
            $member
        );

        // Check if in result
        return isset($results[$id]) ? $results[$id] : false;
    }

    /**
     * @param int $id
     * @param Member|null $member
     * @return bool|mixed
     */
    public function canView($id, Member $member = null)
    {
        // No ID: Check default permission
        if (!$id) {
            return $this->checkDefaultPermissions(InheritedPermissions::VIEW, $member);
        }

        // Regular canView logic is handled by canViewMultiple
        $results = $this->canViewMultiple(
            [$id],
            $member
        );

        // Check if in result
        return isset($results[$id]) ? $results[$id] : false;
    }

    /**
     * Get field to check for permission type for the given check.
     * Defaults to those provided by {@see InheritedPermissionsExtension)
     *
     * @param string $type
     * @return string
     */
    protected function getPermissionField($type)
    {
        switch ($type) {
            case InheritedPermissions::DELETE:
                // Delete uses edit type - Drop through
            case InheritedPermissions::EDIT:
                return 'CanEditType';
            case InheritedPermissions::VIEW:
                return 'CanViewType';
            default:
                throw new InvalidArgumentException("Invalid argument type $type");
        }
    }

    /**
     * Get join table for type
     * Defaults to those provided by {@see InheritedPermissionsExtension)
     *
     * @deprecated 5.1.0 Use getGroupJoinTable() instead
     * @param string $type
     * @return string
     */
    protected function getJoinTable($type)
    {
        Deprecation::notice('5.1.0', 'Use getGroupJoinTable() instead');
        return $this->getGroupJoinTable($type);
    }

    /**
     * Get group join table for type
     * Defaults to those provided by {@see InheritedPermissionsExtension)
     *
     * @param string $type
     * @return string
     */
    protected function getGroupJoinTable($type)
    {
        switch ($type) {
            case InheritedPermissions::DELETE:
                // Delete uses edit type - Drop through
            case InheritedPermissions::EDIT:
                return $this->getEditorGroupsTable();
            case InheritedPermissions::VIEW:
                return $this->getViewerGroupsTable();
            default:
                throw new InvalidArgumentException("Invalid argument type $type");
        }
    }

    /**
     * Get member join table for type
     * Defaults to those provided by {@see InheritedPermissionsExtension)
     *
     * @param string $type
     * @return string
     */
    protected function getMemberJoinTable($type)
    {
        switch ($type) {
            case InheritedPermissions::DELETE:
                // Delete uses edit type - Drop through
            case InheritedPermissions::EDIT:
                return $this->getEditorMembersTable();
            case InheritedPermissions::VIEW:
                return $this->getViewerMembersTable();
            default:
                throw new InvalidArgumentException("Invalid argument type $type");
        }
    }

    /**
     * Determine default permission for a givion check
     *
     * @param string $type Method to check
     * @param Member $member
     * @return bool
     */
    protected function checkDefaultPermissions($type, Member $member = null)
    {
        $defaultPermissions = $this->getDefaultPermissions();
        if (!$defaultPermissions) {
            return false;
        }
        switch ($type) {
            case InheritedPermissions::VIEW:
                return $defaultPermissions->canView($member);
            case InheritedPermissions::EDIT:
                return $defaultPermissions->canEdit($member);
            case InheritedPermissions::DELETE:
                return $defaultPermissions->canDelete($member);
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
        /** @var Versioned|DataObject $singleton */
        $singleton = DataObject::singleton($this->getBaseClass());
        return $singleton->hasExtension(Versioned::class) && $singleton->hasStages();
    }

    /**
     * @return $this
     */
    public function clearCache()
    {
        $this->cachePermissions = [];
        return $this;
    }

    /**
     * Get table to use for editor groups relation
     *
     * @return string
     */
    protected function getEditorGroupsTable()
    {
        $table = DataObject::getSchema()->tableName($this->baseClass);
        return "{$table}_EditorGroups";
    }

    /**
     * Get table to use for viewer groups relation
     *
     * @return string
     */
    protected function getViewerGroupsTable()
    {
        $table = DataObject::getSchema()->tableName($this->baseClass);
        return "{$table}_ViewerGroups";
    }

    /**
     * Get table to use for editor members relation
     *
     * @return string
     */
    protected function getEditorMembersTable()
    {
        $table = DataObject::getSchema()->tableName($this->baseClass);
        return "{$table}_EditorMembers";
    }

    /**
     * Get table to use for viewer members relation
     *
     * @return string
     */
    protected function getViewerMembersTable()
    {
        $table = DataObject::getSchema()->tableName($this->baseClass);
        return "{$table}_ViewerMembers";
    }

    /**
     * Gets the permission from cache
     *
     * @param string $cacheKey
     * @return mixed
     */
    protected function getCachePermissions($cacheKey)
    {
        // Check local cache
        if (isset($this->cachePermissions[$cacheKey])) {
            return $this->cachePermissions[$cacheKey];
        }

        // Check persistent cache
        if ($this->cacheService) {
            $result = $this->cacheService->get($cacheKey);

            // Warm local cache
            if ($result) {
                $this->cachePermissions[$cacheKey] = $result;
                return $result;
            }
        }

        return null;
    }

    /**
     * Creates a cache key for a member and type
     *
     * @param string $type
     * @param int $memberID
     * @return string
     */
    protected function generateCacheKey($type, $memberID)
    {
        $classKey = str_replace('\\', '-', $this->baseClass ?? '');
        return "{$type}-{$classKey}-{$memberID}";
    }
}
