<?php

/**
 * This test class will focus on the when an search filter contains relational
 * component such as has_one, has_many, many_many, the {@link SearchFilter::applyRelation($query)}
 * will add the right "join" clauses to $query without the component parent
 * class missing from "join" chain.
 *
 * @package framework
 * @subpackage tests
 */
class SearchFilterApplyRelationTest extends SapphireTest {

	protected static $fixture_file = 'SearchFilterApplyRelationTest.yml';

	protected $extraDataObjects = array(
		'SearchFilterApplyRelationTest_DO',
		'SearchFilterApplyRelationTest_HasOneParent',
		'SearchFilterApplyRelationTest_HasOneChild',
		'SearchFilterApplyRelationTest_HasOneGrantChild',
		'SearchFilterApplyRelationTest_HasManyParent',
		'SearchFilterApplyRelationTest_HasManyChild',
		'SearchFilterApplyRelationTest_HasManyGrantChild',
		'SearchFilterApplyRelationTest_ManyManyParent',
		'SearchFilterApplyRelationTest_ManyManyChild',
		'SearchFilterApplyRelationTest_ManyManyGrantChild',
	);

	public function testApplyRelationHasOne(){

		$all = singleton("SearchFilterApplyRelationTest_DO");
		$context = $all->getDefaultSearchContext();

		$filter = new ExactMatchFilter("SearchFilterApplyRelationTest_HasOneGrantChild.Title");
		$context->setFilters(null);
		$context->addFilter($filter);
		$params = array(
			"Title" => "I am has_one object",
		);
		$results = $context->getResults($params);
		$this->assertEquals(2, $results->count());
	}

	public function testApplyRelationHasMany(){
		$do1 = $this->objFromFixture('SearchFilterApplyRelationTest_DO', 'do1');
		$do2 = $this->objFromFixture('SearchFilterApplyRelationTest_DO', 'do2');

		$all = singleton("SearchFilterApplyRelationTest_DO");
		$context = $all->getDefaultSearchContext();

		$filter = new PartialMatchFilter("SearchFilterApplyRelationTest_HasManyGrantChildren.Title");
		$context->setFilters(null);
		$context->addFilter($filter);
		$params = array(
			"SearchFilterApplyRelationTest_HasManyGrantChildren__Title" => "I am has_many object1",
		);
		$results = $context->getResults($params);
		$this->assertEquals(1, $results->count());
		$this->assertEquals(array($do1->ID), $results->column('ID'));

		$params = array(
			"SearchFilterApplyRelationTest_HasManyGrantChildren__Title" => "I am has_many object3",
		);
		$results = $context->getResults($params);
		$this->assertEquals(1, $results->count());
		$this->assertEquals(array($do2->ID), $results->column('ID'));

		$params = array(
			"SearchFilterApplyRelationTest_HasManyGrantChildren__Title" => "I am has_many object",
		);
		$results = $context->getResults($params);
		$this->assertEquals(2, $results->count());

		$params = array(
			"SearchFilterApplyRelationTest_HasManyGrantChildren__Title" => "not exist",
		);
		$results = $context->getResults($params);
		$this->assertEquals(0, $results->count());
	}

	public function testApplyRelationManyMany(){
		$all = singleton("SearchFilterApplyRelationTest_DO");
		$context = $all->getDefaultSearchContext();

		$filter = new PartialMatchFilter("ManyManyGrantChildren.Title");
		$context->setFilters(null);
		$context->addFilter($filter);
		$params = array(
			"ManyManyGrantChildren__Title" => "I am many_many object1",
		);
		$results = $context->getResults($params);
		$this->assertEquals(2, $results->count());

		$params = array(
			"ManyManyGrantChildren__Title" => "I am many_many object2",
		);
		$results = $context->getResults($params);
		$this->assertEquals(2, $results->count());

		$params = array(
			"ManyManyGrantChildren__Title" => "I am many_many object",
		);
		$results = $context->getResults($params);
		$this->assertEquals(2, $results->count());

		$params = array(
			"ManyManyGrantChildren__Title" => "not exist",
		);
		$results = $context->getResults($params);
		$this->assertEquals(0, $results->count());
	}
}

/**
 * @package framework
 * @subpackage tests
 */
class SearchFilterApplyRelationTest_DO extends DataObject implements TestOnly {

	private static $has_one = array(
		'SearchFilterApplyRelationTest_HasOneGrantChild' => 'SearchFilterApplyRelationTest_HasOneGrantChild'
	);

	private static $has_many = array(
		'SearchFilterApplyRelationTest_HasManyGrantChildren' => 'SearchFilterApplyRelationTest_HasManyGrantChild'
	);

	private static $many_many = array(
		'ManyManyGrantChildren' => 'SearchFilterApplyRelationTest_ManyManyGrantChild'
	);
}

/**
 * @package framework
 * @subpackage tests
 */
class SearchFilterApplyRelationTest_HasOneParent extends DataObject implements TestOnly {
	private static $db = array(
		"Title" => "Varchar"
	);
}

/**
 * @package framework
 * @subpackage tests
 */
class SearchFilterApplyRelationTest_HasOneChild extends SearchFilterApplyRelationTest_HasOneParent
		implements TestOnly {
	// This is to create an seperate Table only.
	private static $db = array(
		"ChildField" => "Varchar"
	);
}

/**
 * @package framework
 * @subpackage tests
 */
class SearchFilterApplyRelationTest_HasOneGrantChild extends SearchFilterApplyRelationTest_HasOneChild
		implements TestOnly {
	// This is to create an seperate Table only.
	private static $db = array(
		"GrantChildField" => "Varchar"
	);
	private static $has_many = array(
		"SearchFilterApplyRelationTest_DOs" => "SearchFilterApplyRelationTest_DO"
	);
}

/**
 * @package framework
 * @subpackage tests
 */
class SearchFilterApplyRelationTest_HasManyParent extends DataObject implements TestOnly {
	private static $db = array(
		"Title" => "Varchar"
	);
}

/**
 * @package framework
 * @subpackage tests
 */
class SearchFilterApplyRelationTest_HasManyChild extends SearchFilterApplyRelationTest_HasManyParent
		implements TestOnly {
	// This is to create an separate Table only.
	private static $db = array(
		"ChildField" => "Varchar"
	);
}

/**
 * @package framework
 * @subpackage tests
 */
class SearchFilterApplyRelationTest_HasManyGrantChild extends SearchFilterApplyRelationTest_HasManyChild{
	private static $has_one = array(
		"SearchFilterApplyRelationTest_DO" => "SearchFilterApplyRelationTest_DO"
	);
}

/**
 * @package framework
 * @subpackage tests
 */
class SearchFilterApplyRelationTest_ManyManyParent extends DataObject implements TestOnly{
	private static $db = array(
		"Title" => "Varchar"
	);
}

class SearchFilterApplyRelationTest_ManyManyChild extends SearchFilterApplyRelationTest_ManyManyParent
		implements TestOnly {
	// This is to create an seperate Table only.
	private static $db = array(
		"ChildField" => "Varchar"
	);
}

/**
 * @package framework
 * @subpackage tests
 */
class SearchFilterApplyRelationTest_ManyManyGrantChild extends SearchFilterApplyRelationTest_ManyManyChild
		implements TestOnly {
	// This is to create an seperate Table only.
	private static $db = array(
		"GrantChildField" => "Varchar"
	);
	private static $belongs_many_many = array(
		"DOs" => "SearchFilterApplyRelationTest_DO"
	);
}

