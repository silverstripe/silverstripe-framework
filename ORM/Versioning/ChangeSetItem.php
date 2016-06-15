<?php

namespace SilverStripe\ORM\Versioning;

use Exception;
use BadMethodCallException;
use Member;
use Permission;
use CMSPreviewable;
use Controller;
use SilverStripe\Filesystem\Thumbnail;
use SilverStripe\ORM\DataObject;

/**
 * A single line in a changeset
 *
 * @property string $Added
 * @property string $ObjectClass The _base_ data class for the referenced DataObject
 * @property int $ObjectID The numeric ID for the referenced object
 * @method ManyManyList ReferencedBy() List of explicit items that require this change
 * @method ManyManyList References() List of implicit items required by this change
 * @method ChangeSet ChangeSet()
 */
class ChangeSetItem extends DataObject implements Thumbnail {

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

	private static $table_name = 'ChangeSetItem';

	/**
	 * Represents that an object has not yet been changed, but
	 * should be included in this changeset as soon as any changes exist
	 */
	const CHANGE_NONE = 'none';

	private static $db = array(
		'VersionBefore' => 'Int',
		'VersionAfter'  => 'Int',
		'Added'         => "Enum('explicitly, implicitly', 'implicitly')"
	);

	private static $has_one = array(
		'ChangeSet' => 'SilverStripe\ORM\Versioning\ChangeSet',
		'Object'    => 'SilverStripe\ORM\DataObject',
	);

	private static $many_many = array(
		'ReferencedBy' => 'SilverStripe\ORM\Versioning\ChangeSetItem'
	);

	private static $belongs_many_many = array(
		'References' => 'ChangeSetItem.ReferencedBy'
	);

	private static $indexes = array(
		'ObjectUniquePerChangeSet' => array(
			'type' => 'unique',
			'value' => '"ObjectID", "ObjectClass", "ChangeSetID"'
		)
	);

	public function onBeforeWrite() {
		// Make sure ObjectClass refers to the base data class in the case of old or wrong code
		$this->ObjectClass = $this->getSchema()->baseDataClass($this->ObjectClass);
		parent::onBeforeWrite();
	}

	public function getTitle() {
		// Get title of modified object
		$object = $this->getObjectLatestVersion();
		if($object) {
			return $object->getTitle();
		}
		return $this->i18n_singular_name() . ' #' . $this->ID;
	}

	/**
	 * Get a thumbnail for this object
	 *
	 * @param int $width Preferred width of the thumbnail
	 * @param int $height Preferred height of the thumbnail
	 * @return string URL to the thumbnail, if available
	 */
	public function ThumbnailURL($width, $height) {
		$object = $this->getObjectLatestVersion();
		if($object instanceof Thumbnail) {
			return $object->ThumbnailURL($width, $height);
		}
		return null;
	}

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
	protected function getObjectInStage($stage) {
		return Versioned::get_by_stage($this->ObjectClass, $stage)->byID($this->ObjectID);
	}

	/**
	 * Find latest version of this object
	 *
	 * @return Versioned|DataObject
	 */
	protected function getObjectLatestVersion() {
		return Versioned::get_latest_version($this->ObjectClass, $this->ObjectID);
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
				$object->publishSingle();
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
		$object = $this->getObjectLatestVersion();
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
	 * Get the ChangeSetItems that reference a passed DataObject
	 *
	 * @param DataObject $object
	 * @return DataList
	 */
	public static function get_for_object($object) {
		return ChangeSetItem::get()->filter([
			'ObjectID' => $object->ID,
			'ObjectClass' => $object->baseClass(),
		]);
	}

	/**
	 * Get the ChangeSetItems that reference a passed DataObject
	 *
	 * @param int $objectID The ID of the object
	 * @param string $objectClass The class of the object (or any parent class)
	 * @return DataList
	 */
	public static function get_for_object_by_id($objectID, $objectClass) {
		return ChangeSetItem::get()->filter([
			'ObjectID' => $objectID,
			'ObjectClass' => static::getSchema()->baseDataClass($objectClass)
		]);
	}

	/**
	 * Gets the list of modes this record can be previewed in.
	 *
	 * {@link https://tools.ietf.org/html/draft-kelly-json-hal-07#section-5}
	 *
	 * @return array Map of links in acceptable HAL format
	 */
	public function getPreviewLinks() {
		$links = [];

		// Preview draft
		$stage = $this->getObjectInStage(Versioned::DRAFT);
		if($stage instanceof CMSPreviewable && $stage->canView() && ($link = $stage->PreviewLink())) {
			$links[Versioned::DRAFT] = [
				'href' => Controller::join_links($link, '?stage=' . Versioned::DRAFT),
				'type' => $stage->getMimeType(),
			];
		}

		// Preview live
		$live = $this->getObjectInStage(Versioned::LIVE);
		if($live instanceof CMSPreviewable && $live->canView() && ($link = $live->PreviewLink())) {
			$links[Versioned::LIVE] = [
				'href' => Controller::join_links($link, '?stage=' . Versioned::LIVE),
				'type' => $live->getMimeType(),
			];
		}

		return $links;
	}

	/**
	 * Get edit link for this item
	 *
	 * @return string
	 */
	public function CMSEditLink()
	{
		$link = $this->getObjectInStage(Versioned::DRAFT);
		if($link instanceof CMSPreviewable) {
			return $link->CMSEditLink();
		}
		return null;
	}
}
