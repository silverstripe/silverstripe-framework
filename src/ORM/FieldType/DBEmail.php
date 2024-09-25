<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Forms\EmailField;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\Validation\EmailValidator;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\NullableField;

class DBEmail extends DBVarchar
{
    private static array $field_validators = [
        [
            'class' => EmailValidator::class,
        ],
    ];

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
