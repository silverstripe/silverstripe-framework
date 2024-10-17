<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use RuntimeException;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Validation\FieldValidation\FieldValidationInterface;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\FormField;

trait FieldValidatorsTrait
{
    /**
     * FieldValidators configuration for the field, which is either a FormField or DBField
     *
     * Each item in the array can be one of the following
     * a) MyFieldValidator::class,
     * b) MyFieldValidator::class => [null, 'getMyArg'],
     * c) MyFieldValidator::class => null,
     *
     * a) Will create a FieldValidator and pass the name and value of the field as args to the constructor
     * b) Will create a FieldValidator and pass the name, value, make a pass additional args, calling each
     *    non-null value on the field e.g. it will skip the first arg and call $field->getMyArg() for the second arg
     * c) Will disable a previously set FieldValidator. This is useful to disable a FieldValidator that was set
     *    on a parent class
     *
     * You may only have a single instance of a FieldValidator class per field
     */
    private static array $field_validators = [];

    /**
     * Validate this field
     */
    public function validate(): ValidationResult
    {
        $result = ValidationResult::create();
        // Skip validation if the field value is null
        if ($this->getValue() === null) {
            return $result;
        }
        $fieldValidators = $this->getFieldValidators();
        foreach ($fieldValidators as $fieldValidator) {
            $validationResult = $fieldValidator->validate();
            if (!$validationResult->isValid()) {
                $result->combineAnd($validationResult);
            }
        }
        return $result;
    }

    /**
     * Get FieldValidators based on `field_validators` configuration
     */
    private function getFieldValidators(): array
    {
        $fieldValidators = [];
        // Used to disable a validator that was previously set with an int index
        $disabledClasses = [];
        $interface = FieldValidationInterface::class;
        // temporary check, will make FormField implement FieldValidationInterface in a future PR
        $tmp = FormField::class;
        if (!is_a($this, $interface) && !is_a($this, $tmp)) {
            $class = get_class($this);
            throw new RuntimeException("Class $class does not implement interface $interface");
        }
        /** @var FieldValidationInterface|Configurable $this */
        $name = $this->getName();
        $value = $this->getValueForValidation();
        // Field name is required for FieldValidators when called ValidationResult::addFieldMessage()
        if ($name === '') {
            throw new RuntimeException('Field name is blank');
        }
        $classes = [];
        $config = $this->config()->get('field_validators');
        foreach ($config as $indexOrClass => $classOrArgCallsOrDisable) {
            $class = '';
            $argCalls = [];
            $disable = false;
            if (is_int($indexOrClass)) {
                $class = $classOrArgCallsOrDisable;
            } else {
                $class = $indexOrClass;
                $argCalls = $classOrArgCallsOrDisable;
                $disable = $classOrArgCallsOrDisable === null;
            }
            if ($disable) {
                $disabledClasses[$class] = true;
                continue;
            } else {
                if (isset($disabledClasses[$class])) {
                    unset($disabledClasses[$class]);
                }
            }
            if (!is_a($class, FieldValidator::class, true)) {
                throw new RuntimeException("Class $class is not a FieldValidator");
            }
            if (!is_array($argCalls)) {
                throw new RuntimeException("argCalls for FieldValidator $class is not an array");
            }
            $classes[$class] = $argCalls;
        }
        foreach (array_keys($disabledClasses) as $class) {
            unset($classes[$class]);
        }
        foreach ($classes as $class => $argCalls) {
            $args = [$name, $value];
            foreach ($argCalls as $i => $argCall) {
                if (!is_string($argCall) && !is_null($argCall)) {
                    throw new RuntimeException("argCall $i for FieldValidator $class is not a string or null");
                }
                if ($argCall) {
                    $args[] = call_user_func([$this, $argCall]);
                } else {
                    $args[] = null;
                }
            }
            $fieldValidators[$class] = Injector::inst()->createWithArgs($class, $args);
        }
        return array_values($fieldValidators);
    }
}
