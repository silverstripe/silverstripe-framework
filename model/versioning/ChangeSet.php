<?php

// namespace SilverStripe\Framework\Model\Versioning

/**
 * The ChangeSet model tracks several VersionedAndStaged objects for later publication as a single
 * atomic action
 *
 * @method HasManyList Changes()
 * @method Member Owner()
 * @property string $Name
 * @property string $State
 *
 * @package framework
 * @subpackage model
 */
class ChangeSet extends DataObject {

	private static $singular_name = 'Campaign';

	private static $plural_name = 'Campaigns';

	/** An active changeset */
	const STATE_OPEN = 'open';

	/** A changeset which is reverted and closed */
	const STATE_REVERTED = 'reverted';

	/** A changeset which is published and closed */
	const STATE_PUBLISHED = 'published';

	private static $db = array(
		'Name'  => 'Varchar',
		'State' => "Enum('open,published,reverted')",
	);

	private static $has_many = array(
		'Changes' => 'ChangeSetItem',
	);

	private static $defaults = array(
		'State' => 'open'
	);

	private static $has_one = array(
		'Owner' => 'Member',
	);

	private static $default_sort = '""Name" ASC';

	/**
	 * Default permission to require for publishers.
	 * Publishers must either be able to use the campaign admin, or have all admin access.
	 *
	 * Also used as default permission for ChangeSetItem default permission.
	 *
	 * @config
	 * @var array
	 */
	private static $required_permission = array('CMS_ACCESS_CampaignAdmin', 'CMS_ACCESS_LeftAndMain');

	/**
	 * Publish this changeset, then closes it.
	 *
	 * @throws Exception
	 */
	public function publish() {
		// Logical checks prior to publish
		if($this->State !== static::STATE_OPEN) {
			throw new BadMethodCallException(
				"ChangeSet can't be published if it has been already published or reverted."
			);
		}
		if(!$this->isSynced()) {
			throw new ValidationException(
				"ChangeSet does not include all necessary changes and cannot be published."
			);
		}
		if(!$this->canPublish()) {
			throw new Exception("The current member does not have permission to publish this ChangeSet.");
		}

		foreach($this->Changes() as $change) {
			/** @var ChangeSetItem $change */
			$change->publish();
		}

		$this->State = static::STATE_PUBLISHED;
		$this->write();
	}

	/** Revert all changes made to this changeset, then closes it. **/
	public function revert() {
		user_error('Not implemented', E_USER_ERROR);
	}

	/**
	 * Add a new change to this changeset. Will automatically include all owned
	 * changes as those are dependencies of this item.
	 *
	 * @param DataObject $object
	 */
	public function addObject(DataObject $object) {
		if(!$this->isInDB()) {
			throw new BadMethodCallException("ChangeSet must be saved before adding items");
		}

		$references = [
			'ObjectID'    => $object->ID,
			'ObjectClass' => $object->ClassName
		];

		// Get existing item in case already added
		$item = $this->Changes()->filter($references)->first();

		if (!$item) {
			$item = new ChangeSetItem($references);
			$this->Changes()->add($item);
		}

		$item->Added = ChangeSetItem::EXPLICITLY;
		$item->ReferencedBy = '';
		$item->write();

		$this->sync();
	}

	/**
	 * Remove an item from this changeset. Will automatically remove all changes
	 * which own (and thus depend on) the removed item.
	 *
	 * @param DataObject $object
	 */
	public function removeObject(DataObject $object) {
		$item = ChangeSetItem::get()->filter([
				'ObjectID' => $object->ID,
				'ObjectClass' => $object->ClassName,
				'ChangeSetID' => $this->ID
			])->first();

		if ($item) {
			// TODO: Handle case of implicit added item being removed.

			$item->delete();
		}

		$this->sync();
	}

	protected function implicitKey($item) {
		if ($item instanceof ChangeSetItem) return $item->ObjectClass.'.'.$item->ObjectID;
		return $item->ClassName.'.'.$item->ID;
	}

