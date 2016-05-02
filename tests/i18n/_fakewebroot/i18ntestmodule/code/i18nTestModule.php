<?php
class i18nTestModule extends DataObject implements TestOnly {

	static $db = array(
		'MyField' => 'Varchar',
	);

	public function myMethod() {
		_t(
			'i18nTestModule.ENTITY',
			'Entity with "Double Quotes"',
			'Comment for entity'
		);
	}
}
class i18nTestModule_Addition extends Object {
	public function myAdditionalMethod() {
		_t('i18nTestModule.ADDITION','Addition');
	}
}
