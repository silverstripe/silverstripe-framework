<?php

namespace SilverStripe\Filesystem;

use DataObject;
use Injector;
use Member;
use Versioned;
use SilverStripe\Filesystem\Storage\AssetStore;
use SilverStripe\Filesystem\Storage\DBFile;

/**
 * This class provides the necessary business logic to ensure that any assets attached
 * to a record are safely deleted, published, or protected during certain operations.
 *
 * This class will respect the canView() of each object, and will use it to determine
 * whether or not public users can access attached assets. Public and live records
 * will have their assets promoted to the public store.
 *
 * Assets which exist only on non-live stages will be protected.
 *
 * Assets which are no longer referenced will be flushed via explicit delete calls
 * to the underlying filesystem.
 *
 * @property DataObject|Versioned $owner A {@see DataObject}, potentially decorated with {@see Versioned} extension.
 */
class AssetControlExtension extends \DataExtension
{

	/**
	 * When archiving versioned dataobjects, should assets be archived with them?
	 * If false, assets will be deleted when the dataobject is archived.
	 * If true, assets will be instead moved to the protected store, and can be
	 * restored when the dataobject is restored from archive.
	 *
	 * Note that this does not affect the archiving of the actual database record in any way,
	 * only the physical file.
	 *
	 * Unversioned dataobjects will ignore this option and always delete attached
	 * assets on deletion.
	 *
	 * @config
	 * @var bool
	 */
	private static $keep_archived_assets = false;

	/**
	 * Ensure that deletes records remove their underlying file assets, without affecting
	 * other staged records.
	 */
	public function onAfterDelete()
	{
		// Prepare blank manipulation
		$manipulations = new AssetManipulationList();

		// Add all assets for deletion
		$this->addAssetsFromRecord($manipulations, $this->owner, AssetManipulationList::STATE_DELETED);

		// Whitelist assets that exist in other stages
		$this->addAssetsFromOtherStages($manipulations);

		// Apply visibility rules based on the final manipulation
		$this->processManipulation($manipulations);
	}

	/**
	 * Ensure that changes to records flush overwritten files, and update the visibility
	 * of other assets.
	 */
	public function onBeforeWrite()
	{
		// Prepare blank manipulation
		$manipulations = new AssetManipulationList();

		// Mark overwritten object as deleted
		if($this->owner->isInDB()) {
			$priorRecord = DataObject::get(get_class($this->owner))->byID($this->owner->ID);
			if($priorRecord) {
				$this->addAssetsFromRecord($manipulations, $priorRecord, AssetManipulationList::STATE_DELETED);
			}
		}

		// Add assets from new record with the correct visibility rules
		$state = $this->getRecordState($this->owner);
		$this->addAssetsFromRecord($manipulations, $this->owner, $state);

		// Whitelist assets that exist in other stages
		$this->addAssetsFromOtherStages($manipulations);

		// Apply visibility rules based on the final manipulation
		$this->processManipulation($manipulations);
	}

	/**
	 * Check default state of this record
	 *
	 * @param DataObject $record
	 * @return string One of AssetManipulationList::STATE_* constants
	 */
	protected function getRecordState($record) {
		if($this->isVersioned()) {
			// Check stage this record belongs to
			$stage = $record->getSourceQueryParam('Versioned.stage') ?: Versioned::get_stage();

			// Non-live stages are automatically non-public
			if($stage !== Versioned::LIVE) {
				return AssetManipulationList::STATE_PROTECTED;
			}
		}

		// Check if canView permits anonymous viewers
		return $record->canView(Member::create())
			? AssetManipulationList::STATE_PUBLIC
			: AssetManipulationList::STATE_PROTECTED;
	}

	/**
	 * Given a set of asset manipulations, trigger any necessary publish, protect, or
	 * delete actions on each asset.
	 *
	 * @param AssetManipulationList $manipulations
	 */
	protected function processManipulation(AssetManipulationList $manipulations)
	{
		// When deleting from stage then check if we should archive assets
		$archive = $this->owner->config()->keep_archived_assets;
		// Publish assets
		$this->publishAll($manipulations->getPublicAssets());

		// Protect assets
		$this->protectAll($manipulations->getProtectedAssets());

		// Check deletion policy
		$deletedAssets = $manipulations->getDeletedAssets();
		if ($archive && $this->isVersioned()) {
			// Archived assets are kept protected
			$this->protectAll($deletedAssets);
		} else {
			// Otherwise remove all assets
			$this->deleteAll($deletedAssets);
		}
	}

