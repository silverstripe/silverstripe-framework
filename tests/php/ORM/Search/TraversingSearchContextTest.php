<?php

namespace SilverStripe\ORM\Tests\Search;

use SilverStripe\Dev\SapphireTest;

class TraversingSearchContextTest extends SapphireTest
{

    protected static $fixture_file = 'TraversingSearchContextTest.yml';

    protected static $extra_dataobjects = array(
        TraversingSearchContextTest\Student::class,
        TraversingSearchContextTest\Teacher::class,
        TraversingSearchContextTest\Competency::class,
    );

    // searchableFields() called on a singleton DataObject does not throw an exception for traversing search fields
    // while trying to find a default search filter type but returns the explicitly set filter
    public function testTraversingSearchableFields()
    {
        $student = singleton(TraversingSearchContextTest\Student::class);
        $fields = $student->searchableFields();
        $statics = TraversingSearchContextTest\Student::config()->get('searchable_fields');
        $this->assertEquals($statics, $fields);
    }

    public function testTraversingSearchContextSummary()
    {
        $student = singleton(TraversingSearchContextTest\Student::class);
        $context = $student->getDefaultSearchContext();
        $context->setSearchParams([
            'Name' => 'James',
            'Teachers__Name' => 'Susann',
            'Teachers__Competencies__Name' => 'Physics',
        ]);
        $summary = $context->getSummary()->toNestedArray();
        $this->assertEquals([
            [ 'Field' => 'Name', 'Value' => 'James' ],
            [ 'Field' => 'Teacher', 'Value' => 'Susann' ],
            [ 'Field' => 'Competency', 'Value' => 'Physics' ],
        ], $summary);
    }

    public function testTraversingSearchContextResults()
    {
        $student = singleton(TraversingSearchContextTest\Student::class);
        $context = $student->getDefaultSearchContext();
        $results = $context->getResults([
            'Teachers__Competencies__Name' => 'Physics',
        ]);
        $this->assertEquals(['James'], array_values($results->map()->toArray()));
    }
}
