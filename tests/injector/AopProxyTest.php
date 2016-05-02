<?php

/**
 *
 *
 * @author <marcus@silverstripe.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class AopProxyTest extends SapphireTest {
	public function testBeforeMethodsCalled() {
		$proxy = new AopProxyService();
		$aspect = new BeforeAfterCallTestAspect();
		$proxy->beforeCall = array(
			'myMethod'	=> $aspect
		);

		$proxy->proxied = new ProxyTestObject();

		$result = $proxy->myMethod();

		$this->assertEquals('myMethod', $aspect->called);
		$this->assertEquals(42, $result);
	}

	public function testBeforeMethodBlocks() {
		$proxy = new AopProxyService();
		$aspect = new BeforeAfterCallTestAspect();
		$aspect->block = true;

		$proxy->beforeCall = array(
			'myMethod'	=> $aspect
		);

		$proxy->proxied = new ProxyTestObject();

		$result = $proxy->myMethod();

		$this->assertEquals('myMethod', $aspect->called);

		// the actual underlying method will NOT have been called
		$this->assertNull($result);

		// set up an alternative return value
		$aspect->alternateReturn = 84;

		$result = $proxy->myMethod();

		$this->assertEquals('myMethod', $aspect->called);

		// the actual underlying method will NOT have been called,
		// instead the alternative return value
		$this->assertEquals(84, $result);
	}

	public function testAfterCall() {
		$proxy = new AopProxyService();
		$aspect = new BeforeAfterCallTestAspect();

		$proxy->afterCall = array(
			'myMethod'	=> $aspect
		);

		$proxy->proxied = new ProxyTestObject();

		$aspect->modifier = function ($value) {
			return $value * 2;
		};

		$result = $proxy->myMethod();
		$this->assertEquals(84, $result);
	}

}

class ProxyTestObject {
	public function myMethod() {
		return 42;
	}
}

class BeforeAfterCallTestAspect implements BeforeCallAspect, AfterCallAspect {
	public $block = false;

	public $called;

	public $alternateReturn;

	public $modifier;

	public function beforeCall($proxied, $method, $args, &$alternateReturn) {
		$this->called = $method;

		if ($this->block) {
			if ($this->alternateReturn) {
				$alternateReturn = $this->alternateReturn;
			}
			return false;
		}
	}

	public function afterCall($proxied, $method, $args, $result) {
		if ($this->modifier) {
			$modifier = $this->modifier;
			return $modifier($result);
		}
	}
}
