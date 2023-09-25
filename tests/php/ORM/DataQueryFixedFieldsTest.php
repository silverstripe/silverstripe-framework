<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataQuery;

class DataQueryFixedFieldsTest extends SapphireTest
{
    protected static $fixture_file = 'DataQueryFixedFieldsTest.yml';

    protected static $extra_dataobjects = [
        DataQueryTest\ObjectA::class,
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        DataObject::config()->merge('fixed_fields', ['ExtraFixedField' => 'Varchar']);
        static::tempDB()->resetDBSchema(static::$extra_dataobjects);
    }

    public function testDataQueryHasFixedFields()
    {
        $dataQuery = new DataQuery(DataQueryTest\ObjectA::class);
        $dataQuery->setQueriedColumns(['Name']);
        $this->assertSame(['This is the field'], $dataQuery->execute()->column('ExtraFixedField'));
    }
}
