<?php

namespace SilverStripe\Core\Tests\ClassInfoTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * @package framework
 * @subpackage tests
 */
class BaseDataClass extends DataObject implements TestOnly
{
	private static $table_name = 'ClassInfoTest_BaseDataClass';

	private static $db = array(
		'Title' => 'Varchar'
	);
}
