<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\Tests\TransactionTest\TestObject;

class TransactionTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected $usesTransactions = false;

    protected static $extra_dataobjects = [
        TransactionTest\TestObject::class,
    ];

    private static $originalVersionInfo;

    protected function setUp()
    {
        parent::setUp();
        self::$originalVersionInfo = Deprecation::dump_settings();
    }

    protected function tearDown()
    {
        Deprecation::restore_settings(self::$originalVersionInfo);
        parent::tearDown();
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        if (!DB::get_conn()->supportsTransactions()) {
            static::markTestSkipped('Current database does not support transactions');
        }
    }

    public function testTransactions()
    {
        $conn = DB::get_conn();
        if (!$conn->supportsTransactions()) {
            $this->markTestSkipped("DB Doesn't support transactions");
            return;
        }

        // Test that successful transactions are comitted
        $obj = new TestObject();
        $failed = false;
        $conn->withTransaction(
            function () use (&$obj) {
                $obj->Title = 'Save 1';
                $obj->write();
            },
            function () use (&$failed) {
                $failed = true;
            }
        );
        $this->assertEquals('Save 1', TestObject::get()->first()->Title);
        $this->assertFalse($failed);

        // Test failed transactions are rolled back
        $ex = null;
        $failed = false;
        try {
            $conn->withTransaction(
                function () use (&$obj) {
                    $obj->Title = 'Save 2';
                    $obj->write();
                    throw new \Exception("error");
                },
                function () use (&$failed) {
                    $failed = true;
                }
            );
        } catch (\Exception $ex) {
        }
        $this->assertTrue($failed);
        $this->assertEquals('Save 1', TestObject::get()->first()->Title);
        $this->assertInstanceOf('Exception', $ex);
        $this->assertEquals('error', $ex->getMessage());
    }

    public function testNestedTransaction()
    {
        if (!DB::get_conn()->supportsSavepoints()) {
            static::markTestSkipped('Current database does not support savepoints');
        }

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
        // First/Second in a successful transaction
        DB::get_conn()->transactionStart();
        $obj = new TransactionTest\TestObject();
        $obj->Title = 'First page';
        $obj->write();

        $obj = new TransactionTest\TestObject();
        $obj->Title = 'Second page';
        $obj->write();
        DB::get_conn()->transactionEnd();

        // Third/Fourth in a rolled back transaction
        DB::get_conn()->transactionStart();
        $obj = new TransactionTest\TestObject();
        $obj->Title = 'Third page';
        $obj->write();

        $obj = new TransactionTest\TestObject();
        $obj->Title = 'Fourth page';
        $obj->write();
        DB::get_conn()->transactionRollback();


        $first = DataObject::get(TransactionTest\TestObject::class, "\"Title\"='First page'");
        $second = DataObject::get(TransactionTest\TestObject::class, "\"Title\"='Second page'");
        $third = DataObject::get(TransactionTest\TestObject::class, "\"Title\"='Third page'");
        $fourth = DataObject::get(TransactionTest\TestObject::class, "\"Title\"='Fourth page'");

        //These pages should be in the system
        $this->assertTrue(is_object($first) && $first->exists());
        $this->assertTrue(is_object($second) && $second->exists());

        //These pages should NOT exist, we rolled back
        $this->assertFalse(is_object($third) && $third->exists());
        $this->assertFalse(is_object($fourth) && $fourth->exists());
    }

    public function testReadOnlyTransaction()
    {
        if (!DB::get_conn()->supportsTransactions()) {
            $this->markTestSkipped('Current database doesn\'t support transactions');
            return;
        }

        if (!DB::get_conn()->supportsTransactionMode('READ ONLY')) {
            $this->markTestSkipped('Current database doesn\'t support READ ONLY transactions');
            return;
        }

        // This feature is deprecated in 4.4, but we're still testing it.
        Deprecation::notification_version('4.3.0');

        $page = new TestObject();
        $page->Title = 'Read only success';
        $page->write();

        DB::get_conn()->transactionStart('READ ONLY');

        try {
            $page = new TestObject();
            $page->Title = 'Read only page failed';
            $page->write();
            DB::get_conn()->transactionEnd();
        } catch (\Exception $e) {
            //could not write this record
            //We need to do a rollback or a commit otherwise we'll get error messages
            DB::get_conn()->transactionRollback();
        }

        DataObject::flush_and_destroy_cache();

        $success = DataObject::get_one(TestObject::class, "\"Title\"='Read only success'");
        $fail = DataObject::get_one(TestObject::class, "\"Title\"='Read only page failed'");

        //This page should be in the system
        $this->assertInternalType('object', $success);
        $this->assertTrue($success->exists());

        //This page should NOT exist, we had 'read only' permissions
        $this->assertNull($fail);
    }
}
