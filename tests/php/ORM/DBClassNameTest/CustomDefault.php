<?php

namespace SilverStripe\ORM\Tests\DBClassNameTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class CustomDefault extends DataObject implements TestOnly
{
	private static $default_classname = 'DBClassNameTest_CustomDefaultSubclass';

	private static $db = array(
		'Title' => 'Varchar'
	);
}
