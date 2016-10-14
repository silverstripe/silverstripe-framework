<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\Tests\DataQueryTest;

class ObjectE extends DataQueryTest\ObjectC implements TestOnly
{
	private static $table_name = 'DataQueryTest_E';

	private static $db = array(
		'SortOrder' => 'Int'
	);

	private static $many_many = array(
		'ManyTestGs' => ObjectG::class,
	);

	private static $default_sort = '"DataQueryTest_E"."SortOrder" ASC';
}
