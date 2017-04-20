<?php

namespace SilverStripe\Forms\GridField;

/**
 * Simple set of data, similar to stdClass, but without the notice-level
 * errors.
 *
 * @see GridState
 */
class GridState_Data
{

    /**
     * @var array
     */
    protected $data;

    public function __construct($data = array())
    {
        $this->data = $data;
    }

    public function __get($name)
    {
        return $this->getData($name, new GridState_Data());
    }

    public function __call($name, $arguments)
    {
        // Assume first parameter is default value
        $default = empty($arguments) ? new GridState_Data() : $arguments[0];
        return $this->getData($name, $default);
    }

    /**
     * Retrieve the value for the given key
     *
     * @param string $name The name of the value to retrieve
     * @param mixed $default Default value to assign if not set
     * @return mixed The value associated with this key, or the value specified by $default if not set
     */
    public function getData($name, $default = null)
    {
        if (!isset($this->data[$name])) {
            $this->data[$name] = $default;
        } else {
            if (is_array($this->data[$name])) {
                $this->data[$name] = new GridState_Data($this->data[$name]);
            }
        }

        return $this->data[$name];
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }

    public function __toString()
    {
        if (!$this->data) {
            return "";
        }

        return json_encode($this->toArray());
    }

    public function toArray()
    {
        $output = array();

        foreach ($this->data as $k => $v) {
            $output[$k] = (is_object($v) && method_exists($v, 'toArray')) ? $v->toArray() : $v;
        }

        return $output;
    }
}
