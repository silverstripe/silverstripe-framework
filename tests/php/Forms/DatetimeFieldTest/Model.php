<?php

namespace SilverStripe\Forms\Tests\DatetimeFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * @package framework
 * @subpackage tests
 */
class Model extends DataObject implements TestOnly
{
	private static $table_name = 'DatetimeFieldTest_Model';

	private static $db = array(
		'MyDatetime' => 'Datetime'
	);

}
