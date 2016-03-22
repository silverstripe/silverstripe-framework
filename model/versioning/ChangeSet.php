<?php

// namespace SilverStripe\Framework\Model\Versioning

/**
 * The ChangeSet model tracks several VersionedAndStaged objects for later publication as a single
 * atomic action
 *
 * @package framework
 * @subpackage model
 */
class ChangeSet extends DataObject {

	/** An active changeset */
	const STATE_OPEN = 'open';

	/** A changeset which is reverted and closed */
	const STATE_REVERTED = 'reverted';

	/** A changeset which is published and closed */
	const STATE_ADDED = 'published';

	private static $db = array(
		'Name'  => 'Varchar',
		'State' => "Enum('open,published,reverted')"
	);

	private static $has_many = array(
		'Changes' => 'ChangeSetItem',
	);

	private static $has_one = array(
		'Owner' => 'Member'
	);

	/** Publish this changeset, then closes it. */
	public function publish() {
		user_error('Not implemented', E_USER_ERROR);
	}

	/** Revert all changes made to this changeset, then closes it. **/
	public function revert() {
		user_error('Not implemented', E_USER_ERROR);
	}

	/** Add a new change to this changeset. Will automatically include all owned changes as those are dependencies of this item. */
	public function addObject(DataObject $object) {

		$references = [
			'ObjectID'    => $object->ID,
			'ObjectClass' => $object->ClassName,
			'ChangeSetID' => $this->ID
		];

		$item = ChangeSetItem::get()->filter($references)->first();
		if (!$item) $item = new ChangeSetItem($references);

		$item->Added = ChangeSetItem::EXPLICITLY;
		$item->write();

		$this->sync();
	}

	/** Remove an item from this changeset. Will automatically remove all changes which own (and thus depend on) the removed item. */
	public function removeObject(DataObject $object) {
		$item = ChangeSetItem::get()->filter(['ObjectID' => $object->ID, 'ObjectClass' => $object->ClassName, 'ChangeSetID' => $this->ID])->first();

		if ($item) {
			// TODO: Handle case of implicit added item being removed.

			$item->delete();
		}

		$this->sync();
	}

	protected function calculateImplicit() {
		/** @var string[][] $explicit List of all items that have been explicitly added to this ChangeSet */
		$explicit = array();

		/** @var string[][] $referenced List of all items that are "referenced" by items in $explicit */
		$referenced = array();

		foreach ($this->Changes()->filter(['Added' => ChangeSetItem::EXPLICITLY]) as $item) {
			$explicit[$item->ObjectID . '.' . $item->ObjectClass] = true;

			if ($item->Type() == ChangeSetItem::CHANGE_DELETED) {
				$toadd = $item->findOwners(false);
			} else {
				$toadd = $item->findOwned()->filterByCallback(function ($object) {
					return $object->stagesDiffer(Versioned::DRAFT, Versioned::LIVE);
				});
			}

			foreach ($toadd as $add) {
				$referenced[$add->ID . '.' . $add->ClassName] = ['ObjectID' => $add->ID, 'ObjectClass' => $add->ClassName];
			}
		}

		/** @var string[][] $explicit List of all items that are either in $explicit, $referenced or both */
		$all = array_merge($referenced, $explicit);

		/** @var string[][] $implicit Anything that is in $all, but not in $explicit, is an implicit inclusion */
		$implicit = array_diff_key($all, $explicit);

		return $implicit;
	}

	/**
	 * Add implicit changes that should be included in this changeset
	 *
	 * When an item is created or changed, all it's owned items which have
	 * changes are implicitly added
	 *
	 * When an item is deleted, it's owner (even if that owner does not have changes)
	 * is implicitly added
	 */
	public function sync() {
		// Start a transaction (if we can)

		if (DB::get_conn()->supportsTransactions()) DB::get_conn()->transactionStart();

		// Get the implicitly included items for this ChangeSet

		$implicit = $this->calculateImplicit();

		// Adjust the existing implicit ChangeSetItems for this ChangeSet

		foreach ($this->Changes()->filter(['Added' => ChangeSetItem::IMPLICITLY]) as $item) {
			$objectKey = $item->ObjectID . '.' . $item->ObjectClass;

			// If a ChangeSetItem exists, but isn't in $implicit, it's no longer required, so delete it
			if (!array_key_exists($objectKey, $implicit)) $item->delete();
			// Otherwise it is required, so remove from $implicit
			else unset($implicit[$objectKey]);
		}

		// Now $implicit is all those items that are implicitly included, but don't currently have a ChangeSetItem.
		// So create new ChangeSetItems to match

		foreach ($implicit as $key => $props) {
			$item = new ChangeSetItem($props);
			$item->Added = ChangeSetItem::IMPLICITLY;
			$item->ChangeSetID = $this->ID;
			$item->write();
		}

		// Finally, commit the transaction

		if (DB::get_conn()->supportsTransactions()) DB::get_conn()->transactionEnd();
	}

	/** Verify that any objects in this changeset include all owned changes */
	public function validate() {
		$implicit = $this->calculateImplicit();

		// Check the existing implicit ChangeSetItems for this ChangeSet

		foreach ($this->Changes()->filter(['Added' => ChangeSetItem::IMPLICITLY]) as $item) {
			$objectKey = $item->ObjectID . '.' . $item->ObjectClass;

			// If a ChangeSetItem exists, but isn't in $implicit -> validation failure
			if (!array_key_exists($objectKey, $implicit)) return false;
			// Exists, remove from $implicit
			unset($implicit[$objectKey]);
		}

		// If there's anything left in $implicit -> validation failure

		return empty($implicit);
	}

	/** Standard permission mechanisms */
	public function canView($member = null) {
	}

	public function canEdit($member = null) {
	}

	public function canCreate($member = null, $context = array()) {
	}

	public function canDelete($member = null) {
	}

	public function canPublish($member = null) {
	}

	public function canRevert($member = null) {
	}
}