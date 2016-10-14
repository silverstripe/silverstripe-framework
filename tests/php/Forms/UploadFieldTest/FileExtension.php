<?php

namespace SilverStripe\Forms\Tests\UploadFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

class FileExtension extends DataExtension implements TestOnly
{
	private static $has_one = array(
		'HasManyRecord' => TestRecord::class,
		'HasManyMaxTwoRecord' => TestRecord::class,
		'HasManyNoViewRecord' => TestRecord::class,
		'ReadonlyRecord' => TestRecord::class
	);

	private static $has_many = array(
		'HasOneRecords' => 'SilverStripe\Forms\Tests\UploadFieldTest\TestRecord.HasOneFile',
		'HasOneMaxOneRecords' => 'SilverStripe\Forms\Tests\UploadFieldTest\TestRecord.HasOneFileMaxOne',
		'HasOneMaxTwoRecords' => 'SilverStripe\Forms\Tests\UploadFieldTest\TestRecord.HasOneFileMaxTwo',
	);

	private static $belongs_many_many = array(
		'ManyManyRecords' => TestRecord::class
	);

	public function canDelete($member = null)
	{
		if ($this->owner->Name == 'nodelete.txt') {
			return false;
		}
	}

	public function canEdit($member = null)
	{
		if ($this->owner->Name == 'noedit.txt') {
			return false;
		}
	}

	public function canView($member = null)
	{
		if ($this->owner->Name == 'noview.txt') {
			return false;
		}
	}
}
