<?php
/**
 * @package framework
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
			$obj->Title='Fourth page';
			$obj->write();

			//Revert to a savepoint:
			DB::getConn()->transactionRollback('rollback');

			DB::getConn()->transactionEnd();

			$first=DataObject::get('TransactionTest_Object', "\"Title\"='First page'");
			$second=DataObject::get('TransactionTest_Object', "\"Title\"='Second page'");
			$third=DataObject::get('TransactionTest_Object', "\"Title\"='Third page'");
			$fourth=DataObject::get('TransactionTest_Object', "\"Title\"='Fourth page'");

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

class TransactionTest_Object extends DataObject implements TestOnly {
	static $db = array(
		'Title' => 'Varchar(255)'
	);
}
