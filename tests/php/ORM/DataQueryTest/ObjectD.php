<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Tests\DataQueryTest;

class ObjectD extends DataObject implements TestOnly
{
	private static $table_name = 'DataQueryTest_D';

	private static $has_one = array(
		'Relation' => DataQueryTest\ObjectB::class,
	);
}
