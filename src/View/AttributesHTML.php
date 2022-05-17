<?php

namespace SilverStripe\View;

use SilverStripe\Core\Convert;

/**
 * This trait can be applied to a ViewableData class to add the logic to render attributes in an SS template.
 *
 * When applying this trait to a class, you also need to add the following casting configuration.
 * ```
 * private static $casting = [
 *     'AttributesHTML' => 'HTMLFragment',
 *     'getAttributesHTML' => 'HTMLFragment',
 * ];
 * ```
 */
trait AttributesHTML
{

    /**
     * List of attributes to render on the frontend
     * @var array
     */
    protected $attributes = [];

    /**
     * Set an HTML attribute
     * @param $name
     * @param $value
     * @return $this
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * Retrieve the value of an HTML attribute
     * @param string $name
     * @return mixed|null
     */
    public function getAttribute($name)
    {
        $attributes = $this->getAttributes();

        if (isset($attributes[$name])) {
            return $attributes[$name];
        }

        return null;
    }

    /**
     * Get the default attributes when rendering this object.
     *
     * Called by `getAttributes()`
     *
     * @return array
     */
    abstract protected function getDefaultAttributes(): array;

    /**
     * Allows customization through an 'updateAttributes' hook on the base class.
     * Existing attributes are passed in as the first argument and can be manipulated,
     * but any attributes added through a subclass implementation won't be included.
     *
     * @return array
     */
    public function getAttributes()
    {
        $defaultAttributes = $this->getDefaultAttributes();

        $attributes = array_merge($defaultAttributes, $this->attributes);

        if (method_exists($this, 'extend')) {
            $this->extend('updateAttributes', $attributes);
        }

        return $attributes;
    }

    /**
     * Custom attributes to process. Falls back to {@link getAttributes()}.
     *
     * If at least one argument is passed as a string, all arguments act as excludes, by name.
     *
     * @param array $attributes
     *
     * @return string
     */
    public function getAttributesHTML($attributes = null)
    {
        $exclude = null;

        if (is_string($attributes)) {
            $exclude = func_get_args();
        }

        if (!$attributes || is_string($attributes)) {
            $attributes = $this->getAttributes();
        }

        $attributes = (array) $attributes;

        $attributes = array_filter($attributes ?? [], function ($v) {
            return ($v || $v === 0 || $v === '0');
        });

        if ($exclude) {
            $attributes = array_diff_key(
                $attributes ?? [],
                array_flip($exclude ?? [])
            );
        }

        // Create markup
        $parts = [];

        foreach ($attributes as $name => $value) {
            if ($value === true) {
                $value = $name;
            } else {
                if (is_scalar($value)) {
                    $value = (string) $value;
                } else {
                    $value = json_encode($value);
                }
            }

            $parts[] = sprintf('%s="%s"', Convert::raw2att($name), Convert::raw2att($value));
        }

        return implode(' ', $parts);
    }
}
