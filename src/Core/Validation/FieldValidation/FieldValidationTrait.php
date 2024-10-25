<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use RuntimeException;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Validation\FieldValidation\FieldValidationInterface;
use SilverStripe\Core\Validation\ValidationResult;

/**
 * Trait to add FieldValidator validation to a field, e.g. a DBField or FormField
 * The field must implement FieldValidationInterface
 */
trait FieldValidationTrait
{
    use Configurable;

    /**
     * FieldValidators configuration for the field
     *
     * Each item in the array can be one of the following
     * a) MyFieldValidator::class,
     * b) MyFieldValidator::class => [null, 'getSomething'],
     * c) MyFieldValidator::class => null,
     *
     * a) Will create a MyFieldValidator and pass the name and value of the field as args to the constructor
     * b) Will create a MyFieldValidator and pass the name, value, and pass additional args, where each null values
     *    will be passed as null, and non-null values will call a method on the field e.g. will pass null for the first
     *    additional arg and call $field->getSomething() to get a value for the second additional arg
     * c) Will disable a previously set MyFieldValidator. This is useful to disable a FieldValidator that was set
     *    on a parent class
     *
     * You may only have a single instance of a given FieldValidator class per field, e.g. you can't have two
     * instances of a `MyFieldValidator` class for the same field.
     */
    private static array $field_validators = [];

    /**
     * Validate this field using FieldValidators
     */
    public function validate(): ValidationResult
    {
        $result = ValidationResult::create();
        $fieldValidators = $this->getFieldValidators();
        foreach ($fieldValidators as $fieldValidator) {
            $result->combineAnd($fieldValidator->validate());
        }
        return $result;
    }

    /**
     * Get instantiated FieldValidators based on `field_validators` configuration
     */
    private function getFieldValidators(): array
    {
        $fieldValidators = [];
        // Used to disable a validator that was previously set with an int index
        if (!is_a($this, FieldValidationInterface::class)) {
            $message = get_class($this) . ' must implement interface ' . FieldValidationInterface::class;
            throw new RuntimeException($message);
        }
        /** @var FieldValidationInterface|Configurable $this */
        $name = $this->getName();
        $value = $this->getValueForValidation();
        // Field name is required for FieldValidators when called ValidationResult::addFieldMessage()
        if ($name === '') {
            throw new RuntimeException('Field name is blank');
        }
        $classes = $this->getClassesFromConfig();
        foreach ($classes as $class => $argCalls) {
            $args = [$name, $value];
            foreach ($argCalls as $i => $argCall) {
                if (is_null($argCall)) {
                    $args[] = null;
                    continue;
                }
                if (!is_string($argCall)) {
                    throw new RuntimeException("argCall $i for FieldValidator $class is not a string or null");
                }
                if (!$this->hasMethod($argCall)) {
                    throw new RuntimeException("Method $argCall does not exist on " . get_class($this));
                }
                $args[] = call_user_func([$this, $argCall]);
            }
            $fieldValidators[] = Injector::inst()->createWithArgs($class, $args);
        }
        return $fieldValidators;
    }

    /**
     * Get FieldValidator classes based on `field_validators` configuration
     */
    private function getClassesFromConfig(): array
    {
        $classes = [];
        $disabledClasses = [];
        $config = static::config()->get('field_validators');
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
                // Disabling a class that was previously defined
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
        return $classes;
    }
}
