<?php

namespace SilverStripe\Forms\Tests\UploadFieldTest;

use SilverStripe\Assets\File;
use SilverStripe\Dev\TestOnly;

/**
 * Used for testing the create-on-upload
 */
class ExtendedFile extends File implements TestOnly
{

	private static $has_many = array(
		'HasOneExtendedRecords' => 'UploadFieldTest_Record.HasOneExtendedFile'
	);
}
