<?php

/**
 * Add extension that can be added to an object with Object::add_extension().
 * For DataObject extensions, use DataObjectDecorator
 */

abstract class Extension extends Object {
	/**
	 * The DataObject that owns this decorator.
	 * @var DataObject
	 */
	protected $owner;

	/**
	 * Set the owner of this decorator.
	 * @param DataObject $owner
	 */
	function setOwner(Object $owner) {
		$this->owner = $owner;
	}
}

?>