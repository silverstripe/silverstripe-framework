<?php

// namespace SilverStripe\Framework\Model\Versioning

/**
 * A single line in a changeset
 *
 * @property string $ReferencedBy
 * @property string $Added
 * @property string $ObjectClass
 * @property int $ObjectID
 * @method ChangeSet ChangeSet()
 */
class ChangeSetItem extends DataObject {

	const EXPLICITLY = 'explicitly';

	const IMPLICITLY = 'implicitly';

	/** Represents an object deleted */
	const CHANGE_DELETED = 'deleted';

	/** Represents an object which was modified */
	const CHANGE_MODIFIED = 'modified';

	/** Represents an object added */
	const CHANGE_CREATED = 'created';

	/** Represents an object which hasn't been changed directly, but owns a modified many_many relationship. */
	//const CHANGE_MANYMANY = 'manymany';

	/**
	 * Represents that an object has not yet been changed, but
	 * should be included in this changeset as soon as any changes exist
	 */
	const CHANGE_NONE = 'none';

	private static $db = array(
		'VersionBefore' => 'Int',
		'VersionAfter'  => 'Int',
		'Added'         => "Enum('explicitly, implicitly', 'implicitly')",
		'ReferencedBy'  => 'Varchar(255)'
	);

	private static $has_one = array(
		'ChangeSet' => 'ChangeSet',
		'Object'      => 'DataObject',
	);

	private static $indexes = array(
		'ObjectUniquePerChangeSet' => array(
			'type' => 'unique',
			'value' => '"ObjectID", "ObjectClass", "ChangeSetID"'
		)
	);

	/**
	 * Get the type of change: none, created, deleted, modified, manymany
	 *
	 * @return string
	 */
	public function getChangeType() {
		// Get change versions
		if($this->VersionBefore || $this->VersionAfter) {
			$draftVersion = $this->VersionAfter; // After publishing draft was written to stage
			$liveVersion = $this->VersionBefore; // The live version before the publish
		} else {
			$draftVersion = Versioned::get_versionnumber_by_stage(
				$this->ObjectClass, Versioned::DRAFT, $this->ObjectID, false
			);
			$liveVersion = Versioned::get_versionnumber_by_stage(
				$this->ObjectClass, Versioned::LIVE, $this->ObjectID, false
			);
		}

		// Version comparisons
		if ($draftVersion == $liveVersion) {
			return self::CHANGE_NONE;
		} elseif (!$liveVersion) {
			return self::CHANGE_CREATED;
		} elseif (!$draftVersion) {
			return self::CHANGE_DELETED;
		} else {
			return self::CHANGE_MODIFIED;
		}
	}

	/**
	 * Find version of this object in the given stage
	 *
	 * @param string $stage
	 * @return Versioned|DataObject
	 */
	private function getObjectInStage($stage) {
		return Versioned::get_by_stage($this->ObjectClass, $stage)->byID($this->ObjectID);
	}

	/**
	 * Get all implicit objects for this change
	 *
	 * @return SS_List
	 */
	public function findReferenced() {
		if($this->getChangeType() === ChangeSetItem::CHANGE_DELETED) {
			// If deleted from stage, need to look at live record
			return $this->getObjectInStage(Versioned::LIVE)->findOwners(false);
		} else {
			// If changed on stage, look at owned objects there
			return $this->getObjectInStage(Versioned::DRAFT)->findOwned()->filterByCallback(function ($owned) {
				/** @var Versioned|DataObject $owned */
				return $owned->stagesDiffer(Versioned::DRAFT, Versioned::LIVE);
			});
		}
	}

	/**
	 * Publish this item, then close it.
	 *
	 * Note: Unlike Versioned::doPublish() and Versioned::doUnpublish, this action is not recursive.
	 */
	public function publish() {
		// Logical checks prior to publish
		if(!$this->canPublish()) {
			throw new Exception("The current member does not have permission to publish this ChangeSetItem.");
		}
		if($this->VersionBefore || $this->VersionAfter) {
			throw new BadMethodCallException("This ChangeSetItem has already been published");
		}

		// Record state changed
		$this->VersionAfter = Versioned::get_versionnumber_by_stage(
			$this->ObjectClass, Versioned::DRAFT, $this->ObjectID, false
		);
		$this->VersionBefore = Versioned::get_versionnumber_by_stage(
			$this->ObjectClass, Versioned::LIVE, $this->ObjectID, false
		);

		switch($this->getChangeType()) {
			case static::CHANGE_NONE: {
				break;
			}
			case static::CHANGE_DELETED: {
				// Non-recursive delete
				$object = $this->getObjectInStage(Versioned::LIVE);
				$object->deleteFromStage(Versioned::LIVE);
				break;
			}
			case static::CHANGE_MODIFIED:
			case static::CHANGE_CREATED: {
				// Non-recursive publish
				$object = $this->getObjectInStage(Versioned::DRAFT);
				$object->publish(Versioned::DRAFT, Versioned::LIVE);
				break;
			}
		}

		$this->write();
	}

