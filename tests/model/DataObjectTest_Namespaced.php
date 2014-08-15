<?php

namespace DataObjectTest;

/**
 * Right now this is only used in DataListTest, but extending it to DataObjectTest in the future would make sense.
 * Note that it was deliberated named to include "\N" to try and trip bad code up.
 */
class NamespacedClass extends \DataObject implements \TestOnly {
	private static $db = array(
		'Name' => 'Varchar',
	);

	private static $has_many = array(
		'Relations' => 'DataObjectTest\RelationClass'
	);
}

class RelationClass extends \DataObject implements \TestOnly {

	private static $db = array(
		'Title' => 'Varchar'
	);

	private static $has_one = array(
		'Parent' => 'DataObject'
	);
}
