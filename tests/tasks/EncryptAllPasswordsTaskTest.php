<?php
/**
 * @package framework
 * @subpackage tests
 */
class EncryptAllPasswordsTaskTest extends SapphireTest {
	public function testRun() {
		$m = new Member();
		$m->Password = 'plain';
		$m->PasswordEncryption = 'none';
		$m->write();

		$t = new EncryptAllPasswordsTask();
		$t->run(null);

		$m = DataObject::get_by_id('Member', $m->ID);
		$this->assertEquals($m->PasswordEncryption, 'blowfish');
		$this->assertNotEquals($m->Password, 'plain');
		$result = $m->checkPassword('plain');
		$this->assertTrue($result->valid());
	}
}
