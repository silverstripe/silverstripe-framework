<?php
/**
 * @package framework
 * @subpackage tests
 */
class i18nTextCollectorTestMyObject extends DataObject implements TestOnly {
	static $db = array(
		'FirstProperty' => 'Varchar',
		'SecondProperty' => 'Int'
	);
	
	static $has_many = array(
		'Relation' => 'Group'
	);
	
	static $singular_name = "My Object";
	
	static $plural_name = "My Objects";
}
