<?php

namespace SilverStripe\ORM\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\Connect\MySQLSchemaManager;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Tests\MySQLSchemaManagerTest\MySQLDBDummy;

class MySQLSchemaManagerTest extends SapphireTest
{
    public function testMYSQL_8_0_16()
    {
        Config::forClass(MySQLSchemaManager::class)->set('schema_use_int_width', null);

        $mysqlDBdummy = new MySQLDBDummy('8.0.16-standard');
        $mgr = new MySQLSchemaManager();
        $mgr->setDatabase($mysqlDBdummy);

        $this->assertEquals(
            'tinyint(1) unsigned not null',
            $mgr->boolean([]),
            'mysql 8.0.16 boolean has width'
        );

        $this->assertEquals(
            'int(11) not null',
            $mgr->int([]),
            'mysql 8.0.16 int has width'
        );

        $this->assertEquals(
            'bigint(20) not null',
            $mgr->bigint([]),
            'mysql 8.0.16 bigint has width'
        );

        $this->assertEquals(
            'int(11) not null auto_increment',
            $mgr->IdColumn([]),
            'mysql 8.0.16 IdColumn has width'
        );
    }

    public function testMYSQL_8_0_17()
    {
        Config::forClass(MySQLSchemaManager::class)->set('schema_use_int_width', null);

        $mysqlDBdummy = new MySQLDBDummy('8.0.17');
        $mgr = new MySQLSchemaManager();
        $mgr->setDatabase($mysqlDBdummy);

        $this->assertEquals(
            'tinyint unsigned not null',
            $mgr->boolean([]),
            'mysql 8.0.17 boolean has no width'
        );

        $this->assertEquals(
            'int not null',
            $mgr->int([]),
            'mysql 8.0.17 int has no width'
        );

        $this->assertEquals(
            'bigint not null',
            $mgr->bigint([]),
            'mysql 8.0.17 bigint has no width'
        );

        $this->assertEquals(
            'int not null auto_increment',
            $mgr->IdColumn([]),
            'mysql 8.0.17 IdColumn has no width'
        );
    }

    public function testMariaDB()
    {
        Config::forClass(MySQLSchemaManager::class)->set('schema_use_int_width', null);

        $mariaDBdummy = new MySQLDBDummy('10.4.7-MariaDB');
        $mgr = new MySQLSchemaManager();
        $mgr->setDatabase($mariaDBdummy);

        $this->assertEquals(
            'tinyint(1) unsigned not null',
            $mgr->boolean([]),
            'mariadb boolean has width'
        );

        $this->assertEquals(
            'int(11) not null',
            $mgr->int([]),
            'mariadb int has width'
        );

        $this->assertEquals(
            'bigint(20) not null',
            $mgr->bigint([]),
            'mariadb bigint has width'
        );

        $this->assertEquals(
            'int(11) not null auto_increment',
            $mgr->IdColumn([]),
            'mariadb IdColumn has width'
        );
    }

    public function testMySQLForcedON()
    {
        Config::forClass(MySQLSchemaManager::class)->set('schema_use_int_width', true);

        $mysqlDBdummy = new MySQLDBDummy('8.0.17-standard');
        $mgr = new MySQLSchemaManager();
        $mgr->setDatabase($mysqlDBdummy);

        $this->assertEquals(
            'tinyint(1) unsigned not null',
            $mgr->boolean([]),
            'mysql 8.0.17 boolean forced on has width'
        );

        $this->assertEquals(
            'int(11) not null',
            $mgr->int([]),
            'mysql 8.0.17 int forced on has width'
        );

        $this->assertEquals(
            'bigint(20) not null',
            $mgr->bigint([]),
            'mysql 8.0.17 bigint forced on has width'
        );

        $this->assertEquals(
            'int(11) not null auto_increment',
            $mgr->IdColumn([]),
            'mysql 8.0.17 IdColumn forced on has width'
        );
    }

