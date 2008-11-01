<?php
class i18nTestModule extends Object {
	function myMethod() {
		_t(
			'i18nTestModule.ENTITY', 
			'Entity with "Double Quotes"', 
			PR_LOW, 
			'Comment for entity'
		);
	}
}
class i18nTestModule_Addition extends Object {
	function myAdditionalMethod() {
		_t('i18nTestModule.ADDITION','Addition');
	}
}