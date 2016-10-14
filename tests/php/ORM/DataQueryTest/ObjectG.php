<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\Tests\DataQueryTest;

class ObjectG extends DataQueryTest\ObjectC implements TestOnly
{
	private static $table_name = 'DataQueryTest_G';

	private static $belongs_many_many = array(
		'ManyTestEs' => DataQueryTest\ObjectE::class,
	);

}
