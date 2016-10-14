<?php

namespace SilverStripe\ORM\Tests\ChangeSetTest;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\Security\Permission;

/**
 * Provides a set of targettable permissions for tested models
 *
 * @mixin Versioned
 * @mixin DataObject
 */
trait Permissions
{
	public function canEdit($member = null)
	{
		return $this->can(__FUNCTION__, $member);
	}

	public function canDelete($member = null)
	{
		return $this->can(__FUNCTION__, $member);
	}

	public function canCreate($member = null, $context = array())
	{
		return $this->can(__FUNCTION__, $member, $context);
	}

	public function canPublish($member = null, $context = array())
	{
		return $this->can(__FUNCTION__, $member, $context);
	}

	public function canUnpublish($member = null, $context = array())
	{
		return $this->can(__FUNCTION__, $member, $context);
	}

	public function can($perm, $member = null, $context = array())
	{
		$perms = [
			"PERM_{$perm}",
			'CAN_ALL',
		];
		return Permission::checkMember($member, $perms);
	}
}
