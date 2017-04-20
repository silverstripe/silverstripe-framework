<?php

namespace SilverStripe\View;

class ViewableData_Customised extends ViewableData
{

    /**
     * @var ViewableData
     */
    protected $original, $customised;

    /**
     * Instantiate a new customised ViewableData object
     *
     * @param ViewableData $originalObject
     * @param ViewableData $customisedObject
     */
    public function __construct(ViewableData $originalObject, ViewableData $customisedObject)
    {
        $this->original = $originalObject;
        $this->customised = $customisedObject;

        $this->original->setCustomisedObj($this);

        parent::__construct();
    }

    public function __call($method, $arguments)
    {
        if ($this->customised->hasMethod($method)) {
            return call_user_func_array(array($this->customised, $method), $arguments);
        }

        return call_user_func_array(array($this->original, $method), $arguments);
    }

    public function __get($property)
    {
        if (isset($this->customised->$property)) {
            return $this->customised->$property;
        }

        return $this->original->$property;
    }

    public function __set($property, $value)
    {
        $this->customised->$property = $this->original->$property = $value;
    }

    public function hasMethod($method)
    {
        return $this->customised->hasMethod($method) || $this->original->hasMethod($method);
    }

    public function cachedCall($field, $arguments = null, $identifier = null)
    {
        if ($this->customised->hasMethod($field) || $this->customised->hasField($field)) {
            return $this->customised->cachedCall($field, $arguments, $identifier);
        }
        return $this->original->cachedCall($field, $arguments, $identifier);
    }

    public function obj($fieldName, $arguments = null, $cache = false, $cacheName = null)
    {
        if ($this->customised->hasField($fieldName) || $this->customised->hasMethod($fieldName)) {
            return $this->customised->obj($fieldName, $arguments, $cache, $cacheName);
        }
        return $this->original->obj($fieldName, $arguments, $cache, $cacheName);
    }
}
