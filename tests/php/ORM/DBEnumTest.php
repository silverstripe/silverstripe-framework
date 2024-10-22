<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBEnum;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\DB;
use PHPUnit\Framework\Attributes\DataProvider;

class DBEnumTest extends SapphireTest
{

    protected $extraDataObjects = [
        FieldType\DBEnumTestObject::class,
    ];

    protected $usesDatabase = true;

    /**
     *
     * nullifyEmpty is an option on DBString, which DBEnum extends
     * Mainly used for testing that Enum short-array style syntax works while passing in options
     */
    #[DataProvider('provideParse')]
    public function testParse(?string $expectedDefault, bool $expectedNullifyEmpty, string $spec)
    {
        /** @var DBEnum $enum */
        $enum = DBField::create_field($spec, null);
        $this->assertEquals($expectedDefault, $enum->getDefaultValue());
        $this->assertEquals($expectedDefault, $enum->getDefault());
        $this->assertEquals($expectedNullifyEmpty, $enum->getNullifyEmpty());
    }

    public static function provideParse()
    {
        return [
            // standard syntax - double quotes
            ['A', true, 'Enum("A, B, C, D")'],
            ['B', true, 'Enum("A, B, C, D", "B")'],
            ['C', true, 'Enum("A, B, C, D", 2)'],
            [null, true, 'Enum("A, B, C, D", "")'],
            [null, true, 'Enum("A, B, C, D", null)'],
            ['B', false, 'Enum("A, B, C, D", "B", ["nullifyEmpty" => false])'],
            // standard syntax - single quotes
            ['A', true, "Enum('A, B, C, D')"],
            ['B', true, "Enum('A, B, C, D', 'B')"],
            ['C', true, "Enum('A, B, C, D', 2)"],
            [null, true, "Enum('A, B, C, D', '')"],
            [null, true, "Enum('A, B, C, D', null)"],
            ['B', false, "Enum('A, B, C, D', 'B', ['nullifyEmpty' => false])"],
            // long array syntax - double quotes
            ['A', true, 'Enum(array("A", "B", "C", "D"))'],
            ['B', true, 'Enum(array("A", "B", "C", "D"), "B")'],
            ['C', true, 'Enum(array("A", "B", "C", "D"), 2)'],
            [null, true, 'Enum(array("A", "B", "C", "D"), "")'],
            [null, true, 'Enum(array("A", "B", "C", "D"), null)'],
            ['B', false, 'Enum(array("A", "B", "C", "D"), "B", ["nullifyEmpty" => false])'],
            // long array syntax - single quotes
            ['A', true, "Enum(array('A', 'B', 'C', 'D'))"],
            ['B', true, "Enum(array('A', 'B', 'C', 'D'), 'B')"],
            ['C', true, "Enum(array('A', 'B', 'C', 'D'), 2)"],
            [null, true, "Enum(array('A', 'B', 'C', 'D'), '')"],
            [null, true, "Enum(array('A', 'B', 'C', 'D'), null)"],
            ['B', false, "Enum(array('A', 'B', 'C', 'D'), 'B', ['nullifyEmpty' => false])"],
            // short array syntax - double quotes
            ['A', true, 'Enum(["A", "B", "C", "D"])'],
            ['B', true, 'Enum(["A", "B", "C", "D"], "B")'],
            ['C', true, 'Enum(["A", "B", "C", "D"], 2)'],
            [null, true, 'Enum(["A", "B", "C", "D"], "")'],
            [null, true, 'Enum(["A", "B", "C", "D"], null)'],
            ['B', false, 'Enum(["A", "B", "C", "D"], "B", ["nullifyEmpty" => false])'],
            // short array syntax - single quotes
            ['A', true, "Enum(['A', 'B', 'C', 'D'])"],
            ['B', true, "Enum(['A', 'B', 'C', 'D'], 'B')"],
            ['C', true, "Enum(['A', 'B', 'C', 'D'], 2)"],
            [null, true, "Enum(['A', 'B', 'C', 'D'], '')"],
            [null, true, "Enum(['A', 'B', 'C', 'D'], null)"],
            ['B', false, "Enum(['A', 'B', 'C', 'D'], 'B', ['nullifyEmpty' => false])"],
        ];
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
        DBEnum::clearStaticCache();

        $this->assertEquals(
            ['Red', 'Blue', 'Green'],
            $colourField->getEnumObsolete()
        );

        // If the value is removed from the enum, obsolete content is still retained
        $colourField->setEnum(['Blue', 'Green', 'Purple']);
        DBEnum::clearStaticCache();

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
        DBEnum::clearStaticCache();
        $this->assertEquals(
            ['Blue', 'Green'],
            $colourField->getEnumObsolete()
        );
    }
}
