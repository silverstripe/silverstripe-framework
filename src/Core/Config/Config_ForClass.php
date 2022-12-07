<?php

namespace SilverStripe\Core\Config;

class Config_ForClass
{
    /**
     * @var string $class
     */
    protected $class;

    /**
     * @param string|object $class
     */
    public function __construct($class)
    {
        $this->class = is_object($class) ? get_class($class) : $class;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param string $name
     * @param mixed $val
     */
    public function __set($name, $val)
    {
        $this->set($name, $val);
    }

    /**
     * Merge a given config
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function merge($name, $value)
    {
        Config::modify()->merge($this->class, $name, $value);
        return $this;
    }

    /**
     * Replace config value
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function set($name, $value)
    {
        Config::modify()->set($this->class, $name, $value);
        return $this;
    }

        /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        $val = $this->__get($name);
        return isset($val);
    }

    /**
     * @param string $name
     * @param mixed $options
     * @return mixed
     */
    public function get($name, $options = 0)
    {
        return Config::inst()->get($this->class, $name, $options);
    }

    /**
     * Remove the given config key
     *
     * @param string $name
     * @return $this
     */
    public function remove($name)
    {
        Config::modify()->remove($this->class, $name);
        return $this;
    }

    /**
     * @param string $class
     *
     * @return Config_ForClass
     */
    public function forClass($class)
    {
        return Config::forClass($class);
    }

    /**
     * Get uninherited config
     *
     * @param string $name Name of config
     * @return mixed
     */
    public function uninherited($name)
    {
        return $this->get($name, Config::UNINHERITED);
    }
}
