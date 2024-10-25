<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use InvalidArgumentException;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Core\Validation\FieldValidation\FieldValidator;

/**
 * Validates that a value is a string and optionally checks its multi-byte length.
 */
class StringFieldValidator extends FieldValidator
{
    /**
     * The minimum length of the string
     */
    private ?int $minLength;

    /**
     * The maximum length of the string
     */
    private ?int $maxLength;

    public function __construct(
        string $name,
        mixed $value,
        ?int $minLength = null,
        ?int $maxLength = null
    ) {
        parent::__construct($name, $value);
        if (!is_null($minLength) && $minLength < 0) {
            throw new InvalidArgumentException('minLength must be greater than or equal to 0');
        }
        if (!is_null($maxLength) && $maxLength < 0) {
            throw new InvalidArgumentException('maxLength must be greater than or equal to 0');
        }
        if (!is_null($minLength) && !is_null($maxLength) && $maxLength < $minLength) {
            throw new InvalidArgumentException('maxLength must be greater than or equal to minLength');
        }
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
    }

    protected function validateValue(): ValidationResult
    {
        $result = ValidationResult::create();
        if (!is_string($this->value)) {
            $message = _t(__CLASS__ . '.WRONGTYPE', 'Must be a string');
            $result->addFieldError($this->name, $message);
            return $result;
        }
        $len = mb_strlen($this->value);
        if (!is_null($this->minLength) && $len < $this->minLength) {
            $message = _t(
                __CLASS__ . '.TOOSHORT',
                'Must have at least {minLength} characters',
                ['minLength' => $this->minLength]
            );
            $result->addFieldError($this->name, $message);
        }
        if (!is_null($this->maxLength) && $len > $this->maxLength) {
            $message = _t(
                __CLASS__ . '.TOOLONG',
                'Can not have more than {maxLength} characters',
                ['maxLength' => $this->maxLength]
            );
            $result->addFieldError($this->name, $message);
        }
        return $result;
    }
}
