<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Tests\DataQueryTest;

class ObjectC extends DataObject implements TestOnly
{
	private static $table_name = 'DataQueryTest_C';

	private static $db = array(
		'Title' => 'Varchar'
	);

	private static $has_one = array(
		'TestA' => DataQueryTest\ObjectA::class,
		'TestB' => DataQueryTest\ObjectB::class,
	);

	private static $has_many = array(
		'TestAs' => DataQueryTest\ObjectA::class,
		'TestBs' => 'SilverStripe\\ORM\\Tests\\DataQueryTest\\ObjectB.TestC',
		'TestBsTwo' => 'SilverStripe\\ORM\\Tests\\DataQueryTest\\ObjectB.TestCTwo',
	);

	private static $many_many = array(
		'ManyTestAs' => DataQueryTest\ObjectA::class,
		'ManyTestBs' => DataQueryTest\ObjectB::class,
	);
}
