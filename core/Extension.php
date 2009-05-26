<?php
/**
 * Add extension that can be added to an object with {@link Object::add_extension()}.
 * For {@link DataObject} extensions, use {@link DataObjectDecorator}.
 * Each extension instance has an "owner" instance, accessible through
 * {@link getOwner()}.
 * Every object instance gets its own set of extension instances,
 * meaning you can set parameters specific to the "owner instance"
 * in new Extension instances.
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
	 * The base class that this extension was applied to; $this->owner must be one of these
	 * @var DataObject
	 */
	protected $ownerBaseClass;

	/**
	 * Set the owner of this decorator.
	 * @param Object $owner The owner object,
	 * @param string $ownerBaseClass The base class that the extension is applied to; this may be
	 * the class of owner, or it may be a parent.  For example, if Versioned was applied to SiteTree,
	 * and then a Page object was instantiated, $owner would be a Page object, but $ownerBaseClass
	 * would be 'SiteTree'.
	 */
	function setOwner(Object $owner, $ownerBaseClass = null) {
		$this->ownerBaseClass = $ownerBaseClass ? $ownerBaseClass : $owner->class;
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
	
	/**
	 * Helper method to strip eval'ed arguments from a string
	 * thats passed to {@link DataObject::$extensions} or 
	 * {@link Object::add_extension()}.
	 * 
	 * @param string $extensionStr E.g. "Versioned('Stage','Live')"
	 * @return string Extension classname, e.g. "Versioned"
	 */
	public static function get_classname_without_arguments($extensionStr) {
		return (($p = strpos($extensionStr, '(')) !== false) ? substr($extensionStr, 0, $p) : $extensionStr;
	}
	
}

?>