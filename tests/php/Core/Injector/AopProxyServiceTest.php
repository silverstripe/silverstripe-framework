<?php

use SilverStripe\Core\Injector\AopProxyService;
use SilverStripe\Core\Injector\BeforeCallAspect;
use SilverStripe\Core\Injector\AfterCallAspect;
use SilverStripe\Dev\SapphireTest;


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
