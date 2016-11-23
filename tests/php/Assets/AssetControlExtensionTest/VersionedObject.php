<?php

namespace SilverStripe\Assets\Tests\AssetControlExtensionTest;

use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;
use SilverStripe\Security\Member;

/**
 * Versioned object with attached assets
 *
 * @property string $Title
 * @property DBFile $Header
 * @property DBFile $Download
 * @mixin Versioned
 */
class VersionedObject extends DataObject implements TestOnly
{
	private static $extensions = array(
		Versioned::class
	);

	private static $db = array(
		'Title' => 'Varchar(255)',
		'Header' => "DBFile('image/supported')",
		'Download' => 'DBFile'
	);

	private static $table_name = 'AssetControlExtensionTest_VersionedObject';

	/**
	 * @param Member $member
	 * @return bool
	 */
	public function canView($member = null)
	{
		if (!$member) {
			$member = Member::currentUser();
		}

		// Expectation that versioned::canView will hide this object in draft
		$result = $this->extendedCan('canView', $member);
		if ($result !== null) {
			return $result;
		}

		// Open to public
		return true;
	}
}
