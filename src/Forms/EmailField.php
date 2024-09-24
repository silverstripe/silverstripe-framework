<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Validation\ConstraintValidator;
use Symfony\Component\Validator\Constraints;

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
     * @param Validator $validator
     *
     * @return string
     */
    public function validate($validator)
    {
        $this->value = trim($this->value ?? '');

        $result = true;
        // $message = _t('SilverStripe\\Forms\\EmailField.VALIDATION', 'Please enter an email address');
        // $validationResult = ConstraintValidator::validate(
        //     $this->value,
        //     new Constraints\Email(message: $message)
        // );
        $validationResult = $this->getModelField()->validate();

        if (!$validationResult->isValid()) {
            $validator->validationError(
                $this->name,
                $validationResult->getMessages()[0]['message'],
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
