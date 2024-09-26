<?php

namespace SilverStripe\Model;

class ModelDataCustomised extends ModelData
{
    protected ModelData $original;

    protected ModelData $customised;

    /**
     * Instantiate a new customised ModelData object
     */
    public function __construct(ModelData $originalObject, ModelData $customisedObject)
    {
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

    public function forTemplate(): string
    {
        return $this->original->forTemplate();
    }

    public function hasMethod($method)
    {
        return $this->customised->hasMethod($method) || $this->original->hasMethod($method);
    }

    public function castingHelper(string $field): ?string
    {
        if ($this->customisedHas($field)) {
            return $this->customised->castingHelper($field);
        }
        return $this->original->castingHelper($field);
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

    public function customisedHas(string $fieldName): bool
    {
        return property_exists($this->customised, $fieldName) ||
            $this->customised->hasField($fieldName) ||
            $this->customised->hasMethod($fieldName);
    }

    public function getCustomisedModelData(): ?ModelData
    {
        return $this->customised;
    }
}
