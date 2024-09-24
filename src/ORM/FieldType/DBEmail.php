<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\ORM\FieldType\DBVarchar;
use Symfony\Component\Validator\Constraints;
use SilverStripe\Core\Validation\ConstraintValidator;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\NullableField;
use SilverStripe\Forms\FormField;

class DBEmail extends DBVarchar
{
    // public function validate(): ValidationResult
    // {
    //     // https://symfony.com/doc/current/reference/constraints/Email.html
    //     $result = parent::validate();
    //     // $message = _t('SilverStripe\\Forms\\EmailField.VALIDATION', 'Please enter an email address');
    //     // $result = $result->combineAnd(
    //     //     ConstraintValidator::validate(
    //     //         $this->getValue(),
    //     //         new Constraints\Email(message: $message),
    //     //         $this->getName()
    //     //     )
    //     // );
    //     $result = $result->combineAnd($this->validateEmail());
    //     $this->extend('updateValidate', $result);
    //     return $result;
    // }

    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
    {
        // Set field with appropriate size
        $field = EmailField::create($this->name, $title);
        $field->setMaxLength($this->getSize());

        // Allow the user to select if it's null instead of automatically assuming empty string is
        if (!$this->getNullifyEmpty()) {
            return NullableField::create($field);
        }
        return $field;
    }
}
