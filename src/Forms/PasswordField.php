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
    public function __construct(string $name, string $title = null, string $value = ''): void
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
     * @param bool $bool
     * @return $this
     */
    public function setAllowValuePostback(bool $bool): SilverStripe\Forms\PasswordField
    {
        $this->allowValuePostback = (bool) $bool;

        return $this;
    }

    /**
     * @return bool
     */
    public function getAllowValuePostback(): bool
    {
        return $this->allowValuePostback;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
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
    public function Type(): string
    {
        return 'text password';
    }
}
