<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Forms\EmailField;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\Core\Validation\FieldValidation\EmailFieldValidator;
use SilverStripe\Forms\FormField;

class DBEmail extends DBVarchar
{
    private static array $field_validators = [
        EmailFieldValidator::class,
    ];

    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
    {
        $field = EmailField::create($this->name, $title);
        $field->setMaxLength($this->getSize());
        return $field;
    }
}
