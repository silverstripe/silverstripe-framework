<?php

namespace SilverStripe\Core;

use BadMethodCallException;
use SilverStripe\ORM\DataObject;

/**
 * Add extension that can be added to an object with {@link Object::add_extension()}.
 * For {@link DataObject} extensions, use {@link DataExtension}.
 * Each extension instance has an "owner" instance, accessible through
 * {@link getOwner()}.
 * Every object instance gets its own set of extension instances,
 * meaning you can set parameters specific to the "owner instance"
 * in new Extension instances.
 *
 * @template T of object
 */
abstract class Extension
{
    /**
     * This is used by extensions designed to be applied to controllers.
     * It works the same way as {@link Controller::$allowed_actions}.
     */
    private static $allowed_actions = [];

    /**
     * The object this extension is applied to.
     *
     * @var T
     */
    protected $owner;

    /**
     * Stack of all parent owners, not including current owner
     *
     * @var array<T>
     */
    private $ownerStack = [];

    public function __construct()
    {
    }

    /**
     * Called when this extension is added to a particular class
     *
     * @param string $class
     * @param string $extensionClass
     * @param mixed $args
     */
    public static function add_to_class($class, $extensionClass, $args = null)
    {
        // NOP
    }

    /**
     * Set the owner of this extension.
     *
     * @param object $owner The owner object
     */
    public function setOwner($owner)
    {
        $this->ownerStack[] = $this->owner;
        $this->owner = $owner;
    }

    /**
     * Temporarily modify the owner. The original owner is ensured to be restored
     *
     * @param mixed $owner Owner to set
     * @param callable $callback Callback to invoke
     * @param array $args Args to pass to callback
     * @return mixed
     */
    public function withOwner($owner, callable $callback, $args = [])
    {
        try {
            $this->setOwner($owner);
            return $callback(...$args);
        } finally {
            $this->clearOwner();
        }
    }

    /**
     * Clear the current owner, and restore extension to the state prior to the last setOwner()
     */
    public function clearOwner()
    {
        if (empty($this->ownerStack)) {
            throw new BadMethodCallException("clearOwner() called more than setOwner()");
        }
        $this->owner = array_pop($this->ownerStack);
    }

    /**
     * Returns the owner of this extension.
     *
     * @return T
     */
    public function getOwner()
    {
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
    public static function get_classname_without_arguments($extensionStr)
    {
        // Split out both args and service name
        return strtok(strtok($extensionStr ?? '', '(') ?? '', '.');
    }

    /**
     * Invoke extension point. This will prefer explicit `extend` prefixed
     * methods.
     *
     * @param object $owner
     * @param string $method
     * @param array &...$arguments
     * @return mixed
     */
    public function invokeExtension($owner, $method, &...$arguments)
    {
        // Prefer `extend` prefixed methods
        $instanceMethod = method_exists($this, "extend{$method}")
            ? "extend{$method}"
            : (method_exists($this, $method) ? $method : null);
        if (!$instanceMethod) {
            return null;
        }

        try {
            $this->setOwner($owner);
            return $this->$instanceMethod(...$arguments);
        } finally {
            $this->clearOwner();
        }
    }
}