	/**
	 * Checks all stages other than the current stage, and check the visibility
	 * of assets attached to those records.
	 *
	 * @param AssetManipulationList $manipulation Set of manipulations to add assets to
	 */
	protected function addAssetsFromOtherStages(AssetManipulationList $manipulation)
	{
		// Skip unversioned or unsaved assets
		if(!$this->isVersioned() || !$this->owner->isInDB()) {
			return;
		}

		// Unauthenticated member to use for checking visibility
		$baseClass = $this->owner->baseClass();
		$filter = array("\"{$baseClass}\".\"ID\"" => $this->owner->ID);
		$stages = $this->owner->getVersionedStages(); // {@see Versioned::getVersionedStages}
		foreach ($stages as $stage) {
			// Skip current stage; These should be handled explicitly
			if($stage === Versioned::get_stage()) {
				continue;
			}

			// Check if record exists in this stage
			$record = Versioned::get_one_by_stage($baseClass, $stage, $filter);
			if (!$record) {
				continue;
			}

			// Check visibility of this record, and record all attached assets
			$state = $this->getRecordState($record);
			$this->addAssetsFromRecord($manipulation, $record, $state);
		}
	}

	/**
	 * Given a record, add all assets it contains to the given manipulation.
	 * State can be declared for this record, otherwise the underlying DataObject
	 * will be queried for canView() to see if those assets are public
	 *
	 * @param AssetManipulationList $manipulation Set of manipulations to add assets to
	 * @param DataObject $record Record
	 * @param string $state One of AssetManipulationList::STATE_* constant values.
	 */
	protected function addAssetsFromRecord(AssetManipulationList $manipulation, DataObject $record, $state)
	{
		// Find all assets attached to this record
		$assets = $this->findAssets($record);
		if (empty($assets)) {
			return;
		}

		// Add all assets to this stage
		foreach ($assets as $asset) {
			$manipulation->addAsset($asset, $state);
		}
	}

	/**
	 * Return a list of all tuples attached to this dataobject
	 * Note: Variants are excluded
	 *
	 * @param DataObject $record to search
	 * @return array
	 */
	protected function findAssets(DataObject $record)
	{
		// Search for dbfile instances
		$files = array();
		foreach ($record->db() as $field => $db) {
			$fieldObj = $record->$field;
			if(!is_object($fieldObj) || !($record->$field instanceof DBFile)) {
							continue;
			}

			// Omit variant and merge with set
			$next = $record->dbObject($field)->getValue();
			unset($next['Variant']);
			if ($next) {
				$files[] = $next;
			}
		}

		// De-dupe
		return array_map("unserialize", array_unique(array_map("serialize", $files)));
	}

	/**
	 * Determine if {@see Versioned) extension rules should be applied to this object
	 *
	 * @return bool
	 */
	protected function isVersioned()
	{
		return $this->owner->has_extension('Versioned') && class_exists('Versioned');
	}

	/**
	 * Delete all assets in the tuple list
	 *
	 * @param array $assets
	 */
	protected function deleteAll($assets)
	{
		if (empty($assets)) {
			return;
		}
		$store = $this->getAssetStore();
		foreach ($assets as $asset) {
			$store->delete($asset['Filename'], $asset['Hash']);
		}
	}

	/**
	 * Move all assets in the list to the public store
	 *
	 * @param array $assets
	 */
	protected function publishAll($assets)
	{
		if (empty($assets)) {
			return;
		}

		$store = $this->getAssetStore();
		foreach ($assets as $asset) {
			$store->publish($asset['Filename'], $asset['Hash']);
		}
	}

	/**
	 * Move all assets in the list to the protected store
	 *
	 * @param array $assets
	 */
	protected function protectAll($assets)
	{
		if (empty($assets)) {
			return;
		}
		$store = $this->getAssetStore();
		foreach ($assets as $asset) {
			$store->protect($asset['Filename'], $asset['Hash']);
		}
	}

	/**
	 * @return AssetStore
	 */
	protected function getAssetStore()
	{
		return Injector::inst()->get('AssetStore');
	}
}
