<?php
/**
 * @package framework
 * @subpackage tests
 */
class i18nTextCollectorTestMySubObject extends i18nTextCollectorTestMyObject implements TestOnly {
	private static $db = [
		'SubProperty' => 'Varchar',
	];

	private static $has_many = [
		'SubRelation' => 'Group'
	];

	private static $singular_name = "My Sub Object";

	private static $plural_name = "My Sub Objects";
}
