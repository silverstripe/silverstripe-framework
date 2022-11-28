<?php

namespace SilverStripe\Forms;

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

    protected $inputType = 'password';

    /**
     * If true, the field can accept a value attribute, e.g. from posted form data
     * @var bool
     */
    protected $allowValuePostback = false;

    /**
     * Returns an input field.
     *
     * @param string $name
     * @param null|string $title
     * @param string $value
     */
    public function __construct($name, $title = null, $value = '')
    {
        parent::__construct($name, $title, $value);
    }

    /**
     * @param bool $bool
     * @return $this
     */
    public function setAllowValuePostback($bool)
    {
        $this->allowValuePostback = (bool) $bool;

        return $this;
    }

    /**
     * @return bool
     */
    public function getAllowValuePostback()
    {
        return $this->allowValuePostback;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        $attributes = [];

        if (!$this->getAllowValuePostback()) {
            $attributes['value'] = null;
        }

        $autocomplete = $this->config()->get('autocomplete');

        if ($autocomplete) {
            $attributes['autocomplete'] = 'on';
        } else {
            $attributes['autocomplete'] = 'off';
        }

        return array_merge(
            parent::getAttributes(),
            $attributes
        );
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
}