    public function testMySQLForcedOFF()
    {
        Config::forClass(MySQLSchemaManager::class)->set('schema_use_int_width', false);

        $mysqlDBdummy = new MySQLDBDummy('8.0.16-standard');
        $mgr = new MySQLSchemaManager();
        $mgr->setDatabase($mysqlDBdummy);

        $this->assertEquals(
            'tinyint unsigned not null',
            $mgr->boolean([]),
            'mysql 8.0.16 boolean forced off has no width'
        );

        $this->assertEquals(
            'int not null',
            $mgr->int([]),
            'mysql 8.0.16 int forced off has no width'
        );

        $this->assertEquals(
            'bigint not null',
            $mgr->bigint([]),
            'mysql 8.0.16 bigint forced off has no width'
        );

        $this->assertEquals(
            'int not null auto_increment',
            $mgr->IdColumn([]),
            'mysql 8.0.16 IdColumn forced off has no width'
        );
    }

    public function testMariaDBForcedOFF()
    {
        Config::forClass(MySQLSchemaManager::class)->set('schema_use_int_width', false);

        $mysqlDBdummy = new MySQLDBDummy('10.0.1-MariaDB');
        $mgr = new MySQLSchemaManager();
        $mgr->setDatabase($mysqlDBdummy);

        $this->assertEquals(
            'tinyint unsigned not null',
            $mgr->boolean([]),
            'mariadb boolean forced off has no width'
        );

        $this->assertEquals(
            'int not null',
            $mgr->int([]),
            'mariadb int forced off has no width'
        );

        $this->assertEquals(
            'bigint not null',
            $mgr->bigint([]),
            'mariadb bigint forced off has no width'
        );

        $this->assertEquals(
            'int not null auto_increment',
            $mgr->IdColumn([]),
            'mariadb IdColumn forced off has no width'
        );
    }

    public static function provideNeedRebuildColumn(): array
    {
        return [
            'normal column matching' => [
                'existingSpec' => 'tinyint 1 unsigned not null default 0',
                'newSpec' => 'tinyint 1 unsigned not null default 0',
                'expected' => false,
            ],
            'normal column not matching' => [
                'existingSpec' => 'varchar 255 default \'oogabooga\'',
                'newSpec' => 'tinyint 1 unsigned not null default 0',
                'expected' => false,
            ],
            'generated column matching' => [
                'existingSpec' => 'double AS ("Price" * 0.25) STORED',
                'newSpec' => 'double AS ("Price" * 0.25) STORED',
                'expected' => false,
            ],
            'generated column different expression' => [
                'existingSpec' => 'double AS ("Price" * "Discount") STORED',
                'newSpec' => 'double AS ("Price" * 0.25) STORED',
                'expected' => false,
            ],
            'generated column different datatype' => [
                'existingSpec' => 'varchar 255 AS ("Price" * 0.25) STORED',
                'newSpec' => 'double AS ("Price" * 0.25) STORED',
                'expected' => false,
            ],
            'generated column stored to virtual' => [
                'existingSpec' => 'double AS ("Price" * 0.25) STORED',
                'newSpec' => 'double AS ("Price" * 0.25) VIRTUAL',
                'expected' => true,
            ],
            'generated column virtual to stored' => [
                'existingSpec' => 'double AS ("Price" * 0.25) VIRTUAL',
                'newSpec' => 'double AS ("Price" * 0.25) STORED',
                'expected' => true,
            ],
            'generated column stored to non-generated' => [
                'existingSpec' => 'double AS ("Price" * 0.25) STORED',
                'newSpec' => 'double',
                'expected' => false,
            ],
            'generated column virtual to non-generated' => [
                'existingSpec' => 'double AS ("Price" * 0.25) VIRTUAL',
                'newSpec' => 'double',
                'expected' => true,
            ],
            'normal column to stored generated' => [
                'existingSpec' => 'varchar 255 default \'oogabooga\'',
                'newSpec' => 'varchar 255 AS (CONCAT("FirstName", "LastName")) STORED',
                'expected' => false,
            ],
            'normal column to virtual generated' => [
                'existingSpec' => 'varchar 255 default \'oogabooga\'',
                'newSpec' => 'varchar 255 AS (CONCAT("FirstName", "LastName")) VIRTUAL',
                'expected' => true,
            ],
        ];
    }

