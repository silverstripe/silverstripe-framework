<?php
/**
 * @package framework
 * @subpackage tests
 */
class SecurityTokenTest extends SapphireTest {

	public function testIsEnabled() {
		$inst1 = SecurityToken::inst();
		$this->assertTrue($inst1->isEnabled());

		SecurityToken::disable();
		$inst2 = SecurityToken::inst();
		$this->assertFalse($inst2->isEnabled());

		SecurityToken::enable();
	}

	public function testEnableAndDisable() {
		$inst = SecurityToken::inst();
		$this->assertFalse($inst->check('randomvalue'));

		SecurityToken::disable();
		$inst = SecurityToken::inst();
		$this->assertTrue($inst->check('randomvalue'));

		SecurityToken::enable();
		$inst = SecurityToken::inst();
		$this->assertFalse($inst->check('randomvalue'));
	}

	public function testIsEnabledStatic() {
		$this->assertTrue(SecurityToken::is_enabled());

		SecurityToken::disable();
		$this->assertFalse(SecurityToken::is_enabled());

		SecurityToken::enable();
		$this->assertTrue(SecurityToken::is_enabled());
	}

	public function testInst() {
		$inst1 = SecurityToken::inst();
		$this->assertInstanceOf('SecurityToken', $inst1);
	}

	public function testInstReturnsSingleton() {
		$inst1 = SecurityToken::inst();
		$inst2 = SecurityToken::inst();
		$this->assertEquals($inst1, $inst2);
	}

	public function testCheck() {
		$t = new SecurityToken();

		$t->setValue(null);
		$this->assertFalse($t->check('invalidtoken'), 'Any token is invalid if no token is stored');

		$t->setValue(null);
		$this->assertFalse($t->check(null), 'NULL token is invalid if no token is stored');

		$t->setValue('mytoken');
		$this->assertFalse($t->check('invalidtoken'), 'Invalid token returns false');

		$t->setValue('mytoken');
		$this->assertTrue($t->check('mytoken'), 'Valid token returns true');
	}

	public function testReset() {
		$t = new SecurityToken();
		$initialValue = $t->getValue();
		$t->reset();

		$this->assertNotEquals($t->getValue(), $initialValue);
	}

	public function testCheckRequest() {
		$t = new SecurityToken();
		$n = $t->getName();

		$t->setValue(null);
		$r = new SS_HTTPRequest('GET', 'dummy', array($n => 'invalidtoken'));
		$this->assertFalse($t->checkRequest($r), 'Any token is invalid if no token is stored');

		$t->setValue(null);
		$r = new SS_HTTPRequest('GET', 'dummy', array($n => null));
		$this->assertFalse($t->checkRequest($r), 'NULL token is invalid if no token is stored');

		$t->setValue('mytoken');
		$r = new SS_HTTPRequest('GET', 'dummy', array($n => 'invalidtoken'));
		$this->assertFalse($t->checkRequest($r), 'Invalid token returns false');

		$t->setValue('mytoken');
		$r = new SS_HTTPRequest('GET', 'dummy', array($n => 'mytoken'));
		$this->assertTrue($t->checkRequest($r), 'Valid token returns true');
	}

	public function testAddToUrl() {
		$t = new SecurityToken();

		$url = 'http://absolute.tld/action/';
		$this->assertEquals(
			sprintf('%s?%s=%s', $url, $t->getName(), $t->getValue()),
			$t->addToUrl($url),
			'Urls without existing GET parameters'
		);

		$url = 'http://absolute.tld/?getparam=1';
		$this->assertEquals(
			sprintf('%s&%s=%s', $url, $t->getName(), $t->getValue()),
			$t->addToUrl($url),
			'Urls with existing GET parameters'
		);
	}

	public function testUpdateFieldSet() {
		$fs = new FieldList();
		$t = new SecurityToken();
		$t->updateFieldSet($fs);
		$f = $fs->dataFieldByName($t->getName());

		$this->assertInstanceOf('HiddenField', $f);
		$this->assertEquals($f->getName(), $t->getName(), 'Name matches');
		$this->assertEquals($f->Value(), $t->getValue(), 'Value matches');
	}

	public function testUpdateFieldSetDoesntAddTwice() {
		$fs = new FieldList();
		$t = new SecurityToken();
		$t->updateFieldSet($fs); // first
		$t->updateFieldSet($fs); // second
		$f = $fs->dataFieldByName($t->getName());

		$this->assertInstanceOf('HiddenField', $f);
		$this->assertEquals(1, $fs->Count());
	}

	public function testUnnamedTokensCarrySameValue() {
		$t1 = new SecurityToken();
		$t2 = new SecurityToken();

		$this->assertEquals($t1->getName(), $t2->getName());
		$this->assertEquals($t1->getValue(), $t2->getValue());
	}

	public function testNamedTokensCarryDifferentValues() {
		$t1 = new SecurityToken('one');
		$t2 = new SecurityToken('two');

		$this->assertNotEquals($t1->getName(), $t2->getName());
		$this->assertNotEquals($t1->getValue(), $t2->getValue());
	}
}
