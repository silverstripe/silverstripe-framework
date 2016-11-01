<?php

namespace SilverStripe\Core;

use BadMethodCallException;
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Object;

/**
 * Add extension that can be added to an object with {@link Object::add_extension()}.
 * For {@link DataObject} extensions, use {@link DataExtension}.
 * Each extension instance has an "owner" instance, accessible through
 * {@link getOwner()}.
 * Every object instance gets its own set of extension instances,
 * meaning you can set parameters specific to the "owner instance"
 * in new Extension instances.
 */
abstract class Extension {

	/**
	 * This is used by extensions designed to be applied to controllers.
	 * It works the same way as {@link Controller::$allowed_actions}.
	 */
	private static $allowed_actions = null;

	/**
	 * The object this extension is applied to.
	 *
	 * @var Object
	 */
	protected $owner;

	/**
	 * The base class that this extension was applied to; $this->owner must be one of these
	 *
	 * @var DataObject
	 */
	protected $ownerBaseClass;

	/**
	 * Ownership stack for recursive methods.
	 * Last item is current owner.
	 *
	 * @var array
	 */
	private $ownerStack = [];

	public $class;

	public function __construct() {
		$this->class = get_class($this);
	}

	/**
	 * Called when this extension is added to a particular class
	 *
	 * @param string $class
	 * @param string $extensionClass
	 * @param mixed $args
	 */
	public static function add_to_class($class, $extensionClass, $args = null) {
		// NOP
	}

	/**
	 * Set the owner of this extension.
	 *
	 * @param Object $owner The owner object,
	 * @param string $ownerBaseClass The base class that the extension is applied to; this may be
	 * the class of owner, or it may be a parent.  For example, if Versioned was applied to SiteTree,
	 * and then a Page object was instantiated, $owner would be a Page object, but $ownerBaseClass
	 * would be 'SiteTree'.
	 */
	public function setOwner($owner, $ownerBaseClass = null) {
		if($owner) {
			$this->ownerStack[] = $owner;
		}
		$this->owner = $owner;

		// Set ownerBaseClass
		if($ownerBaseClass) {
			$this->ownerBaseClass = $ownerBaseClass;
		} elseif(!$this->ownerBaseClass && $owner) {
			$this->ownerBaseClass = get_class($owner);
		}
	}

	/**
	 * Clear the current owner, and restore extension to the state prior to the last setOwner()
	 */
	public function clearOwner() {
		if(empty($this->ownerStack)) {
			throw new BadMethodCallException("clearOwner() called more than setOwner()");
		}
		array_pop($this->ownerStack);
		if($this->ownerStack) {
			$this->owner = end($this->ownerStack);
		} else {
			$this->owner = null;
		}
	}

	/**
	 * Returns the owner of this extension.
	 *
	 * @return Object
	 */
	public function getOwner() {
		return $this->owner;
	}

	/**
	 * Helper method to strip eval'ed arguments from a string
	 * that's passed to {@link DataObject::$extensions} or
	 * {@link Object::add_extension()}.
	 *
	 * @param string $extensionStr E.g. "Versioned('Stage','Live')"
	 * @return string Extension classname, e.g. "Versioned"
	 */
	public static function get_classname_without_arguments($extensionStr) {
		$parts = explode('(', $extensionStr);
		return $parts[0];
	}

}
