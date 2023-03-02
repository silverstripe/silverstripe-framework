<?php

namespace SilverStripe\Forms;

/**
 * Text input field with validation for correct email format according to RFC 2822.
 */
class EmailField extends TextField
{

    protected $inputType = 'email';
    /**
     * {@inheritdoc}
     */
    public function Type()
    {
        return 'email text';
    }

    /**
     * Validates for RFC 2822 compliant email addresses.
     *
     * @see http://www.regular-expressions.info/email.html
     * @see http://www.ietf.org/rfc/rfc2822.txt
     *
     * @param Validator $validator
     *
     * @return string
     */
    public function validate($validator)
    {
        $result = true;
        $this->value = trim($this->value ?? '');

        $pattern = '^[a-z0-9!#$%&\'*+/=?^_`{|}~-]+(?:\\.[a-z0-9!#$%&\'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$';

        // Escape delimiter characters.
        $safePattern = str_replace('/', '\\/', $pattern ?? '');

        if ($this->value && !preg_match('/' . $safePattern . '/i', $this->value ?? '')) {
            $validator->validationError(
                $this->name,
                _t('SilverStripe\\Forms\\EmailField.VALIDATION', 'Please enter an email address'),
                'validation'
            );

            $result = false;
        }

        return $this->extendValidationResult($result, $validator);
    }

    public function getSchemaValidation()
    {
        $rules = parent::getSchemaValidation();
        $rules['email'] = true;
        return $rules;
    }
}
