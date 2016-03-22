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

	/** Get the type of change: none, created, deleted, modified, manymany */
	public function getChangeType() {
		$draftVersion = Versioned::get_versionnumber_by_stage($this->ObjectClass, Versioned::DRAFT, $this->ObjectID, false);
		$liveVersion = Versioned::get_versionnumber_by_stage($this->ObjectClass, Versioned::LIVE, $this->ObjectID, false);

		if ($draftVersion == $liveVersion) return self::CHANGE_NONE;
		if (!$liveVersion) return self::CHANGE_CREATED;
		if (!$draftVersion) return self::CHANGE_DELETED;
		return self::CHANGE_MODIFIED;
	}

	/** Publish this item, then close it. */
	public function publish() {
		user_error('Not implemented', E_USER_ERROR);
	}

	/** Reverts this item, then close it. **/
	public function revert() {
		user_error('Not implemented', E_USER_ERROR);
	}

}