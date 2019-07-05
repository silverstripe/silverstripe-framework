<?php

namespace SilverStripe\ORM;

/**
 * DataObjectInterface is an interface that other data systems in your application can implement in order to behave in
 * a manner similar to DataObject.
 *
 * In addition to the methods defined below, the data of the object should be directly accessible as fields.
 */
interface DataObjectInterface
{
    /**
     * Create a new data object, not yet in the database.  To load an object into the database, a null object should be
     * constructed, its fields set, and the write() method called.
     */
    public function __construct();

    /**
     * Write the current object back to the database.  It should know whether this is a new object, in which case this
     * would be an insert command, or if this is an existing object queried from the database, in which case thes would
     * be
     */
    public function write();

    /**
     * Remove this object from the database.  Doesn't do anything if this object isn't in the database.
     */
    public function delete();

    /**
     * Get the named field.
     * This function is sometimes called explicitly by the form system, so you need to define it, even if you use the
     * default field system.
     *
     * @param string $fieldName
     * @return mixed
     */
    public function __get($fieldName);

    /**
     * Save content from a form into a field on this data object.
     * Since the data comes straight from a form it can't be trusted and will need to be validated / escaped.'
     *
     * @param string $fieldName
     * @param mixed $val
     * @return $this
     */
    public function setCastedField($fieldName, $val);

    // The following are provided by ViewableData...

    /**
     * Return TRUE if a method exists on this object
     *
     * This should be used rather than PHP's inbuild method_exists() as it takes into account methods added via
     * extensions
     *
     * @param string $method
     * @return bool
     */
    public function hasMethod($method);

    /**
     * Check if a field exists on this object. This should be overloaded in child classes.
     *
     * @param string $field
     * @return bool
     */
    public function hasField($field);

    /**
     * Get the value of a field on this object, automatically inserting the value into any available casting objects
     * that have been specified.
     *
     * @param string $fieldName
     * @param array $arguments
     * @param bool $cache Cache this object
     * @param string $cacheName a custom cache name
     * @return Object|DBField
     */
    public function obj($fieldName, $arguments = [], $cache = false, $cacheName = null);

    /**
     * Return the string-format type for the given field.
     *
     * @param string $field
     * @return string 'xml'|'raw'
     */
    public function escapeTypeForField($field);
}
