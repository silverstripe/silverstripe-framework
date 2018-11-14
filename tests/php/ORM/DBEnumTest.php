<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBEnum;
use SilverStripe\ORM\FieldType\DBField;

class DBEnumTest extends SapphireTest
{
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

    protected function setUp()
    {
        parent::setUp();
        DB::create_table('require_test', ['ID' => 'int(11)']);
    }

    protected function tearDown()
    {
        parent::tearDown();
        DB::query('drop table require_test');
    }

    public function testPlainRequireField()
    {
        DB::quiet();
        DB::get_schema()->schemaUpdate(function () {
            $enum = new DBEnum('testEnum', ['One', 'Two', 'Three'], 'Two');
            $enum->setTable('require_test');
            $enum->requireField();
        });


        DB::query('INSERT INTO require_test (ID) VALUE (1)');

        $val = DB::query('SELECT testEnum from require_test where ID = 1')->value();

        $this->assertEquals('Two', $val);
    }

    public function testSwitchToNewDefaultRequireField()
    {
        DB::quiet();

        $enum = new DBEnum('testEnum', ['One', 'Two', 'Three'], 'Two');
        $enum->setTable('require_test');
        DB::get_schema()->schemaUpdate(function () use ($enum) {
            $enum->requireField();
        });

        DB::query('INSERT INTO require_test (ID) VALUE (1)');

        $enum->setDefault('Three');
        DB::get_schema()->schemaUpdate(function () use ($enum) {
            $enum->requireField();
        });

        $val = DB::query('SELECT testEnum from require_test where ID = 1')->value();
        $this->assertEquals('Two', $val);

        DB::query('INSERT INTO require_test (ID) VALUE (2)');
        $val = DB::query('SELECT testEnum from require_test where ID = 2')->value();
        $this->assertEquals('Three', $val);
    }

    public function testRemoveEnumValueRequireField()
    {
        DB::quiet();

        $enum = new DBEnum('testEnum', ['One', 'Two', 'Three'], 'Two');
        $enum->setTable('require_test');
        DB::get_schema()->schemaUpdate(function () use ($enum) {
            $enum->requireField();
        });

        DB::prepared_query('INSERT INTO require_test (ID, testEnum) VALUE (1, ?)', ['One']);

        $enum->setEnum(['Two', 'Three']);
        $enum->setDefault('Three');
        DB::get_schema()->schemaUpdate(function () use ($enum) {
            $enum->requireField();
        });

        $val = DB::query('SELECT testEnum from require_test where ID = 1')->value();
        $this->assertEquals('Three', $val);

        DB::query('INSERT INTO require_test (ID) VALUE (2)');
        $val = DB::query('SELECT testEnum from require_test where ID = 2')->value();
        $this->assertEquals('Three', $val);
    }

    public function testRemoveDefaultEnumValueRequireField()
    {
        DB::quiet();

        $enum = new DBEnum('testEnum', ['One', 'Two', 'Three'], 'Two');
        $enum->setTable('require_test');
        DB::get_schema()->schemaUpdate(function () use ($enum) {
            $enum->requireField();
        });

        DB::query('INSERT INTO require_test (ID) VALUE (1)');

        $enum->setEnum(['One', 'Three']);
        $enum->setDefault('Three');
        DB::get_schema()->schemaUpdate(function () use ($enum) {
            $enum->requireField();
        });

        $val = DB::query('SELECT testEnum from require_test where ID = 1')->value();
        $this->assertEquals('Three', $val);

        DB::query('INSERT INTO require_test (ID) VALUE (2)');
        $val = DB::query('SELECT testEnum from require_test where ID = 2')->value();
        $this->assertEquals('Three', $val);
    }

    public function testMakeDefaultNullableRequireField()
    {
        DB::quiet();

        $enum = new DBEnum('testEnum', ['One', 'Two', 'Three'], 'Two');
        $enum->setTable('require_test');
        DB::get_schema()->schemaUpdate(function () use ($enum) {
            $enum->requireField();
        });

        DB::query('INSERT INTO require_test (ID) VALUE (1)');

        $enum->setEnum(['One', 'Three']);
        $enum->setDefault(null);
        DB::get_schema()->schemaUpdate(function () use ($enum) {
            $enum->requireField();
        });

        $val = DB::query('SELECT testEnum from require_test where ID = 1')->value();
        $this->assertEmpty($val);

        DB::query('INSERT INTO require_test (ID) VALUE (2)');
        $val = DB::query('SELECT testEnum from require_test where ID = 2')->value();
        $this->assertEmpty($val);
    }
}
