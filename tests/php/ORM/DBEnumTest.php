<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBEnum;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\Queries\SQLInsert;
use SilverStripe\ORM\Queries\SQLSelect;

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
        DB::create_table('require_test', ['ID' => 'int']);
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

        SQLInsert::create('require_test', ['"ID"' => 1])->execute();

        $val = SQLSelect::create('"testEnum"', 'require_test', ['"ID"' => 1])->execute()->value();

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

        SQLInsert::create('require_test', ['"ID"' => 1])->execute();

        $enum->setDefault('Three');
        DB::get_schema()->schemaUpdate(function () use ($enum) {
            $enum->requireField();
        });

        $val = SQLSelect::create('"testEnum"', 'require_test', ['"ID"' => 1])->execute()->value();
        $this->assertEquals('Two', $val);

        SQLInsert::create('require_test', ['"ID"' => 2])->execute();
        $val = SQLSelect::create('"testEnum"', 'require_test', ['"ID"' => 2])->execute()->value();
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

        SQLInsert::create('require_test', ['"ID"' => 1, '"testEnum"' => 'One'])->execute();

        $enum->setEnum(['Two', 'Three']);
        $enum->setDefault('Three');
        DB::get_schema()->schemaUpdate(function () use ($enum) {
            $enum->requireField();
        });

        $val = SQLSelect::create('"testEnum"', 'require_test', ['"ID"' => 1])->execute()->value();
        $this->assertEquals('Three', $val);

        SQLInsert::create('require_test', ['"ID"' => 2])->execute();
        $val = SQLSelect::create('"testEnum"', 'require_test', ['"ID"' => 2])->execute()->value();
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

        SQLInsert::create('require_test', ['"ID"' => 1])->execute();

        $enum->setEnum(['One', 'Three']);
        $enum->setDefault('Three');
        DB::get_schema()->schemaUpdate(function () use ($enum) {
            $enum->requireField();
        });

        $val = SQLSelect::create('"testEnum"', 'require_test', ['"ID"' => 1])->execute()->value();
        $this->assertEquals('Three', $val);

        SQLInsert::create('require_test', ['"ID"' => 2])->execute();
        $val = SQLSelect::create('"testEnum"', 'require_test', ['"ID"' => 2])->execute()->value();
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

        SQLInsert::create('require_test', ['"ID"' => 1])->execute();

        $enum->setEnum(['One', 'Three']);
        $enum->setDefault(null);
        DB::get_schema()->schemaUpdate(function () use ($enum) {
            $enum->requireField();
        });

        $val = SQLSelect::create('"testEnum"', 'require_test', ['"ID"' => 1])->execute()->value();
        $this->assertEmpty($val);

        SQLInsert::create('require_test', ['"ID"' => 2])->execute();
        $val = SQLSelect::create('"testEnum"', 'require_test', ['"ID"' => 2])->execute()->value();
        $this->assertEmpty($val);
    }
}
