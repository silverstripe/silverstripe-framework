<?php
/**
 * @package framework
 * @subpackage tests
 */
class i18nTextCollectorTestMyObject extends DataObject implements TestOnly {
	private static $db = array(
		'FirstProperty' => 'Varchar',
		'SecondProperty' => 'Int'
	);

	private static $has_many = array(
		'Relation' => 'Group'
	);

	private static $singular_name = "My Object";

	private static $plural_name = "My Objects";
}
