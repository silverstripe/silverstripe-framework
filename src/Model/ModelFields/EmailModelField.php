<?php

namespace SilverStripe\Model\ModelFields;

use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Model\ModelFields\StringModelField;
use SilverStripe\Core\Validation\ConstraintValidator;
use Symfony\Component\Validator\Constraints;

class EmailModelField extends StringModelField
{
    public function validate(): ValidationResult
    {
        $result = parent::validate();
        $message = _t('SilverStripe\\Forms\\EmailField.VALIDATION', 'Please enter an email address');
        
        $validationResult = ConstraintValidator::validate(
            $this->getValue(),
            new Constraints\Email(message: $message),
            $this->getName()
        );

        return $result->combineAnd($validationResult);
    }
}
