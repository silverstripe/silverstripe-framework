<?php

namespace SilverStripe\View;

use SilverStripe\Dev\Deprecation;

/**
 * @deprecated 5.4.0 Will be renamed to SilverStripe\Model\ModelDataCustomised
 */
class ViewableData_Customised extends ViewableData
{
    protected ViewableData $original;

    protected ViewableData $customised;

    /**
     * Instantiate a new customised ViewableData object
     */
    public function __construct(ViewableData $originalObject, ViewableData $customisedObject)
    {
        Deprecation::withNoReplacement(function () {
            Deprecation::notice('5.4.0', 'Will be renamed to SilverStripe\Model\ModelDataCustomised', Deprecation::SCOPE_CLASS);
        });

        $this->original = $originalObject;
        $this->customised = $customisedObject;

        $this->original->setCustomisedObj($this);

        parent::__construct();
    }

    public function __call($method, $arguments)
    {
        if ($this->customised->hasMethod($method)) {
            return call_user_func_array([$this->customised, $method], $arguments ?? []);
        }

        return call_user_func_array([$this->original, $method], $arguments ?? []);
    }

    public function __get(string $property): mixed
    {
        if (isset($this->customised->$property)) {
            return $this->customised->$property;
        }

        return $this->original->$property;
    }

    public function __set(string $property, mixed $value): void
    {
        $this->customised->$property = $this->original->$property = $value;
    }

    public function __isset(string $property): bool
    {
        return isset($this->customised->$property) || isset($this->original->$property) || parent::__isset($property);
    }

    public function hasMethod($method)
    {
        return $this->customised->hasMethod($method) || $this->original->hasMethod($method);
    }

    public function cachedCall(string $fieldName, array $arguments = [], ?string $cacheName = null): object
    {
        if ($this->customisedHas($fieldName)) {
            return $this->customised->cachedCall($fieldName, $arguments, $cacheName);
        }
        return $this->original->cachedCall($fieldName, $arguments, $cacheName);
    }

    public function obj(
        string $fieldName,
        array $arguments = [],
        bool $cache = false,
        ?string $cacheName = null
    ): ?object {
        if ($this->customisedHas($fieldName)) {
            return $this->customised->obj($fieldName, $arguments, $cache, $cacheName);
        }
        return $this->original->obj($fieldName, $arguments, $cache, $cacheName);
    }

    private function customisedHas(string $fieldName): bool
    {
        return property_exists($this->customised, $fieldName) ||
            $this->customised->hasField($fieldName) ||
            $this->customised->hasMethod($fieldName);
    }
}
