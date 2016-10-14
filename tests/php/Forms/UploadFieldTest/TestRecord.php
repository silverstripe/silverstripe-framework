<?php

namespace SilverStripe\Forms\Tests\UploadFieldTest;

use SilverStripe\Assets\File;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestRecord extends DataObject implements TestOnly
{
	private static $table_name = 'UploadFieldTest_Record';

	private static $db = array(
		'Title' => 'Text',
	);

	private static $has_one = array(
		'HasOneFile' => File::class,
		'HasOneFileMaxOne' => File::class,
		'HasOneFileMaxTwo' => File::class,
		'HasOneExtendedFile' => ExtendedFile::class
	);

	private static $has_many = array(
		'HasManyFiles' => 'SilverStripe\\Assets\\File.HasManyRecord',
		'HasManyFilesMaxTwo' => 'SilverStripe\\Assets\\File.HasManyMaxTwoRecord',
		'HasManyNoViewFiles' => 'SilverStripe\\Assets\\File.HasManyNoViewRecord',
		'ReadonlyField' => 'SilverStripe\\Assets\\File.ReadonlyRecord'
	);

	private static $many_many = array(
		'ManyManyFiles' => File::class
	);

}
