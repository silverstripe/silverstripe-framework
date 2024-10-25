<?php

namespace SilverStripe\Core\Validation\FieldValidation;

use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\ORM\FieldType\DBYear;

/**
 * Validates a value in DBYear field
 * This FieldValidator is not intended to be used in fields other than DBYear
 */
class YearFieldValidator extends IntFieldValidator
{
    private const YEAR_EXCEPTION = 0;

    public function __construct(
        string $name,
        mixed $value,
        ?int $minValue = null,
        ?int $maxValue = null
    ) {
        $minValue = YearFieldValidator::YEAR_EXCEPTION;
        parent::__construct($name, $value, $minValue, $maxValue);
    }

    protected function validateValue(): ValidationResult
    {
        $result = parent::validateValue();
        if (!$result->isValid()) {
            return $result;
        }
        if ($this->value < DBYear::MIN_YEAR && $this->value !== YearFieldValidator::YEAR_EXCEPTION) {
            $message = _t(
                __CLASS__ . '.TOOSMALL',
                'Value cannot be less than {minYear} unless it is {yearException}',
                [
                    'minYear' => DBYear::MIN_YEAR,
                    'yearException' => YearFieldValidator::YEAR_EXCEPTION
                ]
            );
            $result->addFieldError($this->name, $message);
        }
        return $result;
    }
}
