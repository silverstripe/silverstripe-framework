<?php
/**
 * Add extension that can be added to an object with Object::add_extension().
 * For DataObject extensions, use DataObjectDecorator
 *
 * @package sapphire
 * @subpackage core
 */
abstract class Extension extends Object {
	/**
	 * This is used by extensions designed to be applied to controllers.
	 * It works the same way as {@link Controller::$allowed_actions}.
	 */
	public static $allowed_actions = null;

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
	
	/**
	 * Returns the owner of this decorator
	 *
	 * @return Object
	 */
	public function getOwner() {
		return $this->owner;
	}
}

?>