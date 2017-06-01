<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\SapphireTest;

class TransactionTest extends SapphireTest
{

    protected static $extra_dataobjects = array(
        TransactionTest\TestObject::class
    );

    public function testCreateWithTransaction()
    {

        if (DB::get_conn()->supportsTransactions()==true) {
            DB::get_conn()->transactionStart();
            $obj=new TransactionTest\TestObject();
            $obj->Title='First page';
            $obj->write();

            $obj=new TransactionTest\TestObject();
            $obj->Title='Second page';
            $obj->write();

            //Create a savepoint here:
            DB::get_conn()->transactionSavepoint('rollback');

            $obj=new TransactionTest\TestObject();
            $obj->Title='Third page';
            $obj->write();

            $obj=new TransactionTest\TestObject();
            $obj->Title='Fourth page';
            $obj->write();

            //Revert to a savepoint:
            DB::get_conn()->transactionRollback('rollback');

            $first = DataObject::get(
                TransactionTest\TestObject::class,
                sprintf('%s = %s', Convert::symbol2sql('Title'), Convert::raw2sql('First page', true))
            );
            $second = DataObject::get(
                TransactionTest\TestObject::class,
                sprintf('%s = %s', Convert::symbol2sql('Title'), Convert::raw2sql('Second page', true))
            );
            $third = DataObject::get(
                TransactionTest\TestObject::class,
                sprintf('%s = %s', Convert::symbol2sql('Title'), Convert::raw2sql('Third page', true))
            );
            $fourth = DataObject::get(
                TransactionTest\TestObject::class,
                sprintf('%s = %s', Convert::symbol2sql('Title'), Convert::raw2sql('Fourth page', true))
            );

            //These pages should be in the system
            $this->assertTrue(is_object($first) && $first->exists());
            $this->assertTrue(is_object($second) && $second->exists());

            //These pages should NOT exist, we reverted to a savepoint:
            $this->assertFalse(is_object($third) && $third->exists());
            $this->assertFalse(is_object($fourth) && $fourth->exists());
        } else {
            $this->markTestSkipped('Current database does not support transactions');
        }
    }
}
