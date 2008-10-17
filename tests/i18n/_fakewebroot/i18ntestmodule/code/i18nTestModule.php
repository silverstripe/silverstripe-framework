<?php
class i18nTestModule extends Object {
	function __construct() {
		_t(
			'i18nTestModule.ENTITY', 
			'Entity with "Double Quotes"', 
			PR_LOW, 
			'Comment for entity'
		);
		
		parent::__construct();
	}
}
class i18nTestModule_Addition extends Object {
	function __construct() {
		_t('i18nTestModule.ADDITION','Addition');
		
		parent::__construct();
	}
}