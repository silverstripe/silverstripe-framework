<?php

namespace SilverStripe\Validation;

use RuntimeException;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Core\Injector\Injector;

/**
 * Abstract class that can be used as a validator for FormFields and DBFields
 */
abstract class FieldValidator
{
    protected string $name;
    protected mixed $value;

    public function __construct(string $name, mixed $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function validate(): ValidationResult
    {
        $result = ValidationResult::create();
        $result = $this->validateValue($result);
        return $result;
    }

    abstract protected function validateValue(ValidationResult $result): ValidationResult;

    public static function createFieldValidatorsForField(
        FormField|DBField $field,
        string $name,
        mixed $value
    ): array {
        $fieldValidators = [];
        $config = $field->config()->get('field_validators');
        foreach ($config as $spec) {
            $class = $spec['class'];
            $argCalls = $spec['argCalls'] ?? null;
            if (!is_a($class, FieldValidator::class, true)) {
                throw new RuntimeException("Class $class is not a FieldValidator");
            }
            $args = [$name, $value];
            if (!is_null($argCalls)) {
                if (!is_array($argCalls)) {
                    throw new RuntimeException("argCalls for $class is not an array");
                }
                foreach ($argCalls as $i => $argCall) {
                    if (!is_string($argCall) && !is_null($argCall)) {
                        throw new RuntimeException("argCall $i for $class is not a string or null");
                    }
                    if ($argCall) {
                        $args[] = call_user_func([$field, $argCall]);
                    } else {
                        $args[] = null;
                    }
                }
            }
            $fieldValidators[] = Injector::inst()->createWithArgs($class, $args);
        }
        return $fieldValidators;
    }
}
