<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBClassNameVarchar;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\ORM\Tests\DataObjectSchemaTest\HasFields;

/**
 * These unit tests test will change DBClassName to a varchar column
 * and then test that the tests in DataObjectSchemaTest still pass
 *
 * There's also a test that a ClassName of an arbitary DataObject is a Varchar
 */
class DBClassNameVarcharTest extends DataObjectSchemaTest
{
    public function setup(): void
    {
        parent::setup();
        $fixedFields = Config::inst()->get(DataObject::class, 'fixed_fields');
        $fixedFields['ClassName'] = 'DBClassNameVarchar';
        Config::modify()->set(DataObject::class, 'fixed_fields', $fixedFields);
    }

    public function testVarcharType(): void
    {
        /** @var DataObject $obj */
        $obj = HasFields::create();
        $class = get_class($obj->dbObject('ClassName'));
        $this->assertSame(DBClassNameVarchar::class, $class);
        $this->assertTrue(is_a($class, DBVarchar::class, true));
    }
}
