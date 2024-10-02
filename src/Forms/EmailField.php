<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Validation\ConstraintValidator;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;

/**
 * Text input field with validation for correct email format according to the relevant RFC.
 */
class EmailField extends TextField
{
    protected $inputType = 'email';

    public function Type()
    {
        return 'email text';
    }

    /**
     * Validates for RFC compliant email addresses.
     *
     * @param Validator $validator
     */
    public function validate($validator)
    {
        $this->value = trim($this->value ?? '');

        $message = _t('SilverStripe\\Forms\\EmailField.VALIDATION', 'Please enter an email address');
        $result = ConstraintValidator::validate(
            $this->value,
            new EmailConstraint(message: $message, mode: EmailConstraint::VALIDATION_MODE_STRICT),
            $this->getName()
        );
        $validator->getResult()->combineAnd($result);
        $isValid = $result->isValid();

        return $this->extendValidationResult($isValid, $validator);
    }

    public function getSchemaValidation()
    {
        $rules = parent::getSchemaValidation();
        $rules['email'] = true;
        return $rules;
    }
}
