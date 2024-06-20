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

    protected $defaults = [];

    public function __construct($data = [])
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
        if (empty($arguments)) {
            $default = new GridState_Data();
        } else {
            $default = $arguments[0];
        }

        return $this->getData($name, $default);
    }

    public function __clone()
    {
        $this->data = $this->toArray();
    }

    /**
     * Initialise the defaults values for the grid field state
     * These values won't be included in getChangesArray()
     *
     * @param array $defaults
     */
    public function initDefaults(array $defaults): void
    {
        foreach ($defaults as $key => $value) {
            $this->defaults[$key] = $value;
            $this->getData($key, $value);
        }
    }

    /**
     * Retrieve the value for the given key
     *
     * @param string $name The name of the value to retrieve
     * @param mixed $default Default value to assign if not set. Note that this *will* be included in getChangesArray()
     * @return mixed The value associated with this key, or the value specified by $default if not set
     */
    public function getData($name, $default = null)
    {
        if (!array_key_exists($name, $this->data ?? [])) {
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

    /**
     * Return all data, including defaults, as array
     */
    public function toArray()
    {
        $output = [];

        foreach ($this->data as $k => $v) {
            $output[$k] = (is_object($v) && method_exists($v, 'toArray')) ? $v->toArray() : $v;
        }

        return $output;
    }
    /**
     * Convert the state to an array including only value that differ from the default state defined by initDefaults()
     * @return array
     */
    public function getChangesArray(): array
    {
        $output = [];

        foreach ($this->data as $k => $v) {
            if (is_object($v) && method_exists($v, 'getChangesArray')) {
                $value = $v->getChangesArray();
                // Empty arrays represent pristine data, so we do not include them
                if (empty($value)) {
                    continue;
                }
            } else {
                $value = $v;
                // Check if we have a default value for this key and if it matches our current value
                if (array_key_exists($k, $this->defaults ?? []) && $this->defaults[$k] === $value) {
                    continue;
                }
            }

            $output[$k] = $value;
        }

        return $output;
    }
}
