<?php

class ConfigStaticManifestTestClassKeyword implements TestOnly {

	private static $foo = 'bar';

	public function __construct() {
		$this->inst = Injector::inst()->get(static::class);
	}

}
