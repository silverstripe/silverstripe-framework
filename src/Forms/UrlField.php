<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Validation\ConstraintValidator;
use Symfony\Component\Validator\Constraints\Url;

/**
 * Text input field with validation for a url
 * Url must include a scheme, either http:// or https://
 */
class UrlField extends TextField
{
    public function Type()
    {
        return 'text url';
    }

    public function validate($validator)
    {
        $result = true;
        if ($this->value && !ConstraintValidator::validate($this->value, new Url())->isValid()) {
            $validator->validationError(
                $this->name,
                _t(__CLASS__ . '.INVALID', 'Please enter a valid URL'),
                'validation'
            );
            $result = false;
        }
        return $this->extendValidationResult($result, $validator);
    }
}