	/** Reverts this item, then close it. **/
	public function revert() {
		user_error('Not implemented', E_USER_ERROR);
	}

	public function canView($member = null) {
		return $this->can(__FUNCTION__, $member);
	}

	public function canEdit($member = null) {
		return $this->can(__FUNCTION__, $member);
	}

	public function canCreate($member = null, $context = array()) {
		return $this->can(__FUNCTION__, $member, $context);
	}

	public function canDelete($member = null) {
		return $this->can(__FUNCTION__, $member);
	}

	/**
	 * Check if the BeforeVersion of this changeset can be restored to draft
	 *
	 * @param Member $member
	 * @return bool
	 */
	public function canRevert($member) {
		// Just get the best version as this object may not even exist on either stage anymore.
		/** @var Versioned|DataObject $object */
		$object = Versioned::get_latest_version($this->ObjectClass, $this->ObjectID);
		if(!$object) {
			return false;
		}

		// Check change type
		switch($this->getChangeType()) {
			case static::CHANGE_CREATED: {
				// Revert creation by deleting from stage
				if(!$object->canDelete($member)) {
					return false;
				}
				break;
			}
			default: {
				// All other actions are typically editing draft stage
				if(!$object->canEdit($member)) {
					return false;
				}
				break;
			}
		}

		// If object can be published/unpublished let extensions deny
		return $this->can(__FUNCTION__, $member);
	}

	/**
	 * Check if this ChangeSetItem can be published
	 *
	 * @param Member $member
	 * @return bool
	 */
	public function canPublish($member = null) {
		// Check canMethod to invoke on object
		switch($this->getChangeType()) {
			case static::CHANGE_DELETED: {
				/** @var Versioned|DataObject $object */
				$object = Versioned::get_by_stage($this->ObjectClass, Versioned::LIVE)->byID($this->ObjectID);
				if(!$object || !$object->canUnpublish($member)) {
					return false;
				}
				break;
			}
			default: {
				/** @var Versioned|DataObject $object */
				$object = Versioned::get_by_stage($this->ObjectClass, Versioned::DRAFT)->byID($this->ObjectID);
				if(!$object || !$object->canPublish($member)) {
					return false;
				}
				break;
			}
		}

		// If object can be published/unpublished let extensions deny
		return $this->can(__FUNCTION__, $member);
	}

	/**
	 * Default permissions for this ChangeSetItem
	 *
	 * @param string $perm
	 * @param Member $member
	 * @param array $context
	 * @return bool
	 */
	public function can($perm, $member = null, $context = array()) {
		if(!$member) {
			$member = Member::currentUser();
		}

		// Allow extensions to bypass default permissions, but only if
		// each change can be individually published.
		$extended = $this->extendedCan($perm, $member, $context);
		if($extended !== null) {
			return $extended;
		}

		// Default permissions
		return (bool)Permission::checkMember($member, ChangeSet::config()->required_permission);
	}

	/**
	 * If this object is implicitly added, returns the list of
	 * changes for objects that reference this.
	 *
	 * @return SS_List|null
	 */
	public function getReferencedItems() {
		// Skip objects not implicitly added
		if(!$this->isInDB()
			|| $this->Added !== static::IMPLICITLY
			|| empty($this->ReferencedBy)
		) {
			return null;
		}

		$items = new ArrayList();
		$objectReferences = explode(',', $this->ReferencedBy);
		foreach($objectReferences as $objectReference) {
			list($objectClass, $objectID) = explode('.', $objectReference);
			// Find explicit change matching this object reference
			$item = $this->ChangeSet()->Changes()->filter([
				'ObjectClass' => $objectClass,
				'ObjectID' => $objectID,
				'Added' => static::EXPLICITLY,
			])->first();
			if($item) {
				$items->push($item);
			}
		}
		return $items;
	}

}
