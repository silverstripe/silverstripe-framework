<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class TransactionTest extends SapphireTest {

	function testCreateWithTransaction() {

		if(DB::getConn()->supportsTransactions()==true){
			DB::getConn()->transactionStart();
			$page=new Page();
			$page->Title='First page';
			$page->write();

			$page=new Page();
			$page->Title='Second page';
			$page->write();

			//Create a savepoint here:
			DB::getConn()->transactionSavepoint('rollback');

			$page=new Page();
			$page->Title='Third page';
			$page->write();

			$page=new Page();
			$page->Title='Forth page';
			$page->write();

			//Revert to a savepoint:
			DB::getConn()->transactionRollback('rollback');

			DB::getConn()->transactionEnd();

			$first=DataObject::get('Page', "\"Title\"='First page'");
			$second=DataObject::get('Page', "\"Title\"='Second page'");
			$third=DataObject::get('Page', "\"Title\"='Third page'");
			$forth=DataObject::get('Page', "\"Title\"='Forth page'");

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

}