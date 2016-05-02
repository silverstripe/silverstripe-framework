<?php
/**
 * @package framework
 * @subpackage tests
 */

class PasswordValidatorTest extends SapphireTest {

	public function testValidate() {
		$v = new PasswordValidator();
		$r = $v->validate('', new Member());
		$this->assertTrue($r->valid(), 'Empty password is valid by default');

		$r = $v->validate('mypassword', new Member());
		$this->assertTrue($r->valid(), 'Non-Empty password is valid by default');
	}

	public function testValidateMinLength() {
		$v = new PasswordValidator();

		$v->minLength(4);
		$r = $v->validate('123', new Member());
		$this->assertFalse($r->valid(), 'Password too short');

		$v->minLength(4);
		$r = $v->validate('1234', new Member());
		$this->assertTrue($r->valid(), 'Password long enough');
	}

	public function testValidateMinScore() {
		$v = new PasswordValidator();
		$v->characterStrength(3, array("lowercase", "uppercase", "digits", "punctuation"));

		$r = $v->validate('aA', new Member());
		$this->assertFalse($r->valid(), 'Passing too few tests');

		$r = $v->validate('aA1', new Member());
		$this->assertTrue($r->valid(), 'Passing enough tests');
	}

	public function testHistoricalPasswordCount() {
		$this->markTestIncomplete();
	}
}
