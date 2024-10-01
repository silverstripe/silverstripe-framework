<?php

namespace SilverStripe\Forms;

use SilverStripe\Validation\EmailValidator;

/**
 * Text input field with validation for correct email format
 */
class EmailField extends TextField
{
    private static array $field_validators = [
        [
            'class' => EmailValidator::class,
        ],
    ];

    protected $inputType = 'email';
    /**
     * {@inheritdoc}
     */
    public function Type()
    {
        return 'email text';
    }

    public function getSchemaValidation()
    {
        $rules = parent::getSchemaValidation();
        $rules['email'] = true;
        return $rules;
    }
}
