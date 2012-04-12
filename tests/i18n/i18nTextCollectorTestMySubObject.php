<?php
/**
 * @package framework
 * @subpackage tests
 */
class i18nTextCollectorTestMySubObject extends i18nTextCollectorTestMyObject implements TestOnly {
	static $db = array(
		'SubProperty' => 'Varchar',
	);
	
	static $has_many = array(
		'SubRelation' => 'Group'
	);
	
	static $singular_name = "My Sub Object";
	
	static $plural_name = "My Sub Objects";
}
