<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Tests\TransactionTest\TestObject;

class TransactionTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        TransactionTest\TestObject::class,
    ];

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        if (!DB::get_conn()->supportsTransactions()) {
            static::markTestSkipped('Current database does not support transactions');
        }
    }

    public function testNestedTransaction()
    {
        $this->assertCount(0, TestObject::get());
        try {
            DB::get_conn()->withTransaction(function () {
                $obj = TransactionTest\TestObject::create();
                $obj->Title = 'Test';
                $obj->write();

                $this->assertCount(1, TestObject::get());

                DB::get_conn()->withTransaction(function () {
                    $obj = TransactionTest\TestObject::create();
                    $obj->Title = 'Test2';
                    $obj->write();
                    $this->assertCount(2, TestObject::get());
                });

                throw new \Exception('roll back transaction');
            });
        } catch (\Exception $e) {
            $this->assertEquals('roll back transaction', $e->getMessage());
        }
        $this->assertCount(0, TestObject::get());
    }

    public function testCreateWithTransaction()
    {
        DB::get_conn()->transactionStart();
        $obj = new TransactionTest\TestObject();
        $obj->Title = 'First page';
        $obj->write();

        $obj = new TransactionTest\TestObject();
        $obj->Title = 'Second page';
        $obj->write();

        //Create a savepoint here:
        DB::get_conn()->transactionSavepoint('rollback');

        $obj = new TransactionTest\TestObject();
        $obj->Title = 'Third page';
        $obj->write();

        $obj = new TransactionTest\TestObject();
        $obj->Title = 'Fourth page';
        $obj->write();

        //Revert to a savepoint:
        DB::get_conn()->transactionRollback('rollback');

        DB::get_conn()->transactionEnd();

        $first = DataObject::get(TransactionTest\TestObject::class, "\"Title\"='First page'");
        $second = DataObject::get(TransactionTest\TestObject::class, "\"Title\"='Second page'");
        $third = DataObject::get(TransactionTest\TestObject::class, "\"Title\"='Third page'");
        $fourth = DataObject::get(TransactionTest\TestObject::class, "\"Title\"='Fourth page'");

        //These pages should be in the system
        $this->assertTrue(is_object($first) && $first->exists());
        $this->assertTrue(is_object($second) && $second->exists());

        //These pages should NOT exist, we reverted to a savepoint:
        $this->assertFalse(is_object($third) && $third->exists());
        $this->assertFalse(is_object($fourth) && $fourth->exists());
    }
}
