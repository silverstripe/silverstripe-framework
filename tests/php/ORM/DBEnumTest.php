<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBEnum;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\DB;

class DBEnumTest extends SapphireTest
{

    protected $extraDataObjects = [
        FieldType\DBEnumTestObject::class,
    ];

    protected $usesDatabase = true;

    public function testDefault()
    {
        /** @var DBEnum $enum1 */
        $enum1 = DBField::create_field('Enum("A, B, C, D")', null);
        /** @var DBEnum $enum2 */
        $enum2 = DBField::create_field('Enum("A, B, C, D", "")', null);
        /** @var DBEnum $enum3 */
        $enum3 = DBField::create_field('Enum("A, B, C, D", null)', null);
        /** @var DBEnum $enum4 */
        $enum4 = DBField::create_field('Enum("A, B, C, D", 1)', null);

        $this->assertEquals('A', $enum1->getDefaultValue());
        $this->assertEquals('A', $enum1->getDefault());
        $this->assertEquals(null, $enum2->getDefaultValue());
        $this->assertEquals(null, $enum2->getDefault());
        $this->assertEquals(null, $enum3->getDefaultValue());
        $this->assertEquals(null, $enum3->getDefault());
        $this->assertEquals('B', $enum4->getDefaultValue());
        $this->assertEquals('B', $enum4->getDefault());
    }

    public function testObsoleteValues()
    {
        $obj = new FieldType\DBEnumTestObject();
        $colourField = $obj->obj('Colour');
        $colourField->setTable('FieldType_DBEnumTestObject');

        // Test values prior to any database content
        $this->assertEquals(
            ['Red', 'Blue', 'Green'],
            $colourField->getEnumObsolete()
        );

        // Test values with a record
        $obj->Colour = 'Red';
        $obj->write();
        DBEnum::flushCache();

        $this->assertEquals(
            ['Red', 'Blue', 'Green'],
            $colourField->getEnumObsolete()
        );

        // If the value is removed from the enum, obsolete content is still retained
        $colourField->setEnum(['Blue', 'Green', 'Purple']);
        DBEnum::flushCache();

        $this->assertEquals(
            ['Blue', 'Green', 'Purple', 'Red'], // Red on the end now, because it's obsolete
            $colourField->getEnumObsolete()
        );

        // Check that old and new data is preserved after a schema update
        DB::get_schema()->schemaUpdate(function () use ($colourField) {
            $colourField->requireField();
        });

        $obj2 = new FieldType\DBEnumTestObject();
        $obj2->Colour = 'Purple';
        $obj2->write();

        $this->assertEquals(
            ['Purple', 'Red'],
            FieldType\DBEnumTestObject::get()->sort('Colour')->column('Colour')
        );

        // Ensure that enum columns are retained
        $colourField->setEnum(['Blue', 'Green']);
        $this->assertEquals(
            ['Blue', 'Green', 'Purple', 'Red'],
            $colourField->getEnumObsolete()
        );

        // If obsolete records are deleted, the extra values go away
        $obj->delete();
        $obj2->delete();
        DBEnum::flushCache();
        $this->assertEquals(
            ['Blue', 'Green'],
            $colourField->getEnumObsolete()
        );
    }
}
