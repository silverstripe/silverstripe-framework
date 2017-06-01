<?php

namespace SilverStripe\ORM\Tests\Filters;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Filters\FulltextFilter;
use SilverStripe\ORM\Tests\Filters\FulltextFilterTest\TestObject;

class FulltextFilterTest extends SapphireTest
{

    protected static $extra_dataobjects = array(
        TestObject::class
    );

    protected static $fixture_file = "FulltextFilterTest.yml";

    public function testFilter()
    {
        if (DB::get_conn() instanceof MySQLDatabase) {
            $baseQuery = FulltextFilterTest\TestObject::get();
            $this->assertEquals(3, $baseQuery->count(), "FulltextFilterTest_DataObject count does not match.");

            // First we'll text the 'SearchFields' which has been set using an array
            $search = $baseQuery->filter("SearchFields:Fulltext", 'SilverStripe');
            $this->assertEquals(1, $search->count());

            $search = $baseQuery->exclude("SearchFields:Fulltext", "SilverStripe");
            $this->assertEquals(2, $search->count());

            // Now we'll run the same tests on 'OtherSearchFields' which should yield the same resutls
            // but has been set using a string.
            $search = $baseQuery->filter("OtherSearchFields:Fulltext", 'SilverStripe');
            $this->assertEquals(1, $search->count());

            $search = $baseQuery->exclude("OtherSearchFields:Fulltext", "SilverStripe");
            $this->assertEquals(2, $search->count());

            // Search on a single field
            $search = $baseQuery->filter("ColumnE:Fulltext", 'Dragons');
            $this->assertEquals(1, $search->count());

            $search = $baseQuery->exclude("ColumnE:Fulltext", "Dragons");
            $this->assertEquals(2, $search->count());
        } else {
            $this->markTestSkipped("FulltextFilter only supports MySQL syntax.");
        }
    }

    public function testGenerateQuery()
    {
        // Test if columns have table identifier
        $filter1 = new FulltextFilter('SearchFields', 'SilverStripe');
        $filter1->setModel(TestObject::class);
        $query1 = FulltextFilterTest\TestObject::get()->dataQuery();
        $filter1->apply($query1);
        $this->assertNotEquals(sprintf('%s,%s', Convert::symbol2sql('ColumnA'),Convert::symbol2sql('ColumnB')), $filter1->getDbName());
        $this->assertNotEquals(
            array(sprintf('MATCH (%s,%s) AGAINST (%s)")',
                Convert::symbol2sql('ColumnA'),
                Convert::symbol2sql('ColumnB'),
                Convert::raw2sql('SilverStripe')
            )),
            $query1->query()->getWhere()
        );

        // Test SearchFields
        $filter1 = new FulltextFilter('SearchFields', 'SilverStripe');
        $filter1->setModel(TestObject::class);
        $query1 = FulltextFilterTest\TestObject::get()->dataQuery();
        $filter1->apply($query1);
        $this->assertEquals(
            sprintf('%s,%s',
                Convert::symbol2sql('FulltextFilterTest_DataObject.ColumnA'),
                Convert::symbol2sql('FulltextFilterTest_DataObject.ColumnB')
            ), $filter1->getDbName()
        );
        $this->assertEquals(
            array(array(
                sprintf(
                    'MATCH (%s,%s) AGAINST (?)',
                    Convert::symbol2sql('FulltextFilterTest_DataObject.ColumnA'),
                    Convert::symbol2sql('FulltextFilterTest_DataObject.ColumnB')
                ) => array('SilverStripe'),
            )),
            $query1->query()->getWhere()
        );

        // Test Other searchfields
        $filter2 = new FulltextFilter('OtherSearchFields', 'SilverStripe');
        $filter2->setModel(TestObject::class);
        $query2 = FulltextFilterTest\TestObject::get()->dataQuery();
        $filter2->apply($query2);
        $this->assertEquals(sprintf(
            '%s,%s',
            Convert::symbol2sql('FulltextFilterTest_DataObject.ColumnC'),
            Convert::symbol2sql('FulltextFilterTest_DataObject.ColumnD')
        ), $filter2->getDbName());
        $this->assertEquals(
            array(array(
                  sprintf(
                      'MATCH (%s,%s) AGAINST (?)',
                      Convert::symbol2sql('FulltextFilterTest_DataObject.ColumnC'),
                      Convert::symbol2sql('FulltextFilterTest_DataObject.ColumnD')
                  ) => array('SilverStripe')
            )),
            $query2->query()->getWhere()
        );

        // Test fallback to single field
        $filter3 = new FulltextFilter('ColumnA', 'SilverStripe');
        $filter3->setModel(TestObject::class);
        $query3 = FulltextFilterTest\TestObject::get()->dataQuery();
        $filter3->apply($query3);
        $this->assertEquals(Convert::symbol2sql('FulltextFilterTest_DataObject.ColumnA'), $filter3->getDbName());
        $this->assertEquals(
            array(array(
                sprintf(
                    "MATCH (%s) AGAINST (?)",
                    Convert::symbol2sql('FulltextFilterTest_DataObject.ColumnA')
                ) => array('SilverStripe')
            )),
            $query3->query()->getWhere()
        );
    }
}
