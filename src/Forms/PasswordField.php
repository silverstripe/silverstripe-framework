<?php

namespace SilverStripe\Forms;

use SilverStripe\Dev\Deprecation;

/**
 * Password input field.
 */
class PasswordField extends TextField
{
    /**
     * Controls the autocomplete attribute on the field.
     *
     * Setting it to false will set the attribute to "off", which will hint the browser
     * to not cache the password and to not use any password managers.
     */
    private static $autocomplete;

    /**
     * Determines if the value should be set in the frontend when rendering.
     * Set to a default safe value. You should set this to true if you need these values
     * to be sent back by the server.
     *
     * @var bool
     */
    protected $displaysSetValue = false;

    protected $inputType = 'password';

    /**
     * Returns an input field.
     *
     * @param string $name
     * @param null|string $title
     * @param string $value
     */
    public function __construct($name, $title = null, $value = '')
    {
        if (count(func_get_args()) > 3) {
            Deprecation::notice(
                '3.0',
                'Use setMaxLength() instead of constructor arguments',
                Deprecation::SCOPE_GLOBAL
            );
        }

        parent::__construct($name, $title, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        $attributes = parent::getAttributes();

        $autocomplete = $this->config()->get('autocomplete');

        if ($autocomplete) {
            $attributes['autocomplete'] = 'on';
        } else {
            $attributes['autocomplete'] = 'off';
        }

        // Hide value when rendering
        if (!$this->getDisplaysSetValue()) {
            $attributes['value'] = null;
        }

        return $attributes;
    }

    /**
     * Creates a read-only version of the field.
     *
     * @return FormField
     */
    public function performReadonlyTransformation()
    {
        $field = $this->castedCopy('SilverStripe\\Forms\\ReadonlyField');

        $field->setValue('*****');

        return $field;
    }

    /**
     * {@inheritdoc}
     */
    public function Type()
    {
        return 'text password';
    }

    /**
     * Set if the assigned value should be emitted to the frontend
     *
     * @return bool
     */
    public function getDisplaysSetValue()
    {
        return $this->displaysSetValue;
    }

    /**
     * Set if the assigned value should be emitted to the frontend
     *
     * @param bool $displaysSetValue
     * @return $this
     */
    public function setDisplaysSetValue($displaysSetValue)
    {
        $this->displaysSetValue = $displaysSetValue;
        return $this;
    }
}
