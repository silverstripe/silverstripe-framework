<?php
/**
 * This test class will focus on the when an search filter contains relational component,
 * such as has_one, has_many, many_many, the SearchFilter::applyRelation($query) will add
 * the right "join" clauses to $query without the component parent class missing from
 * "join" chain.
 * 
 * @package sapphire
 * @subpackage testing
 */
class SearchFilterApplyRelationTest extends SapphireTest{
	static $fixture_file = 'sapphire/tests/search/SearchFilterApplyRelationTest.yml';
	
	
	function testApplyRelationHasOne(){
		
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
	
	function testApplyRelationHasMany(){
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
		$this->assertEquals(array('1'), $results->column('ID'));
		
		$params = array(
			"SearchFilterApplyRelationTest_HasManyGrantChildren__Title" => "I am has_many object3",
		);
		$results = $context->getResults($params);
		$this->assertEquals(1, $results->count());
		$this->assertEquals(array('2'), $results->column('ID'));
		
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
	
	function testApplyRelationManyMany(){
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

class SearchFilterApplyRelationTest_DO extends DataObject implements TestOnly{
	static $has_one = array(
		'SearchFilterApplyRelationTest_HasOneGrantChild' => 'SearchFilterApplyRelationTest_HasOneGrantChild'
	);
	
	static $has_many = array(
		'SearchFilterApplyRelationTest_HasManyGrantChildren' => 'SearchFilterApplyRelationTest_HasManyGrantChild'
	);
	
	static $many_many = array(
		'ManyManyGrantChildren' => 'SearchFilterApplyRelationTest_ManyManyGrantChild'
	);
}

class SearchFilterApplyRelationTest_HasOneParent extends DataObject implements TestOnly{
	static $db = array(
		"Title" => "Varchar"
	);
}

class SearchFilterApplyRelationTest_HasOneChild extends SearchFilterApplyRelationTest_HasOneParent{
	// This is to create an seperate Table only.
	static $db = array(
		"ChildField" => "Varchar"
	);
}

class SearchFilterApplyRelationTest_HasOneGrantChild extends SearchFilterApplyRelationTest_HasOneChild{
	// This is to create an seperate Table only.
	static $db = array(
		"GrantChildField" => "Varchar"
	);
	static $has_many = array(
		"SearchFilterApplyRelationTest_DOs" => "SearchFilterApplyRelationTest_DO"
	);
}

class SearchFilterApplyRelationTest_HasManyParent extends DataObject implements TestOnly{
	static $db = array(
		"Title" => "Varchar"
	);
}

class SearchFilterApplyRelationTest_HasManyChild extends SearchFilterApplyRelationTest_HasManyParent{
	// This is to create an seperate Table only.
	static $db = array(
		"ChildField" => "Varchar"
	);
}

class SearchFilterApplyRelationTest_HasManyGrantChild extends SearchFilterApplyRelationTest_HasManyChild{
	static $has_one = array(
		"SearchFilterApplyRelationTest_DO" => "SearchFilterApplyRelationTest_DO"
	);
}

class SearchFilterApplyRelationTest_ManyManyParent extends DataObject implements TestOnly{
	static $db = array(
		"Title" => "Varchar"
	);
}

class SearchFilterApplyRelationTest_ManyManyChild extends SearchFilterApplyRelationTest_ManyManyParent{
	// This is to create an seperate Table only.
	static $db = array(
		"ChildField" => "Varchar"
	);
}

class SearchFilterApplyRelationTest_ManyManyGrantChild extends SearchFilterApplyRelationTest_ManyManyChild{
	// This is to create an seperate Table only.
	static $db = array(
		"GrantChildField" => "Varchar"
	);
	static $belongs_many_many = array(
		"DOs" => "SearchFilterApplyRelationTest_DO"
	);
}

?>