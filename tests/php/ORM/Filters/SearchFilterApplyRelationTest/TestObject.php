<?php

namespace SilverStripe\ORM\Tests\Filters\SearchFilterApplyRelationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
	private static $table_name = 'SearchFilterApplyRelationTest_DO';

	private static $has_one = array(
		'SearchFilterApplyRelationTest_HasOneGrantChild' => HasOneGrandChild::class
	);

	private static $has_many = array(
		'SearchFilterApplyRelationTest_HasManyGrantChildren' => HasManyGrandChild::class
	);

	private static $many_many = array(
		'ManyManyGrantChildren' => ManyManyGrandChild::class
	);
}