	protected function calculateImplicit() {
		/** @var string[][] $explicit List of all items that have been explicitly added to this ChangeSet */
		$explicit = array();

		/** @var string[][] $referenced List of all items that are "referenced" by items in $explicit */
		$referenced = array();

		/** @var string[][] $references List of which explicit items reference each thing in referenced */
		$references = array();

		foreach ($this->Changes()->filter(['Added' => ChangeSetItem::EXPLICITLY]) as $item) {
			$explicitKey = $this->implicitKey($item);
			$explicit[$explicitKey] = true;

			foreach ($item->findReferenced() as $referee) {
				$key = $this->implicitKey($referee);

				$referenced[$key] = [
					'ObjectID' => $referee->ID,
					'ObjectClass' => $referee->ClassName
				];

				$references[$key][] = $explicitKey;
			}
		}

		/** @var string[][] $explicit List of all items that are either in $explicit, $referenced or both */
		$all = array_merge($referenced, $explicit);

		/** @var string[][] $implicit Anything that is in $all, but not in $explicit, is an implicit inclusion */
		$implicit = array_diff_key($all, $explicit);

		foreach($implicit as $key => $object) {
			$referencedBy = $references[$key];
			sort($referencedBy);
			$implicit[$key]['ReferencedBy'] = implode(',', $referencedBy);
		}

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
		DB::get_conn()->withTransaction(function() {

			// Get the implicitly included items for this ChangeSet
			$implicit = $this->calculateImplicit();

			// Adjust the existing implicit ChangeSetItems for this ChangeSet
			foreach ($this->Changes()->filter(['Added' => ChangeSetItem::IMPLICITLY]) as $item) {
				$objectKey = $this->implicitKey($item);

				// If a ChangeSetItem exists, but isn't in $implicit, it's no longer required, so delete it
				if (!array_key_exists($objectKey, $implicit)) {
					$item->delete();
				}
				// Otherwise it is required, so update ReferencedBy and remove from $implicit
				else {
					$item->ReferencedBy = $implicit[$objectKey]['ReferencedBy'];
					$item->write();
					unset($implicit[$objectKey]);
				}
			}

			// Now $implicit is all those items that are implicitly included, but don't currently have a ChangeSetItem.
			// So create new ChangeSetItems to match

			foreach ($implicit as $key => $props) {
				$item = new ChangeSetItem($props);
				$item->Added = ChangeSetItem::IMPLICITLY;
				$item->ChangeSetID = $this->ID;
				$item->write();
			}
		});
	}

	/** Verify that any objects in this changeset include all owned changes */
	public function isSynced() {
		$implicit = $this->calculateImplicit();

		// Check the existing implicit ChangeSetItems for this ChangeSet

		foreach ($this->Changes()->filter(['Added' => ChangeSetItem::IMPLICITLY]) as $item) {
			$objectKey = $this->implicitKey($item);

			// If a ChangeSetItem exists, but isn't in $implicit -> validation failure
			if (!array_key_exists($objectKey, $implicit)) return false;
			// Exists, remove from $implicit
			unset($implicit[$objectKey]);
		}

		// If there's anything left in $implicit -> validation failure
		return empty($implicit);
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
	 * Check if this item is allowed to be published
	 *
	 * @param Member $member
	 * @return bool
	 */
	public function canPublish($member = null) {
		// All changes must be publishable
		foreach($this->Changes() as $change) {
			/** @var ChangeSetItem $change */
			if(!$change->canPublish($member)) {
				return false;
			}
		}

		// Default permission
		return $this->can(__FUNCTION__, $member);
	}

	/**
	 * Check if this changeset (if published) can be reverted
	 *
	 * @param Member $member
	 * @return bool
	 */
	public function canRevert($member = null) {
		// All changes must be publishable
		foreach($this->Changes() as $change) {
			/** @var ChangeSetItem $change */
			if(!$change->canRevert($member)) {
				return false;
			}
		}

		// Default permission
		return $this->can(__FUNCTION__, $member);
	}

	/**
	 * Default permissions for this changeset
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
		return (bool)Permission::checkMember($member, $this->config()->required_permission);
	}

	public function getCMSFields() {
		$fields = new FieldList();
		$fields->merge([
			TextField::create('Name'),
			ReadonlyField::create('State')
		]);
		$this->extend('updateCMSFields', $fields);
		return $fields;
	}
}