    #[DataProvider('provideNeedRebuildColumn')]
    public function testNeedRebuildColumn(string $existingSpec, string $newSpec, bool $expected): void
    {
        $manager = new MySQLSchemaManager();
        $reflectionMethod = new ReflectionMethod($manager, 'needRebuildColumn');
        $this->assertSame($expected, $reflectionMethod->invoke($manager, $existingSpec, $newSpec));
    }

    public static function provideNormaliseGeneratedColumnExpression(): array
    {
        return [
            'expression with no thrills' => [
                'expression' => 'CASE WHEN "Surname" IS NULL THEN \'\' ELSE "Surname" END',
                'expected' => 'CASE WHEN "Surname" IS NULL THEN \'\' ELSE "Surname" END',
            ],
            'backtick quotes' => [
                'expression' => 'CASE WHEN `Surname` IS NULL THEN \'\' ELSE `Surname` END',
                'expected' => 'CASE WHEN "Surname" IS NULL THEN \'\' ELSE "Surname" END',
            ],
            'no quotes at all' => [
                'expression' => 'CASE WHEN Surname IS NULL THEN \'\' ELSE Surname END',
                'expected' => 'CASE WHEN "Surname" IS NULL THEN \'\' ELSE "Surname" END',
            ],
            'explicitcharset before string matches default' => [
                'expression' => 'CASE WHEN "Surname" IS NULL THEN _utf8mb4\'\' ELSE "Surname" END',
                'expected' => 'CASE WHEN "Surname" IS NULL THEN \'\' ELSE "Surname" END',
            ],
            'explicitcharset before string DOESNT match default' => [
                'expression' => 'CASE WHEN "Surname" IS NULL THEN _utf8\'\' ELSE "Surname" END',
                'expected' => 'CASE WHEN "Surname" IS NULL THEN _utf8 \'\' ELSE "Surname" END',
            ],
            'mixed case reserved keywords' => [
                'expression' => 'CASE when "Surname" IS nUlL THEN \'\' ElSe "Surname" end',
                'expected' => 'CASE WHEN "Surname" IS NULL THEN \'\' ELSE "Surname" END',
            ],
            'unnecessary brackets around when' => [
                'expression' => 'CASE WHEN ("Surname" IS NULL) THEN \'\' ELSE "Surname" END',
                'expected' => 'CASE WHEN "Surname" IS NULL THEN \'\' ELSE "Surname" END',
            ],
            'unnecessary brackets around case' => [
                'expression' => '(CASE WHEN "Surname" IS NULL THEN \'\' ELSE "Surname" END)',
                'expected' => 'CASE WHEN "Surname" IS NULL THEN \'\' ELSE "Surname" END',
            ],
            'quotes that actually matter are not touched' => [
                'expression' => '2 * ((1 + 3) / 4)',
                'expected' => '2 * ((1 + 3) / 4)',
            ],
            // If more scenarios are added above, make sure they're represented in this one below.
            // This scenario includes all of the above and ensures the logic works as expected in a complex expression.
            'all the trimmings' => [
                'expression' => '(trim(concat((2 * ((1 + 3) / 4)), `FirstName`,_utf8mb4\' \',(case when ("Surname" IS null) then _utf8\'\' ELSE Surname end))))',
                'expected' => 'TRIM(CONCAT((2 * ((1 + 3) / 4)),"FirstName",\' \',CASE WHEN "Surname" IS NULL THEN _utf8 \'\' ELSE "Surname" END))',
            ],
        ];
    }

    #[DataProvider('provideNormaliseGeneratedColumnExpression')]
    public function testNormaliseGeneratedColumnExpression(string $expression, string $expected): void
    {
        $manager = new MySQLSchemaManager();
        $reflectionMethod = new ReflectionMethod($manager, 'normaliseGeneratedColumnExpression');
        $this->assertSame($expected, $reflectionMethod->invoke($manager, $expression));
    }

    // public function testMakeGenerated(): void
}
