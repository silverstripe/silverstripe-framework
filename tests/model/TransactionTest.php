<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class TransactionTest extends SapphireTest {
	
	protected $extraDataObjects = array(
		'TransactionTest_Object'
	);

	function testCreateWithTransaction() {

		if(DB::getConn()->supportsTransactions()==true){
			DB::getConn()->transactionStart();
			$obj=new TransactionTest_Object();
			$obj->Title='First page';
			$obj->write();

			$obj=new TransactionTest_Object();
			$obj->Title='Second page';
			$obj->write();

			//Create a savepoint here:
			DB::getConn()->transactionSavepoint('rollback');

			$obj=new TransactionTest_Object();
			$obj->Title='Third page';
			$obj->write();

			$obj=new TransactionTest_Object();
			$obj->Title='Forth page';
			$obj->write();

			//Revert to a savepoint:
			DB::getConn()->transactionRollback('rollback');

			DB::getConn()->transactionEnd();

			$first=DataObject::get('TransactionTest_Object', "\"Title\"='First page'");
			$second=DataObject::get('TransactionTest_Object', "\"Title\"='Second page'");
			$third=DataObject::get('TransactionTest_Object', "\"Title\"='Third page'");
			$forth=DataObject::get('TransactionTest_Object', "\"Title\"='Forth page'");

			//These pages should be in the system
			$this->assertTrue(is_object($first) && $first->exists());
			$this->assertTrue(is_object($second) && $second->exists());

			//These pages should NOT exist, we reverted to a savepoint:
			$this->assertFalse(is_object($third) && $third->exists());
			$this->assertFalse(is_object($forth) && $forth->exists());
		} else {
			$this->markTestSkipped('Current database does not support transactions');
		}
	}

	function testReadOnlyTransaction(){

		if(DB::getConn()->supportsTransactions()==true){

			$obj=new TransactionTest_Object();
			$obj->Title='Read only success';
			$obj->write();

			DB::getConn()->transactionStart('READ ONLY');

			try {
				$obj=new TransactionTest_Object();
				$obj->Title='Read only page failed';
				$obj->write();
			} catch (Exception $e) {
				//could not write this record
				//We need to do a rollback or a commit otherwise we'll get error messages
				DB::getConn()->transactionRollback();
			}

			DB::getConn()->transactionEnd();

			DataObject::flush_and_destroy_cache();

			$success=DataObject::get('TransactionTest_Object', "\"Title\"='Read only success'");
			$fail=DataObject::get('TransactionTest_Object', "\"Title\"='Read only page failed'");

			//This page should be in the system
			$this->assertTrue(is_object($success) && $success->exists());

			//This page should NOT exist, we had 'read only' permissions
			$this->assertFalse(is_object($fail) && $fail->exists());

		}

	}

}

class TransactionTest_Object extends DataObject implements TestOnly {
	static $db = array(
		'Title' => 'Varchar(255)'
	);
}