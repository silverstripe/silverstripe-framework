<?php

// namespace SilverStripe\Framework\Model\Versioning

/**
 * A single line in a changeset
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
	const CHANGE_MANYMANY = 'manymany';

	/**
	 * Represents that an object has not yet been changed, but
	 * should be included in this changeset as soon as any changes exist
	 */
	const CHANGE_NONE = 'none';

	private static $db = array(
		'VersionBefore' => 'Int',
		'VersionAfter'  => 'Int',
		'Added'         => "Enum('explicitly, implicitly', 'implicitly')",
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

	/** Publish this item, then close it. */
	public function publish() {
		user_error('Not implemented', E_USER_ERROR);
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

}
