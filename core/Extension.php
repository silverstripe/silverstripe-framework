<?php
/**
 * Add extension that can be added to an object with {@link Object::add_extension()}.
 * For {@link DataObject} extensions, use {@link DataExtension}.
 * Each extension instance has an "owner" instance, accessible through
 * {@link getOwner()}.
 * Every object instance gets its own set of extension instances,
 * meaning you can set parameters specific to the "owner instance"
 * in new Extension instances.
 *
 * @package framework
 * @subpackage core
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
	 * @var DataObject
	 */
	protected $ownerBaseClass;

	/**
	 * Reference counter to ensure that the owner isn't cleared until clearOwner() has
	 * been called as many times as setOwner()
	 */
	private $ownerRefs = 0;

	public $class;

	public function __construct() {
		$this->class = get_class($this);
	}

	/**
	 * Called when this extension is added to a particular class
	 *
	 * @static
	 * @param $class
	 */
	public static function add_to_class($class, $extensionClass, $args = null) {
		// NOP
	}

	/**
	 * Set the owner of this extension.
	 * @param Object $owner The owner object,
	 * @param string $ownerBaseClass The base class that the extension is applied to; this may be
	 * the class of owner, or it may be a parent.  For example, if Versioned was applied to SiteTree,
	 * and then a Page object was instantiated, $owner would be a Page object, but $ownerBaseClass
	 * would be 'SiteTree'.
	 */
	public function setOwner($owner, $ownerBaseClass = null) {
		if($owner) $this->ownerRefs++;
		$this->owner = $owner;

		if($ownerBaseClass) $this->ownerBaseClass = $ownerBaseClass;
		else if(!$this->ownerBaseClass && $owner) $this->ownerBaseClass = $owner->class;
	}

	public function clearOwner() {
		if($this->ownerRefs <= 0) user_error("clearOwner() called more than setOwner()", E_USER_WARNING);
		$this->ownerRefs--;
		if($this->ownerRefs == 0) $this->owner = null;
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

