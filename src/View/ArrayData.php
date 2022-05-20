<?php

namespace SilverStripe\View;

use SilverStripe\ORM\ArrayLib;
use InvalidArgumentException;
use stdClass;

/**
 * Lets you wrap a bunch of array data, or object members, into a {@link ViewableData} object.
 *
 * <code>
 * new ArrayData(array(
 *    "ClassName" => "Page",
 *    "AddAction" => "Add a new Page page",
 * ));
 * </code>
 */
class ArrayData extends ViewableData
{

    /**
     * @var array
     * @see ArrayData::_construct()
     */
    protected $array;

    /**
     * @param object|array $value An associative array, or an object with simple properties.
     * Converts object properties to keys of an associative array.
     */
    public function __construct($value = [])
    {
        if (is_object($value)) {
            $this->array = get_object_vars($value);
        } elseif (is_array($value)) {
            if (ArrayLib::is_associative($value)) {
                $this->array = $value;
            } elseif (count($value ?? []) === 0) {
                $this->array = [];
            } else {
                $message = 'ArrayData constructor expects an object or associative array,
                            enumerated array passed instead. Did you mean to use ArrayList?';
                throw new InvalidArgumentException($message);
            }
        } else {
            $message = 'ArrayData constructor expects an object or associative array';
            throw new InvalidArgumentException($message);
        }
        parent::__construct();
    }

    /**
     * Get the source array
     *
     * @return array
     */
    public function toMap()
    {
        return $this->array;
    }

    /**
     * Gets a field from this object.
     *
     *
     * If the value is an object but not an instance of
     * ViewableData, it will be converted recursively to an
     * ArrayData.
     *
     * If the value is an associative array, it will likewise be
     * converted recursively to an ArrayData.
     *
     * @param string $field
     * @return mixed
     */
    public function getField($field)
    {
        $value = $this->array[$field];
        if (is_object($value) && !$value instanceof ViewableData) {
            return new ArrayData($value);
        } elseif (ArrayLib::is_associative($value)) {
            return new ArrayData($value);
        } else {
            return $value;
        }
    }
    /**
    * Add or set a field on this object.
    *
    * @param string $field
    * @param mixed $value
    * @return $this
    */
    public function setField($field, $value)
    {
        $this->array[$field] = $value;
        return $this;
    }

    /**
     * Check array to see if field isset
     *
     * @param string $field Field Key
     * @return bool
     */
    public function hasField($field)
    {
        return isset($this->array[$field]);
    }

    /**
     * Converts an associative array to a simple object
     *
     * @param array $arr
     * @return stdClass $obj
     */
    public static function array_to_object($arr = null)
    {
        $obj = new stdClass();
        if ($arr) {
            foreach ($arr as $name => $value) {
                $obj->$name = $value;
            }
        }
        return $obj;
    }
}
