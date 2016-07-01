<?php

/**
 * Namespaced dataobjcets used by DataObjectSchemaTest
 */
namespace Namespaced\DOST;
use SilverStripe\ORM\DataObject;


/**
 * Basic namespaced object
 */
class MyObject extends DataObject implements \TestOnly {
	private static $db = [
		'Title' => 'Varchar',
		'Description' => 'Text',
	];
}

/**
 * Namespaced object with custom table
 */
class MyObject_CustomTable extends DataObject implements \TestOnly {
	private static $table_name = 'CustomNamespacedTable';
	private static $db = [
		'Title' => 'Varchar',
		'Description' => 'Text',
	];

	private static $belongs_many_many = [
		'Parents' => 'Namespaced\DOST\MyObject_Namespaced_Subclass',
	];
}

/**
 * Namespaced subclassed object
 */
class MyObject_NestedObject extends MyObject implements \TestOnly {
	private static $db = [
		'Content' => 'HTMLText',
	];
}

/**
 * Namespaced object with custom table that itself is namespaced
 */
class MyObject_NamespacedTable extends DataObject implements \TestOnly {
	private static $table_name = 'Custom\NamespacedTable';
	private static $db = [
		'Title' => 'Varchar',
		'Description' => 'Text',
	];
	private static $has_one = [
		'Owner' => 'Namespaced\DOST\MyObject_NoFields',
	];
}

/**
 * Subclass of a namespaced class
 * Has a many_many to another namespaced table
 */
class MyObject_Namespaced_Subclass extends MyObject_NamespacedTable implements \TestOnly {
	private static $table_name = 'Custom\SubclassedTable';
	private static $db = [
		'Details' => 'Varchar',
	];
	private static $many_many = [
		'Children' => 'Namespaced\DOST\MyObject_CustomTable',
	];
}

/**
 * Namespaced class without any fields
 * has a has_many to another namespaced table
 */
class MyObject_NoFields extends DataObject implements \TestOnly {
	private static $has_many = [
		'Owns' => 'Namespaced\DOST\MyObject_NamespacedTable',
	];
}
