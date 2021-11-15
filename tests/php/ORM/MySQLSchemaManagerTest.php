<?php

namespace SilverStripe\ORM\Tests;

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
}
