<?php

namespace SilverStripe\ORM\Tests\Filters;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Filters\ExactMatchFilter;
use SilverStripe\ORM\Filters\PartialMatchFilter;

/**
 * This test class will focus on the when an search filter contains relational
 * component such as has_one, has_many, many_many, the {@link SearchFilter::applyRelation($query)}
 * will add the right "join" clauses to $query without the component parent
 * class missing from "join" chain.
 */
class SearchFilterApplyRelationTest extends SapphireTest
{

    protected static $fixture_file = 'SearchFilterApplyRelationTest.yml';

    protected static $extra_dataobjects = array(
        SearchFilterApplyRelationTest\TestObject::class,
        SearchFilterApplyRelationTest\HasOneParent::class,
        SearchFilterApplyRelationTest\HasOneChild::class,
        SearchFilterApplyRelationTest\HasOneGrandChild::class,
        SearchFilterApplyRelationTest\HasManyParent::class,
        SearchFilterApplyRelationTest\HasManyChild::class,
        SearchFilterApplyRelationTest\HasManyGrandChild::class,
        SearchFilterApplyRelationTest\ManyManyParent::class,
        SearchFilterApplyRelationTest\ManyManyChild::class,
        SearchFilterApplyRelationTest\ManyManyGrandChild::class,
    );

    public function testApplyRelationHasOne()
    {

        $all = SearchFilterApplyRelationTest\TestObject::singleton();
        $context = $all->getDefaultSearchContext();

        $filter = new ExactMatchFilter("SearchFilterApplyRelationTest_HasOneGrandChild.Title");
        $context->setFilters(null);
        $context->addFilter($filter);
        $params = array(
            "Title" => "I am has_one object",
        );
        $results = $context->getResults($params);
        $this->assertEquals(2, $results->count());
    }

    public function testApplyRelationHasMany()
    {
        $do1 = $this->objFromFixture(SearchFilterApplyRelationTest\TestObject::class, 'do1');
        $do2 = $this->objFromFixture(SearchFilterApplyRelationTest\TestObject::class, 'do2');

        $all = SearchFilterApplyRelationTest\TestObject::singleton();
        $context = $all->getDefaultSearchContext();

        $filter = new PartialMatchFilter("SearchFilterApplyRelationTest_HasManyGrandChildren.Title");
        $context->setFilters(null);
        $context->addFilter($filter);
        $params = array(
            "SearchFilterApplyRelationTest_HasManyGrandChildren__Title" => "I am has_many object1",
        );
        $results = $context->getResults($params);
        $this->assertEquals(1, $results->count());
        $this->assertEquals(array($do1->ID), $results->column('ID'));

        $params = array(
            "SearchFilterApplyRelationTest_HasManyGrandChildren__Title" => "I am has_many object3",
        );
        $results = $context->getResults($params);
        $this->assertEquals(1, $results->count());
        $this->assertEquals(array($do2->ID), $results->column('ID'));

        $params = array(
            "SearchFilterApplyRelationTest_HasManyGrandChildren__Title" => "I am has_many object",
        );
        $results = $context->getResults($params);
        $this->assertEquals(2, $results->count());

        $params = array(
            "SearchFilterApplyRelationTest_HasManyGrandChildren__Title" => "not exist",
        );
        $results = $context->getResults($params);
        $this->assertEquals(0, $results->count());
    }

    public function testApplyRelationManyMany()
    {
        $all = SearchFilterApplyRelationTest\TestObject::singleton();
        $context = $all->getDefaultSearchContext();

        $filter = new PartialMatchFilter("ManyManyGrandChildren.Title");
        $context->setFilters(null);
        $context->addFilter($filter);
        $params = array(
            "ManyManyGrandChildren__Title" => "I am many_many object1",
        );
        $results = $context->getResults($params);
        $this->assertEquals(2, $results->count());

        $params = array(
            "ManyManyGrandChildren__Title" => "I am many_many object2",
        );
        $results = $context->getResults($params);
        $this->assertEquals(2, $results->count());

        $params = array(
            "ManyManyGrandChildren__Title" => "I am many_many object",
        );
        $results = $context->getResults($params);
        $this->assertEquals(2, $results->count());

        $params = array(
            "ManyManyGrandChildren__Title" => "not exist",
        );
        $results = $context->getResults($params);
        $this->assertEquals(0, $results->count());
    }
}
